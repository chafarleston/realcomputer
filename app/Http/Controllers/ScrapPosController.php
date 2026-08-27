<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\RestaurantOrder;
use App\Models\RestaurantOrderItem;
use App\Models\RestaurantTable;
use App\Models\Serie;
use App\Services\PrintService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ScrapPosController extends Controller
{
    private array $modes = ['venta', 'compra'];

    public function index(Request $request, string $mode = 'venta')
    {
        $this->authorize('permission', 'view_pos');

        if (!in_array($mode, $this->modes)) {
            abort(404);
        }

        $mainCompany = Company::getMainCompany();
        if (!$mainCompany) {
            abort(400, 'No hay empresa principal configurada');
        }
        $companyId = $mainCompany->id;

        $cajaAbierta = CashRegister::where('company_id', $companyId)
            ->where('estado', 'ABIERTA')
            ->first();

        if (!$cajaAbierta) {
            return redirect()->route('cashregisters.index')
                ->with('error', 'No se puede acceder al ' . ($mode === 'venta' ? 'POS Venta' : 'POS Compra') . ' sin tener una caja abierta');
        }

        $this->ensureStations($companyId, $mode);

        $stations = RestaurantTable::where('company_id', $companyId)
            ->posMode($mode)
            ->orderBy('name')
            ->with(['orders' => function ($q) {
                $q->whereNotIn('status', ['COMPLETED', 'CANCELLED'])->withCount('items');
            }])
            ->get();

        $products = Product::where('company_id', $companyId)
            ->where('estado', 'ACTIVO')
            ->orderBy('descripcion')
            ->get();

        $categories = Category::where('company_id', $companyId)
            ->whereIn('estado', ['ACTIVO', 'ACT'])
            ->orderBy('nombre')
            ->get();

        $customers = Customer::where('company_id', $companyId)
            ->where('estado', 'ACTIVO')
            ->get();

        $series = Serie::where('company_id', $companyId)
            ->where('estado', 'ACTIVO')
            ->whereIn('tipo_documento', ['01', '03', 'NV'])
            ->get();

        $igvPercent = $mainCompany->getActiveIgvPercent();

        return view('scrap_pos.index', compact(
            'mode', 'stations', 'products', 'categories', 'customers', 'series',
            'companyId', 'cajaAbierta', 'igvPercent'
        ));
    }

    public function openStation(Request $request, string $mode, $station)
    {
        $this->authorize('permission', 'view_pos');

        if (!in_array($mode, $this->modes)) {
            return response()->json(['success' => false, 'message' => 'Modo inválido'], 400);
        }

        try {
            $table = RestaurantTable::findOrFail($station);

            if ($table->pos_mode !== $mode) {
                return response()->json(['success' => false, 'message' => 'Estación no pertenece a este modo'], 400);
            }

            $existingOrder = RestaurantOrder::where('table_id', $table->id)
                ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                ->first();

            if ($existingOrder) {
                return response()->json([
                    'success' => true,
                    'order_id' => $existingOrder->id,
                    'message' => 'Operación existente cargada',
                ]);
            }

            $order = RestaurantOrder::create([
                'company_id' => $table->company_id,
                'table_id' => $table->id,
                'user_id' => Auth::id(),
                'order_number' => RestaurantOrder::generateOrderNumber(),
                'status' => 'OPEN',
                'order_type' => 'pos_' . $mode,
            ]);

            $table->update(['status' => 'OCCUPIED']);

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function stations(Request $request, string $mode = 'venta')
    {
        $this->authorize('permission', 'view_pos');

        if (!in_array($mode, $this->modes)) {
            return response()->json(['success' => false, 'message' => 'Modo inválido'], 400);
        }

        $companyId = Company::getMainCompany()->id;

        $stations = RestaurantTable::where('company_id', $companyId)
            ->posMode($mode)
            ->orderBy('name')
            ->with(['orders' => function ($q) {
                $q->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                    ->with(['items' => fn($iq) => $iq->where('kitchen_status', '!=', 'CANCELLED')]);
            }])
            ->get();

        $list = $stations->map(function ($station) use ($mode) {
            $order = $station->orders->first();
            $items = $order ? $order->items : collect();
            $total = round((float) $items->sum('total'), 2);
            $status = !$order ? 'LIBRE' : ($order->status === 'SENT_TO_KITCHEN' ? 'POR_COBRAR' : 'PESANDO');

            return [
                'id' => $station->id,
                'name' => $station->name,
                'status' => $status,
                'order_id' => $order?->id,
                'order_number' => $order?->order_number,
                'items_count' => $items->count(),
                'total' => $total,
                'seller' => $order?->notes,
            ];
        });

        return response()->json(['success' => true, 'stations' => $list])
            ->header('Cache-Control', 'no-cache, must-revalidate, no-store, private')
            ->header('Pragma', 'no-cache');
    }

    public function getOrder(Request $request, $order)
    {
        $this->authorize('permission', 'view_pos');

        try {
            $order = RestaurantOrder::with(['items', 'table', 'user'])
                ->findOrFail($order);

            $activeItems = $order->items->where('kitchen_status', '!=', 'CANCELLED');

            return response()->json([
                'success' => true,
                'order' => $order,
                'order_status' => $order->status,
                'seller' => $order->notes,
                'items' => $activeItems->values(),
                'total' => (float) $order->total,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function addItem(Request $request, $order)
    {
        $this->authorize('permission', 'view_pos');

        try {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|numeric|min:0.0001',
                'price_level' => 'required|integer|between:1,4',
                'notes' => 'nullable|string|max:500',
            ]);

            $product = Product::findOrFail($validated['product_id']);
            $order = RestaurantOrder::findOrFail($order);
            $mode = $order->table?->pos_mode ?? 'venta';

            $this->assertOrderEditable($order);

            if ($mode === 'venta') {
                $this->assertSaleStock($order, $product, (float) $validated['quantity']);
            }

            $unitPrice = $mode === 'compra'
                ? $product->priceCompra((int) $validated['price_level'])
                : $product->priceVenta((int) $validated['price_level']);

            $existingItem = RestaurantOrderItem::where('restaurant_order_id', $order->id)
                ->where('product_id', $product->id)
                ->where('kitchen_status', 'PENDING')
                ->whereNull('paid_invoice_id')
                ->where('price_level', $validated['price_level'])
                ->where('notes', $validated['notes'] ?? null)
                ->first();

            if ($existingItem) {
                $existingItem->quantity += $validated['quantity'];
                $existingItem->total = round($existingItem->quantity * $existingItem->unit_price, 4);
                $existingItem->save();
                $item = $existingItem;
            } else {
                $item = RestaurantOrderItem::create([
                    'restaurant_order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->descripcion,
                    'quantity' => $validated['quantity'],
                    'unit_price' => $unitPrice,
                    'price_level' => $validated['price_level'],
                    'total' => round($unitPrice * $validated['quantity'], 4),
                    'kitchen_status' => 'PENDING',
                    'notes' => $validated['notes'] ?? null,
                    'print_destination' => 'productos',
                ]);
            }

            $this->updateOrderTotals($order);

            return response()->json([
                'success' => true,
                'item' => $item,
                'order_total' => (float) $order->fresh()->total,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function updateItem(Request $request, $item)
    {
        $this->authorize('permission', 'view_pos');

        $item = RestaurantOrderItem::findOrFail($item);

        $validated = $request->validate([
            'quantity' => 'nullable|numeric|min:0.0001',
            'quantity_delta' => 'nullable|numeric',
            'price_level' => 'nullable|integer|between:1,4',
            'notes' => 'nullable|string|max:500',
        ]);

        $order = $item->order;
        $mode = $order->table?->pos_mode ?? 'venta';

        $this->assertOrderEditable($order);

        if (isset($validated['quantity_delta'])) {
            $newQty = $item->quantity + $validated['quantity_delta'];
        } elseif (isset($validated['quantity'])) {
            $newQty = $validated['quantity'];
        } else {
            $newQty = $item->quantity;
        }
        $newQty = max(0.0001, (float) $newQty);

        if ($mode === 'venta' && $newQty > (float) $item->quantity) {
            $product = Product::find($item->product_id);
            if ($product) {
                $additional = $newQty - (float) $item->quantity;
                $this->assertSaleStock($order, $product, (float) $additional);
            }
        }

        $priceLevel = (int) ($validated['price_level'] ?? $item->price_level);

        if (isset($validated['quantity_delta'])) {
            $item->quantity = $newQty;
        } elseif (isset($validated['quantity'])) {
            $item->quantity = $newQty;
        }

        if (array_key_exists('notes', $validated)) {
            $item->notes = $validated['notes'];
        }

        if ((int) $priceLevel !== (int) $item->price_level) {
            $product = Product::find($item->product_id);
            if ($product) {
                $item->unit_price = $mode === 'compra'
                    ? $product->priceCompra((int) $priceLevel)
                    : $product->priceVenta((int) $priceLevel);
                $item->price_level = (int) $priceLevel;
            }
        }

        $item->total = round($item->quantity * $item->unit_price, 4);
        $item->save();

        $this->updateOrderTotals($item->order);

        return response()->json(['success' => true, 'item' => $item, 'order_total' => (float) $item->order->fresh()->total]);
    }

    public function removeItem(Request $request, $item)
    {
        $this->authorize('permission', 'view_pos');

        $item = RestaurantOrderItem::findOrFail($item);
        $order = $item->order;

        $this->assertOrderEditable($order);

        $item->delete();

        $this->updateOrderTotals($order);

        $activeItems = $order->items()->where('kitchen_status', '!=', 'CANCELLED')->count();
        if ($activeItems == 0) {
            $order->update(['status' => 'CANCELLED']);
            $order->table->update(['status' => 'AVAILABLE']);
        }

        return response()->json(['success' => true]);
    }

    public function deleteStation(Request $request, RestaurantTable $station)
    {
        $this->authorize('permission', 'view_pos');

        $mode = $request->get('mode', $station->pos_mode ?? 'venta');
        if (!in_array($mode, $this->modes)) {
            return response()->json(['success' => false, 'message' => 'Modo inválido'], 400);
        }

        try {
            $activeOrder = RestaurantOrder::where('table_id', $station->id)
                ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
                ->first();

            if (!$activeOrder) {
                return response()->json([
                    'success' => true,
                    'message' => 'La estación no tiene una operación activa para anular.'
                ]);
            }

            $hasInvoices = RestaurantOrderItem::where('restaurant_order_id', $activeOrder->id)
                ->whereNotNull('paid_invoice_id')
                ->exists();

            if ($hasInvoices) {
                return response()->json([
                    'success' => false,
                    'message' => 'La estación tiene productos ya cobrados (facturados). Cancélalos o elimínalos antes.'
                ], 400);
            }

            $sentItems = $activeOrder->items()
                ->whereIn('kitchen_status', ['SENT', 'READY', 'DELIVERED'])
                ->count();

            if ($sentItems > 0) {
                $adminPassword = $request->input('admin_password');
                if (!$adminPassword) {
                    return response()->json([
                        'success' => false,
                        'requires_admin' => true,
                        'message' => 'La operación fue enviada a caja. Ingresa la contraseña de administrador para anularla.'
                    ], 401);
                }

                $user = auth()->user();
                if (!$user || !$user->isAdmin() || !Hash::check($adminPassword, $user->password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contraseña de administrador incorrecta'
                    ], 403);
                }
            }

            foreach ($activeOrder->items as $item) {
                $item->update([
                    'kitchen_status' => 'CANCELLED',
                    'cancelled_from' => $item->kitchen_status === 'PENDING' ? 'OPEN' : $item->kitchen_status,
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                ]);
            }

            $activeOrder->update(['status' => 'CANCELLED']);

            $station->update(['status' => 'AVAILABLE']);

            return response()->json([
                'success' => true,
                'message' => 'Operación anulada. La estación quedó disponible.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular: ' . $e->getMessage()
            ], 500);
        }
    }

    public function printList(Request $request, $order)
    {
        $this->authorize('permission', 'view_pos');

        $order = RestaurantOrder::with(['items', 'table', 'user'])->findOrFail($order);
        $mode = $order->table?->pos_mode ?? 'venta';

        $order->setRelation('items', $order->items->where('kitchen_status', '!=', 'CANCELLED'));

        try {
            $printService = app(PrintService::class);
            $printService->printScrapOrder($order, $mode);
            return response()->json(['success' => true, 'message' => 'Lista enviada a imprimir']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function printPrecuenta(Request $request, $order)
    {
        $this->authorize('permission', 'view_pos');

        $order = RestaurantOrder::with(['items', 'table', 'user'])->findOrFail($order);
        $mode = $order->table?->pos_mode ?? 'venta';
        $order->setRelation('items', $order->items->where('kitchen_status', '!=', 'CANCELLED'));

        try {
            $printService = app(PrintService::class);
            $printService->printScrapPrebill($order, $mode);
            return response()->json(['success' => true, 'message' => 'Precuenta enviada a imprimir']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function setOrderCustomer(Request $request, $order)
    {
        $this->authorize('permission', 'view_pos');

        try {
            $validated = $request->validate([
                'customer_name' => 'nullable|string|max:255',
            ]);

            $order = RestaurantOrder::with(['table'])->findOrFail($order);
            $mode = $order->table?->pos_mode ?? 'venta';

            if ($order->status !== 'OPEN') {
                return response()->json([
                    'success' => false,
                    'message' => 'La operación ya fue enviada a caja. No se puede cambiar el cliente.'
                ], 400);
            }

            $name = trim($validated['customer_name'] ?? '');
            $order->update(['notes' => $name ?: null]);

            return response()->json([
                'success' => true,
                'seller' => $name,
                'message' => $name ? "Cliente establecido: {$name}" : 'Cliente eliminado',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function sendOrder(Request $request, $order)
    {
        $this->authorize('permission', 'view_pos');

        try {
            $validated = $request->validate([
                'seller_name' => 'nullable|string|max:255',
            ]);

            $order = RestaurantOrder::with(['items', 'table', 'user'])->findOrFail($order);
            $mode = $order->table?->pos_mode ?? 'venta';

            $this->assertOrderEditable($order);

            $pendingItems = $order->items->where('kitchen_status', 'PENDING')->whereNull('paid_invoice_id');
            if ($pendingItems->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No hay productos pendientes para enviar'], 400);
            }

            foreach ($pendingItems as $item) {
                $item->kitchen_status = 'SENT';
                $item->sent_to_kitchen_at = now();
                $item->save();
            }

            $seller = trim($validated['seller_name'] ?? '');
            $order->update([
                'status' => 'SENT_TO_KITCHEN',
                'notes' => $seller ?: $order->notes,
            ]);

            $order->setRelation('items', $order->items->where('kitchen_status', '!=', 'CANCELLED'));

            try {
                $printService = app(PrintService::class);
                $printService->printScrapOrder($order->fresh(['items', 'table', 'user']), $mode);
            } catch (\Exception $e) {
                \Log::error('Scrap send print error: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido enviado a caja' . ($seller ? " — Cliente: {$seller}" : ''),
                'order_status' => 'SENT_TO_KITCHEN',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function chargeOrder(Request $request, $order)
    {
        $this->authorize('permission', 'view_pos');

        try {
            $mainCompany = Company::getMainCompany();
            $companyId = $mainCompany->id;

            $cajaAbierta = CashRegister::where('company_id', $companyId)
                ->where('estado', 'ABIERTA')
                ->first();

            if (!$cajaAbierta) {
                return response()->json(['success' => false, 'message' => 'No hay caja abierta. Abra una caja antes de operar.'], 400);
            }

            $order = RestaurantOrder::with('items')->findOrFail($order);
            $mode = $order->table?->pos_mode ?? 'venta';

            if ($order->status !== 'SENT_TO_KITCHEN') {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido aún no fue enviado a caja. El pesador debe enviarlo antes de cobrar.'
                ], 400);
            }

            $items = $order->items
                ->where('kitchen_status', '!=', 'CANCELLED')
                ->whereNull('paid_invoice_id');

            if ($items->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'La operación no tiene productos para cobrar'], 400);
            }

            $customerId = $request->customer_id;
            $documentType = $mode === 'compra' ? 'CO' : ($request->document_type ?? 'NV');
            $payments = $request->payments ?? [['method' => 'EFECTIVO', 'amount' => round($items->sum('total'), 2)]];
            $reference = $request->reference ?: ($order->notes ?? '');

            $result = $this->createInvoiceFromItems(
                $order, $items, $customerId, $documentType, $payments,
                $reference, $cajaAbierta, $mainCompany, $companyId, $mode
            );
            $invoice = $result['invoice'];

            RestaurantOrderItem::whereIn('id', $items->pluck('id'))
                ->update(['paid_invoice_id' => $invoice->id]);

            $order->status = 'COMPLETED';
            $order->save();
            $order->table->update(['status' => 'AVAILABLE']);

            return response()->json([
                'success' => true,
                'invoice_id' => $invoice->id,
                'full_number' => $result['full_number'],
                'total' => $result['total'],
                'document_type' => $result['document_type'],
                'vuelto' => $result['vuelto'],
                'mode' => $mode,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function printThermal(Invoice $invoice)
    {
        $this->authorize('permission', 'view_pos');

        $invoice->load(['items', 'customer']);

        try {
            $printService = app(PrintService::class);
            $printService->printScrapInvoice($invoice);
            return response()->json(['success' => true, 'message' => 'Comprobante enviado a impresora Caja']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al imprimir: ' . $e->getMessage()], 500);
        }
    }

    public function printCompra(Invoice $invoice, string $format = '80mm')
    {
        $this->authorize('permission', 'view_pos');

        $invoice->load(['items', 'customer']);
        $company = \App\Models\Company::getMainCompany();

        if (!$company) {
            abort(400, 'No hay empresa principal configurada');
        }

        $view = $format === 'A4' ? 'scrap_pos.compra-a4' : 'scrap_pos.ticket-compra';

        if ($format === 'A4') {
            $pdf = Pdf::loadView($view, compact('invoice', 'company'))
                ->setPaper('a4', 'portrait');
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="nota-compra-' . $invoice->full_number . '.pdf"');
        }

        $pdf = Pdf::loadView($view, compact('invoice', 'company'))
            ->setPaper([0, 0, 226.77, 800], 'portrait')
            ->setOption('margin-top', 2)
            ->setOption('margin-right', 2)
            ->setOption('margin-bottom', 2)
            ->setOption('margin-left', 2);
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="nota-compra-' . $invoice->full_number . '.pdf"');
    }

    private function createInvoiceFromItems($order, $items, $customerId, $documentType, $payments, $reference, $cajaAbierta, $mainCompany, $companyId, $mode)
    {
        $serie = Serie::where('company_id', $companyId)
            ->where('tipo_documento', $documentType)
            ->where('estado', 'ACTIVO')
            ->first();

        if (!$serie) {
            $serieName = match ($documentType) {
                'NV' => 'NV01',
                '01' => 'F001',
                '03' => 'B001',
                'CO' => 'COM',
                default => 'COM',
            };
            $serie = Serie::create([
                'company_id' => $companyId,
                'tipo_documento' => $documentType,
                'serie' => $serieName,
                'numero_actual' => 0,
                'estado' => 'ACTIVO',
            ]);
        }

        if ($mode === 'compra') {
            $customer = null;
            if ($customerId) {
                $customer = Customer::find($customerId);
            }
            if (!$customer) {
                $customer = Customer::firstOrCreate(
                    ['company_id' => $companyId, 'documento_numero' => '88888888'],
                    [
                        'company_id' => $companyId,
                        'documento_tipo' => '1',
                        'documento_numero' => '88888888',
                        'nombre' => 'CLIENTES VARIOS',
                        'estado' => 'ACTIVO',
                    ]
                );
            }
        } else {
            $customer = $customerId ? Customer::find($customerId) : null;
        }

        $nextNumber = $serie->getNextNumber();
        $total = round($items->sum('total'), 2);

        if (empty($payments) || collect($payments)->sum('amount') <= 0) {
            $payments = [['method' => 'EFECTIVO', 'amount' => $total]];
        }

        $igvRate = $mainCompany ? $mainCompany->getIgvRate() : 0.18;
        $subtotal = $total / (1 + $igvRate);
        $igv = $total - $subtotal;

        $invoice = Invoice::create([
            'company_id' => $companyId,
            'customer_id' => $customer?->id,
            'tipo_documento' => $documentType,
            'serie' => $serie->serie,
            'numero' => $nextNumber,
            'full_number' => $serie->serie . '-' . str_pad($nextNumber, 8, '0', STR_PAD_LEFT),
            'fecha_emision' => now()->format('Y-m-d'),
            'hora_emision' => now()->format('H:i:s'),
            'fecha_vencimiento' => now()->format('Y-m-d'),
            'moneda' => 'PEN',
            'gravado' => round($subtotal, 2),
            'igv' => round($igv, 2),
            'total' => $total,
            'subtotal' => round($subtotal, 2),
            'total_letras' => $this->numberToWords($total) . ' SOLES',
            'metodo_pago' => collect($payments)->map(fn($p) => $p['method'] . '/' . $p['amount'])->implode(' + '),
            'referencia_pago' => $reference ?: ($order->table->name ?? ''),
            'sunat_estado' => $mode === 'compra' ? 'NO_ENVIADO' : 'PENDIENTE',
            'order_source' => 'pos_' . $mode,
        ]);

        $productIds = $items->pluck('product_id')->filter()->toArray();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            $unitBase = $item->unit_price / (1 + $igvRate);
            $itemIgv = $item->unit_price - $unitBase;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $item->product_id,
                'codigo' => $product?->codigo ?? '',
                'descripcion' => $item->product_name,
                'cantidad' => $item->quantity,
                'umedida' => $product?->umedida_codigo ?? 'NIU',
                'precio_unitario' => round($unitBase, 4),
                'precio_venta' => round($item->unit_price * $item->quantity, 4),
                'igv' => round($itemIgv, 2),
                'tipo_afectacion' => $product?->tipo_afectacion === 'EXO' ? '20' : '10',
                'igv_percent' => round($igvRate * 100, 2),
            ]);

            if ($product) {
                if ($mode === 'compra') {
                    $product->increment('stock', $item->quantity);
                } elseif ($product->is_composite) {
                    foreach ($product->components as $component) {
                        $componentProduct = $component->component;
                        if ($componentProduct) {
                            $componentProduct->decrement('stock', $component->quantity * $item->quantity);
                        }
                    }
                } else {
                    $product->decrement('stock', $item->quantity);
                }
            }
        }

        $serie->increment('numero_actual');
        $fullNumber = $serie->serie . '-' . str_pad($nextNumber, 8, '0', STR_PAD_LEFT);

        if ($mode === 'compra') {
            $cajaAbierta->cantidad_compras = ($cajaAbierta->cantidad_compras ?? 0) + 1;
            $cajaAbierta->total_compras = ($cajaAbierta->total_compras ?? 0) + $total;
            foreach ($payments as $payment) {
                $paymentField = match ($payment['method']) {
                    'EFECTIVO' => 'compras_efectivo',
                    'TARJETA' => 'compras_tarjeta',
                    'YAPE' => 'compras_yape',
                    'PLIN' => 'compras_plin',
                    default => 'compras_otro',
                };
                $cobrado = min((float) ($payment['amount'] ?? 0), $total);
                $cajaAbierta->$paymentField = ($cajaAbierta->$paymentField ?? 0) + $cobrado;
            }
        } else {
            $cajaAbierta->cantidad_ventas = ($cajaAbierta->cantidad_ventas ?? 0) + 1;
            $cajaAbierta->total_ventas = ($cajaAbierta->total_ventas ?? 0) + $total;
            foreach ($payments as $payment) {
                $paymentField = match ($payment['method']) {
                    'EFECTIVO' => 'ventas_efectivo',
                    'TARJETA' => 'ventas_tarjeta',
                    'YAPE' => 'ventas_yape',
                    'PLIN' => 'ventas_plin',
                    default => 'ventas_otro',
                };
                $cobrado = min((float) ($payment['amount'] ?? 0), $total);
                $cajaAbierta->$paymentField = ($cajaAbierta->$paymentField ?? 0) + $cobrado;
            }
        }
        $cajaAbierta->save();

        $totalPagado = collect($payments)->sum('amount');
        $vuelto = max(0, $totalPagado - $total);

        return [
            'invoice' => $invoice,
            'full_number' => $fullNumber,
            'total' => $total,
            'document_type' => $documentType,
            'vuelto' => $vuelto,
        ];
    }

    private function updateOrderTotals(RestaurantOrder $order)
    {
        $order->load('items');
        $items = $order->items
            ->where('kitchen_status', '!=', 'CANCELLED')
            ->whereNull('paid_invoice_id');
        $company = Company::find($order->company_id);
        $igvRate = $company ? $company->getIgvRate() : 0.18;

        $subtotal = $items->sum('total') / (1 + $igvRate);
        $igv = $items->sum('total') - $subtotal;
        $total = $items->sum('total');

        $order->update([
            'subtotal' => round($subtotal, 2),
            'igv' => round($igv, 2),
            'total' => round($total, 2),
        ]);
    }

    private function ensureStations(int $companyId, string $mode): void
    {
        $exists = RestaurantTable::where('company_id', $companyId)->posMode($mode)->exists();
        if ($exists) {
            return;
        }

        $label = $mode === 'compra' ? 'Compra' : 'Venta';
        $floorId = \App\Models\Floor::where('company_id', $companyId)->where('name', $label)->value('id');
        if (!$floorId) {
            $floorId = \App\Models\Floor::create([
                'company_id' => $companyId,
                'name' => $label,
                'order' => $mode === 'venta' ? 1 : 2,
                'status' => 'ACTIVE',
            ])->id;
        }

        for ($i = 1; $i <= 5; $i++) {
            RestaurantTable::create([
                'company_id' => $companyId,
                'floor_id' => $floorId,
                'name' => $label . ' ' . $i,
                'capacity' => 1,
                'status' => 'AVAILABLE',
                'is_for_kiosko' => false,
                'pos_mode' => $mode,
            ]);
        }
    }

    private function assertOrderEditable(RestaurantOrder $order): void
    {
        if ($order->status !== 'OPEN') {
            throw new \RuntimeException('El pedido ya fue enviado a caja y no se puede modificar. Solo el cajero puede cobrarlo.');
        }
    }

    private function assertSaleStock(RestaurantOrder $order, Product $product, float $quantity): void
    {
        $inOrder = (float) $order->items()
            ->where('product_id', $product->id)
            ->where('kitchen_status', '!=', 'CANCELLED')
            ->sum('quantity');

        $totalNeeded = $inOrder + $quantity;

        if ($product->is_composite) {
            foreach ($product->components as $component) {
                $componentProduct = $component->component;
                if (!$componentProduct) continue;
                $needed = (float) $component->quantity * $totalNeeded;
                $available = (float) $componentProduct->stock;
                if ($needed > $available + 0.0001) {
                    throw new \RuntimeException(
                        "Stock insuficiente. El compuesto '{$product->descripcion}' requiere {$this->fmtQty($needed)} de '{$componentProduct->descripcion}' y solo hay {$this->fmtQty($available)}."
                    );
                }
            }
            return;
        }

        $available = (float) $product->stock;
        if ($totalNeeded > $available + 0.0001) {
            throw new \RuntimeException(
                "Stock insuficiente de '{$product->descripcion}'. Disponible: {$this->fmtQty($available)} — Solicitado: {$this->fmtQty($totalNeeded)}. Compre chatarra primero o cargue stock."
            );
        }
    }

    private function fmtQty(float $qty): string
    {
        return $qty == intval($qty) ? (string) (int) $qty : rtrim(number_format($qty, 3, '.', ''), '0');
    }

    private function numberToWords($number)
    {
        $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
        return ucfirst($formatter->format($number));
    }
}
