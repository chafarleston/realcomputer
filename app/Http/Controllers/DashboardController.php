<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $companyId = \App\Models\Company::getMainCompany()->id;

        $ventaBase = function ($q) use ($companyId) {
            return $q->where('company_id', $companyId)
                ->where('tipo_documento', '!=', 'CO')
                ->where('sunat_estado', '!=', 'ANULADO');
        };

        $tt = \App\Models\Invoice::where('company_id', $companyId)
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN sunat_estado = 'ACEPTADO' THEN 1 ELSE 0 END) as aceptados")
            ->selectRaw("SUM(CASE WHEN sunat_estado IN ('PENDIENTE','ENVIADO') THEN 1 ELSE 0 END) as pendientes")
            ->selectRaw("COALESCE(SUM(CASE WHEN sunat_estado != 'ANULADO' AND tipo_documento != 'CO' THEN total ELSE 0 END), 0) as total_ventas")
            ->selectRaw("SUM(CASE WHEN tipo_documento = '01' THEN 1 ELSE 0 END) as facturas")
            ->selectRaw("SUM(CASE WHEN tipo_documento = '03' THEN 1 ELSE 0 END) as boletas")
            ->selectRaw("SUM(CASE WHEN tipo_documento = 'NV' THEN 1 ELSE 0 END) as notas_venta")
            ->selectRaw("COALESCE(SUM(CASE WHEN sunat_estado != 'ANULADO' AND tipo_documento = 'CO' THEN total ELSE 0 END), 0) as total_compras")
            ->selectRaw("SUM(CASE WHEN tipo_documento = 'CO' THEN 1 ELSE 0 END) as compras")
            ->first();

        $stats = [
            'total' => ($tt->total ?? 0) - ($tt->compras ?? 0),
            'aceptados' => $tt->aceptados ?? 0,
            'pendientes' => $tt->pendientes ?? 0,
            'total_ventas' => $tt->total_ventas ?? 0,
            'facturas' => $tt->facturas ?? 0,
            'boletas' => $tt->boletas ?? 0,
            'notas_venta' => $tt->notas_venta ?? 0,
            'total_compras' => $tt->total_compras ?? 0,
            'compras' => $tt->compras ?? 0,
            'total_productos' => \App\Models\Product::where('estado', 'ACTIVO')->count(),
            'total_clientes' => \App\Models\Customer::where('company_id', $companyId)->where('estado', 'ACTIVO')->count(),
        ];
        
        $ventasPorDia = [];
        $comprasPorDia = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = Carbon::now()->subDays($i);
            $ventas = $ventaBase(Invoice::query())
                ->whereDate('fecha_emision', $fecha)
                ->sum('total');
            $compras = Invoice::where('company_id', $companyId)
                ->where('tipo_documento', 'CO')
                ->where('sunat_estado', '!=', 'ANULADO')
                ->whereDate('fecha_emision', $fecha)
                ->sum('total');
            
            $ventasPorDia[] = [
                'dia' => $fecha->format('d/m'),
                'fecha' => $fecha->format('Y-m-d'),
                'monto' => round($ventas, 2),
            ];
            $comprasPorDia[] = [
                'dia' => $fecha->format('d/m'),
                'fecha' => $fecha->format('Y-m-d'),
                'monto' => round($compras, 2),
            ];
        }
        
        $monthlySales = [];
        for ($i = 29; $i >= 0; $i--) {
            $fecha = Carbon::now()->subDays($i);
            $ventas = $ventaBase(Invoice::query())
                ->whereDate('fecha_emision', $fecha)
                ->sum('total');
            
            $monthlySales[] = [
                'dia' => $fecha->format('d'),
                'fecha' => $fecha->format('Y-m-d'),
                'monto' => round($ventas, 2),
            ];
        }
        
        $topProducts = \DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.tipo_documento', '!=', 'CO')
            ->whereMonth('invoices.fecha_emision', Carbon::now()->month)
            ->selectRaw('products.descripcion, SUM(invoice_items.cantidad) as total_vendido, SUM(invoice_items.precio_venta) as total_monto')
            ->groupBy('products.descripcion')
            ->orderBy('total_vendido', 'desc')
            ->limit(5)
            ->get();

        $topPurchases = \DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_items.product_id', '=', 'products.id')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.tipo_documento', 'CO')
            ->whereMonth('invoices.fecha_emision', Carbon::now()->month)
            ->selectRaw('products.descripcion, SUM(invoice_items.cantidad) as total_comprado, SUM(invoice_items.precio_venta) as total_monto')
            ->groupBy('products.descripcion')
            ->orderBy('total_comprado', 'desc')
            ->limit(5)
            ->get();
        
        $recentInvoices = Invoice::with('customer')
            ->where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->limit(12)
            ->get();

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        $startOfPrevMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfPrevMonth = Carbon::now()->subMonth()->endOfMonth();

        $currentMonthSales = $ventaBase(Invoice::query())
            ->whereBetween('fecha_emision', [$startOfMonth, $endOfMonth])
            ->sum('total');

        $prevMonthSales = $ventaBase(Invoice::query())
            ->whereBetween('fecha_emision', [$startOfPrevMonth, $endOfPrevMonth])
            ->sum('total');

        $currentMonthPurchases = Invoice::where('company_id', $companyId)
            ->where('tipo_documento', 'CO')
            ->where('sunat_estado', '!=', 'ANULADO')
            ->whereBetween('fecha_emision', [$startOfMonth, $endOfMonth])
            ->sum('total');

        $prevMonthPurchases = Invoice::where('company_id', $companyId)
            ->where('tipo_documento', 'CO')
            ->where('sunat_estado', '!=', 'ANULADO')
            ->whereBetween('fecha_emision', [$startOfPrevMonth, $endOfPrevMonth])
            ->sum('total');

        $stats['ventas_mes'] = $currentMonthSales;
        $stats['ventas_mes_anterior'] = $prevMonthSales;
        $stats['crecimiento'] = $prevMonthSales > 0 ? (($currentMonthSales - $prevMonthSales) / $prevMonthSales) * 100 : ($currentMonthSales > 0 ? 100 : 0);

        $stats['compras_mes'] = $currentMonthPurchases;
        $stats['compras_mes_anterior'] = $prevMonthPurchases;
        $stats['crecimiento_compras'] = $prevMonthPurchases > 0 ? (($currentMonthPurchases - $prevMonthPurchases) / $prevMonthPurchases) * 100 : ($currentMonthPurchases > 0 ? 100 : 0);

        $stats['compras_mes_count'] = Invoice::where('company_id', $companyId)
            ->where('tipo_documento', 'CO')
            ->whereBetween('fecha_emision', [$startOfMonth, $endOfMonth])
            ->count();

        $stats['total'] = $ventaBase(Invoice::query())
            ->whereBetween('fecha_emision', [$startOfMonth, $endOfMonth])
            ->count();

        $stats['aceptados'] = $ventaBase(Invoice::query())
            ->whereBetween('fecha_emision', [$startOfMonth, $endOfMonth])
            ->where('sunat_estado', 'ACEPTADO')
            ->count();

        $stats['pendientes'] = $ventaBase(Invoice::query())
            ->whereBetween('fecha_emision', [$startOfMonth, $endOfMonth])
            ->whereIn('sunat_estado', ['PENDIENTE', 'ENVIADO'])
            ->count();

        $stats['facturas'] = $ventaBase(Invoice::query())
            ->whereBetween('fecha_emision', [$startOfMonth, $endOfMonth])
            ->where('tipo_documento', '01')
            ->count();

        $stats['boletas'] = $ventaBase(Invoice::query())
            ->whereBetween('fecha_emision', [$startOfMonth, $endOfMonth])
            ->where('tipo_documento', '03')
            ->count();

        $stats['notas_venta'] = $ventaBase(Invoice::query())
            ->whereBetween('fecha_emision', [$startOfMonth, $endOfMonth])
            ->where('tipo_documento', 'NV')
            ->count();

        return view('dashboard', compact('stats', 'ventasPorDia', 'comprasPorDia', 'recentInvoices', 'topProducts', 'topPurchases', 'monthlySales'));
    }
}
