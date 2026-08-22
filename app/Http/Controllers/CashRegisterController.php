<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Invoice;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashRegisterController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('permission', 'view_cashregisters');
        $companyId = \App\Models\Company::getMainCompany()->id;
        
        $cajaAbierta = CashRegister::where('company_id', $companyId)
            ->where('estado', 'ABIERTA')
            ->first();

        $flujo = null;
        if ($cajaAbierta) {
            $fechaApertura = $cajaAbierta->fecha_apertura instanceof \Carbon\Carbon
                ? $cajaAbierta->fecha_apertura
                : \Carbon\Carbon::parse($cajaAbierta->fecha_apertura);
            $ahora = now();

            $rango = function ($q) use ($fechaApertura, $ahora) {
                return $q->whereRaw("CONCAT(fecha_emision, ' ', COALESCE(hora_emision, '00:00:00')) BETWEEN ? AND ?", [
                    $fechaApertura->format('Y-m-d H:i:s'),
                    $ahora->format('Y-m-d H:i:s')
                ])->where('sunat_estado', '!=', 'ANULADO');
            };

            $ventas = $rango(Invoice::where('company_id', $companyId)->where('tipo_documento', '!=', 'CO'))->get();
            $compras = $rango(Invoice::where('company_id', $companyId)->where('tipo_documento', 'CO'))->get();

            $ventasBuckets = $this->paymentBuckets($ventas);
            $comprasBuckets = $this->paymentBuckets($compras);

            $totalVentas = round($ventasBuckets['efectivo'] + $ventasBuckets['tarjeta'] + $ventasBuckets['yape'] + $ventasBuckets['plin'] + $ventasBuckets['otro'], 2);
            $totalCompras = round($comprasBuckets['efectivo'] + $comprasBuckets['tarjeta'] + $comprasBuckets['yape'] + $comprasBuckets['plin'] + $comprasBuckets['otro'], 2);

            $ingresos = round((float) $cajaAbierta->movements()->where('tipo', 'INGRESO')->sum('monto'), 2);
            $gastos = round((float) $cajaAbierta->movements()->where('tipo', 'GASTO')->sum('monto'), 2);

            $flujo = [
                'apertura' => round((float) $cajaAbierta->monto_apertura, 2),
                'ventas' => $totalVentas,
                'compras' => $totalCompras,
                'ingresos' => $ingresos,
                'gastos' => $gastos,
                'saldo' => round($cajaAbierta->monto_apertura + $totalVentas + $ingresos - $totalCompras - $gastos, 2),
                'ventas_count' => $ventas->count(),
                'compras_count' => $compras->count(),
            ];
        }

        $cajas = CashRegister::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('cashregisters.index', compact('cajas', 'cajaAbierta', 'companyId', 'flujo'));
    }

    public function open(Request $request)
    {
        $this->authorize('permission', 'open_cashregister');

        $request->validate([
            'monto_apertura' => 'required|numeric|min:0'
        ]);

        $companyId = \App\Models\Company::getMainCompany()->id;
        
        $cajaExistente = CashRegister::where('company_id', $companyId)
            ->where('estado', 'ABIERTA')
            ->first();
            
        if ($cajaExistente) {
            return back()->with('error', 'Ya hay una caja abierta');
        }

        CashRegister::create([
            'company_id' => $companyId,
            'user_id' => Auth::id(),
            'monto_apertura' => $request->monto_apertura,
            'fecha_apertura' => now(),
            'estado' => 'ABIERTA',
            'referencia' => $request->referencia,
        ]);

        return redirect()->route('cashregisters.index')
            ->with('success', 'Caja abierta correctamente');
    }

    public function close(Request $request)
    {
        $this->authorize('permission', 'close_cashregister');

        $request->validate([
            'cashregister_id' => 'required|exists:cashregisters,id',
            'observaciones' => 'nullable|string|max:500',
        ]);

        $caja = CashRegister::findOrFail($request->cashregister_id);
        
        if ($caja->estado === 'CERRADA') {
            return back()->with('error', 'La caja ya está cerrada');
        }

        $companyId = $caja->company_id;

        $openTables = RestaurantOrder::where('company_id', $companyId)
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->where('order_type', '!=', 'kiosko')
            ->count();

        $openKiosko = RestaurantOrder::where('company_id', $companyId)
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->where('order_type', 'kiosko')
            ->count();

        if ($openTables > 0 || $openKiosko > 0) {
            $mensaje = 'No se puede cerrar caja: ';
            $partes = [];
            if ($openTables > 0) {
                $partes[] = "{$openTables} mesa(s)/estación(es) con operaciones abiertas";
            }
            if ($openKiosko > 0) {
                $partes[] = "{$openKiosko} pedido(s) de kiosko pendientes";
            }
            $mensaje .= implode(' y ', $partes) . '. Cierre o registre todos los pedidos antes de cerrar caja.';
            return back()->with('error', $mensaje);
        }

        $fechaApertura = $caja->fecha_apertura instanceof \Carbon\Carbon
            ? $caja->fecha_apertura 
            : \Carbon\Carbon::parse($caja->fecha_apertura);
        $fechaCierre = now();

        $rangeQuery = function ($q) use ($fechaApertura, $fechaCierre) {
            return $q->whereRaw("CONCAT(fecha_emision, ' ', COALESCE(hora_emision, '00:00:00')) BETWEEN ? AND ?", [
                $fechaApertura->format('Y-m-d H:i:s'),
                $fechaCierre->format('Y-m-d H:i:s')
            ])->where('sunat_estado', '!=', 'ANULADO');
        };

        $ventas = $rangeQuery(Invoice::where('company_id', $companyId)->where('tipo_documento', '!=', 'CO'))->get();
        $compras = $rangeQuery(Invoice::where('company_id', $companyId)->where('tipo_documento', 'CO'))->get();

        $ventasBuckets = $this->paymentBuckets($ventas);
        $comprasBuckets = $this->paymentBuckets($compras);

        $facturas = 0;
        $facturasTotal = 0;
        $boletas = 0;
        $boletasTotal = 0;
        $nvs = 0;
        $nvsTotal = 0;
        $kioskoTotal = 0;
        $kioskoCount = 0;

        foreach ($ventas as $v) {
            if (($v->order_source ?? '') === 'kiosko') {
                $kioskoTotal += $v->total;
                $kioskoCount++;
            }
            if ($v->tipo_documento === '01') {
                $facturas++;
                $facturasTotal += $v->total;
            } elseif ($v->tipo_documento === '03') {
                $boletas++;
                $boletasTotal += $v->total;
            } else {
                $nvs++;
                $nvsTotal += $v->total;
            }
        }

        $ingresos = (float) $caja->movements()->where('tipo', 'INGRESO')->sum('monto');
        $gastos = (float) $caja->movements()->where('tipo', 'GASTO')->sum('monto');

        $totalVentas = round($ventasBuckets['efectivo'] + $ventasBuckets['tarjeta'] + $ventasBuckets['yape'] + $ventasBuckets['plin'] + $ventasBuckets['otro'], 2);
        $totalCompras = round($comprasBuckets['efectivo'] + $comprasBuckets['tarjeta'] + $comprasBuckets['yape'] + $comprasBuckets['plin'] + $comprasBuckets['otro'], 2);

        $saldoFinal = round((float) $caja->monto_apertura + $totalVentas + $ingresos - $totalCompras - $gastos, 2);
        $montoCierre = $saldoFinal;

        $caja->update([
            'ventas_efectivo' => round($ventasBuckets['efectivo'], 2),
            'ventas_tarjeta' => round($ventasBuckets['tarjeta'], 2),
            'ventas_yape' => round($ventasBuckets['yape'], 2),
            'ventas_plin' => round($ventasBuckets['plin'], 2),
            'ventas_otro' => round($ventasBuckets['otro'], 2),
            'cantidad_ventas' => $ventas->count(),
            'total_ventas' => $totalVentas,
            'compras_efectivo' => round($comprasBuckets['efectivo'], 2),
            'compras_tarjeta' => round($comprasBuckets['tarjeta'], 2),
            'compras_yape' => round($comprasBuckets['yape'], 2),
            'compras_plin' => round($comprasBuckets['plin'], 2),
            'compras_otro' => round($comprasBuckets['otro'], 2),
            'cantidad_compras' => $compras->count(),
            'total_compras' => $totalCompras,
            'ingresos_total' => round($ingresos, 2),
            'gastos_total' => round($gastos, 2),
            'saldo_final' => $saldoFinal,
            'monto_cierre' => $montoCierre,
            'estado' => 'CERRADA',
            'fecha_cierre' => $fechaCierre,
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('cashregisters.show', $caja)
            ->with('success', 'Caja cerrada. Resumen contable generado.');
    }

    private function paymentBuckets($invoices): array
    {
        $buckets = ['efectivo' => 0, 'tarjeta' => 0, 'yape' => 0, 'plin' => 0, 'otro' => 0];

        foreach ($invoices as $v) {
            $metodo = $v->metodo_pago ?? 'EFECTIVO';

            if (str_contains($metodo, ' + ')) {
                $parts = explode(' + ', $metodo);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (str_contains($part, '/')) {
                        [$met, $amt] = explode('/', $part);
                        $amt = min((float) $amt, (float) $v->total);
                    } else {
                        $met = $part;
                        $amt = min((float) $v->total / count($parts), (float) $v->total);
                    }
                    $key = strtoupper($met);
                    match (true) {
                        str_starts_with($key, 'EFECT') => $buckets['efectivo'] += $amt,
                        str_starts_with($key, 'TARJ') => $buckets['tarjeta'] += $amt,
                        $key === 'YAPE' => $buckets['yape'] += $amt,
                        $key === 'PLIN' => $buckets['plin'] += $amt,
                        default => $buckets['otro'] += $amt,
                    };
                }
            } else {
                $key = strtoupper(explode('/', $metodo)[0]);
                $amt = str_contains($metodo, '/')
                    ? min((float) explode('/', $metodo)[1], (float) $v->total)
                    : (float) $v->total;
                match (true) {
                    str_starts_with($key, 'EFECT') => $buckets['efectivo'] += $amt,
                    str_starts_with($key, 'TARJ') => $buckets['tarjeta'] += $amt,
                    $key === 'YAPE' => $buckets['yape'] += $amt,
                    $key === 'PLIN' => $buckets['plin'] += $amt,
                    default => $buckets['otro'] += $amt,
                };
            }
        }

        return $buckets;
    }

    public function show(CashRegister $cashregister)
    {
        $this->authorize('permission', 'view_cashregisters');
        $data = $this->getCashRegisterData($cashregister);
        extract($data);
        
        $ventasEfectivo = 0;
        $ventasTarjeta = 0;
        $ventasYape = 0;
        $ventasPlin = 0;
        $ventasOtro = 0;

        foreach ($ventas as $venta) {
            $pago = $venta->metodo_pago ?? 'EFECTIVO';
            if (str_contains($pago, ' + ')) {
                $parts = explode(' + ', $pago);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (str_contains($part, '/')) {
                        [$metName, $metAmt] = explode('/', $part);
                        $amt = min((float) $metAmt, $venta->total);
                    } else {
                        $metName = $part;
                        $amt = round($venta->total / count($parts), 2);
                    }
                    $key = strtoupper($metName);
                    match (true) {
                        str_starts_with($key, 'EFECT') => $ventasEfectivo += $amt,
                        str_starts_with($key, 'TARJ') => $ventasTarjeta += $amt,
                        $key === 'YAPE' => $ventasYape += $amt,
                        $key === 'PLIN' => $ventasPlin += $amt,
                        default => $ventasOtro += $amt,
                    };
                }
            } else {
                $key = strtoupper(explode('/', $pago)[0]);
                match (true) {
                    str_starts_with($key, 'EFECT') => $ventasEfectivo += $venta->total,
                    str_starts_with($key, 'TARJ') => $ventasTarjeta += $venta->total,
                    $key === 'YAPE' => $ventasYape += $venta->total,
                    $key === 'PLIN' => $ventasPlin += $venta->total,
                    default => $ventasOtro += $venta->total,
                };
            }
        }
        $totalMetodos = $ventasEfectivo + $ventasTarjeta + $ventasYape + $ventasPlin + $ventasOtro;
        
        $kioskoVentas = $ventas->where('order_source', 'kiosko');
        $kioskoTotal = $kioskoVentas->sum('total');
        $kioskoCount = $kioskoVentas->count();

        $comprasBuckets = $this->paymentBuckets($compras);

        return view('cashregisters.show', compact(
            'cashregister', 'facturas', 'boletas', 'nvs', 'ventas',
            'categoriasVentas', 'productosVendidos',
            'ventasEfectivo', 'ventasTarjeta', 'ventasYape', 'ventasPlin', 'ventasOtro',
            'totalMetodos', 'lineasEliminadas', 'kioskoTotal', 'kioskoCount',
            'compras', 'comprasBuckets', 'movimientos', 'ingresos', 'gastos', 'saldo'
        ));
    }

    private function getCashRegisterData(CashRegister $cashregister)
    {
        if (!$cashregister->fecha_apertura) {
            $cashregister->fecha_apertura = now();
            $cashregister->save();
        }
        if (!$cashregister->fecha_cierre) {
            $cashregister->fecha_cierre = now();
        }

        $fechaApertura = $cashregister->fecha_apertura instanceof \Carbon\Carbon 
            ? $cashregister->fecha_apertura 
            : \Carbon\Carbon::parse($cashregister->fecha_apertura);
        $fechaCierre = $cashregister->fecha_cierre instanceof \Carbon\Carbon 
            ? $cashregister->fecha_cierre 
            : \Carbon\Carbon::parse($cashregister->fecha_cierre);

        $ventas = Invoice::where('company_id', $cashregister->company_id)
            ->whereRaw("CONCAT(fecha_emision, ' ', COALESCE(hora_emision, '00:00:00')) BETWEEN ? AND ?", [
                $fechaApertura->format('Y-m-d H:i:s'),
                $fechaCierre->format('Y-m-d H:i:s')
            ])
            ->where('sunat_estado', '!=', 'ANULADO')
            ->where('tipo_documento', '!=', 'CO')
            ->with(['items.product.category', 'customer'])
            ->get();

        $compras = Invoice::where('company_id', $cashregister->company_id)
            ->whereRaw("CONCAT(fecha_emision, ' ', COALESCE(hora_emision, '00:00:00')) BETWEEN ? AND ?", [
                $fechaApertura->format('Y-m-d H:i:s'),
                $fechaCierre->format('Y-m-d H:i:s')
            ])
            ->where('sunat_estado', '!=', 'ANULADO')
            ->where('tipo_documento', 'CO')
            ->with(['items.product', 'customer'])
            ->get();

        $movimientos = $cashregister->movements()->orderBy('fecha')->get();
        $ingresos = round((float) $movimientos->where('tipo', 'INGRESO')->sum('monto'), 2);
        $gastos = round((float) $movimientos->where('tipo', 'GASTO')->sum('monto'), 2);
        $saldo = round(
            (float) $cashregister->monto_apertura
            + (float) $ventas->sum('total')
            + $ingresos
            - (float) $compras->sum('total')
            - $gastos,
            2
        );

        $lineasEliminadas = RestaurantOrderItem::with('cancelledBy')
            ->where('kitchen_status', 'CANCELLED')
            ->whereNotNull('cancelled_from')
            ->whereIn('cancelled_from', ['SENT', 'READY', 'DELIVERED'])
            ->where('cancelled_at', '>=', $fechaApertura)
            ->where('cancelled_at', '<=', $fechaCierre)
            ->get();

        $facturas = $ventas->where('tipo_documento', '01');
        $boletas = $ventas->where('tipo_documento', '03');
        $nvs = $ventas->where('tipo_documento', 'NV');

        $ventasPorMetodo = [];
        $categoriasVentas = [];
        $productosVendidos = [];

        foreach ($ventas as $venta) {
            $metodo = $venta->metodo_pago ?? 'Efectivo';

            if (str_contains($metodo, ' + ')) {
                $parts = explode(' + ', $metodo);
                foreach ($parts as $part) {
                    $part = trim($part);
                    $met = str_contains($part, '/') ? explode('/', $part)[0] : $part;
                    if (!isset($ventasPorMetodo[$met])) {
                        $ventasPorMetodo[$met] = [];
                    }
                    $ventasPorMetodo[$met][] = $venta;
                }
            } else {
                $met = str_contains($metodo, '/') ? explode('/', $metodo)[0] : $metodo;
                if (!isset($ventasPorMetodo[$met])) {
                    $ventasPorMetodo[$met] = [];
                }
                $ventasPorMetodo[$met][] = $venta;
            }

            foreach ($venta->items as $item) {
                if ($item->descripcion === 'POR CONSUMO' && !empty($item->detalle_consumo)) {
                    foreach ($item->detalle_consumo as $detalle) {
                        $nombre = $detalle['product_name'] ?? 'Producto';
                        if (!isset($productosVendidos[$nombre])) {
                            $productosVendidos[$nombre] = ['cantidad' => 0, 'total' => 0];
                        }
                        $productosVendidos[$nombre]['cantidad'] += $detalle['quantity'] ?? 0;
                        $productosVendidos[$nombre]['total'] += $detalle['total'] ?? 0;
                    }
                } else {
                    $productoNombre = $item->descripcion;
                    if (!isset($productosVendidos[$productoNombre])) {
                        $productosVendidos[$productoNombre] = ['cantidad' => 0, 'total' => 0];
                    }
                    $productosVendidos[$productoNombre]['cantidad'] += $item->cantidad;
                    $productosVendidos[$productoNombre]['total'] += $item->precio_venta;
                }
            }
        }

        arsort($categoriasVentas);
        arsort($productosVendidos);

        return compact('cashregister', 'facturas', 'boletas', 'nvs', 'ventasPorMetodo', 'categoriasVentas', 'productosVendidos', 'lineasEliminadas', 'ventas', 'compras', 'movimientos', 'ingresos', 'gastos', 'saldo');
    }

    public function pdf(CashRegister $cashregister)
    {
        $this->authorize('permission', 'view_cashregisters');
        $data = $this->getCashRegisterData($cashregister);

        $pdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 10,
            'margin_bottom' => 15,
        ]);

        $html = view('cashregisters.pdf', $data)->render();
        $pdf->WriteHTML($html);

        return $pdf->Output('resumen-caja-a4-' . $cashregister->id . '.pdf', 'D');
    }

    public function ticketPdf(CashRegister $cashregister)
    {
        $this->authorize('permission', 'view_cashregisters');
        $data = $this->getCashRegisterData($cashregister);

        $pdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 200],
            'margin_top' => 2,
            'margin_bottom' => 2,
        ]);

        $html = view('cashregisters.ticket', $data)->render();
        $pdf->WriteHTML($html);

        return $pdf->Output('resumen-caja-ticket-' . $cashregister->id . '.pdf', 'D');
    }

    public function printCaja(CashRegister $cashregister)
    {
        $this->authorize('permission', 'view_cashregisters');
        try {
            $data = $this->getCashRegisterData($cashregister);
            
            $ventas = $data['ventas'];
            $data['total_ventas'] = $ventas->sum('total');
            $data['efectivo'] = 0;
            $data['tarjeta'] = 0;
            $data['yape'] = 0;
            $data['plin'] = 0;
            $data['otro'] = 0;
            $data['total_compras'] = $data['compras']->sum('total');
            $data['compras_efectivo'] = 0;
            $data['compras_tarjeta'] = 0;
            $data['compras_yape'] = 0;
            $data['compras_plin'] = 0;
            $data['compras_otro'] = 0;

            foreach ($ventas as $venta) {
                $pago = $venta->metodo_pago ?? 'EFECTIVO';
                if (str_contains($pago, ' + ')) {
                    $parts = explode(' + ', $pago);
                    foreach ($parts as $part) {
                        $part = trim($part);
                        if (str_contains($part, '/')) {
                            [$metName, $metAmt] = explode('/', $part);
                            $amt = min((float) $metAmt, $venta->total);
                        } else {
                            $metName = $part;
                            $amt = round($venta->total / count($parts), 2);
                        }
                        $key = strtoupper($metName);
                        match (true) {
                            str_starts_with($key, 'EFECT') => $data['efectivo'] += $amt,
                            str_starts_with($key, 'TARJ') => $data['tarjeta'] += $amt,
                            $key === 'YAPE' => $data['yape'] += $amt,
                            $key === 'PLIN' => $data['plin'] += $amt,
                            default => $data['otro'] += $amt,
                        };
                    }
                } else {
                    $key = strtoupper(explode('/', $pago)[0]);
                    match (true) {
                        str_starts_with($key, 'EFECT') => $data['efectivo'] += $venta->total,
                        str_starts_with($key, 'TARJ') => $data['tarjeta'] += $venta->total,
                        $key === 'YAPE' => $data['yape'] += $venta->total,
                        $key === 'PLIN' => $data['plin'] += $venta->total,
                        default => $data['otro'] += $venta->total,
                    };
                }
            }

            $comprasBuckets = $this->paymentBuckets($data['compras']);
            $data['compras_efectivo'] = $comprasBuckets['efectivo'];
            $data['compras_tarjeta'] = $comprasBuckets['tarjeta'];
            $data['compras_yape'] = $comprasBuckets['yape'];
            $data['compras_plin'] = $comprasBuckets['plin'];
            $data['compras_otro'] = $comprasBuckets['otro'];

            $data['ingresos'] = $data['ingresos'] ?? 0;
            $data['gastos'] = $data['gastos'] ?? 0;
            $data['saldo'] = $data['saldo'] ?? round(
                (float) $cashregister->monto_apertura + $data['total_ventas'] + $data['ingresos']
                - $data['total_compras'] - $data['gastos'],
                2
            );

            $printer = \App\Models\Printer::where('assigned_to', 'caja')->where('active', true)->first();
            if (!$printer) {
                return back()->with('error', 'No hay impresora Caja configurada');
            }
            $width = \App\Services\PlainTextTicket::widthForPaper($printer->paper_size);
            $text = \App\Services\PlainTextTicket::cashRegisterSummary($cashregister, $data, 'escpos', $width);
            app(\App\Services\PrintServerService::class)->printText($printer, $text);
            return back()->with('success', 'Resumen enviado a impresora Caja');
        } catch (\Exception $e) {
            \Log::error('Print caja error: ' . $e->getMessage());
            return back()->with('error', 'Error al imprimir: ' . $e->getMessage());
        }
    }
}