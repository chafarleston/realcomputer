@extends('layouts.admin')
@section('title', 'POS ' . ($mode === 'compra' ? 'Compra' : 'Venta'))
@section('page_title', 'POS ' . ($mode === 'compra' ? 'Compra' : 'Venta'))

@push('styles')
<style>
    body { overflow: hidden; }
    .main-footer, .content-header { display: none !important; }
    .content-wrapper { padding-top: 0 !important; }

    .pos-container {
        height: calc(100vh - 60px);
        width: 100%;
        padding: 10px;
        box-sizing: border-box;
    }

    .pos-shell {
        height: 100%;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .pos-header {
        padding: 12px 15px;
        border-bottom: 2px solid #eee;
        flex-shrink: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .pos-header h4 { margin: 0; font-size: 16px; }
    .pos-header small { font-size: 11px; color: #666; }

    .stations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 14px;
        padding: 18px;
        overflow-y: auto;
        flex: 1;
        align-content: start;
    }

    .station-card {
        background: #fff;
        border: 3px solid #28a745;
        border-radius: 12px;
        padding: 18px 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        min-height: 120px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        position: relative;
    }
    .station-card:hover { transform: scale(1.03); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .station-card.occupied { border-color: #dc3545; background: #fff5f5; }
    .station-card.pesando { border-color: #007bff; background: #e7f1ff; }
    .station-card.por-cobrar { border-color: #fd7e14; background: #fff3e6; }
    .station-card i { font-size: 30px; margin-bottom: 8px; color: #28a745; }
    .station-card.occupied i { color: #dc3545; }
    .station-card.pesando i { color: #007bff; }
    .station-card.por-cobrar i { color: #fd7e14; }
    .station-delete-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 24px;
        height: 24px;
        border: none;
        border-radius: 50%;
        background: transparent;
        color: #999;
        font-size: 14px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
    }
    .station-delete-btn:hover { background: #dc3545; color: #fff; }
    .station-name { font-weight: bold; font-size: 15px; }
    .station-status { font-size: 11px; color: #666; margin-top: 4px; font-weight: bold; }
    .station-seller { font-size: 10px; margin-top: 4px; padding: 2px 8px; border-radius: 10px; background: #fd7e14; color: white; }
    .station-order { font-size: 10px; margin-top: 6px; padding: 3px 8px; border-radius: 10px; background: #007bff; color: white; }
    .station-card.por-cobrar .station-order { background: #fd7e14; }

    /* Full screen order modal */
    .pos-modal {
        display: none;
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: #f4f6f9;
        z-index: 9999;
        flex-direction: column;
    }
    .pos-modal.show { display: flex; }

    .modal-bar {
        background: #fff;
        padding: 10px 15px;
        border-bottom: 2px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
        flex-wrap: wrap;
        gap: 8px;
    }
    .modal-bar h4 { margin: 0; font-size: 15px; }

    .order-body {
        display: flex;
        flex: 1;
        min-height: 0;
        flex-direction: column;
    }

    .items-area { flex: 1; overflow-y: auto; padding: 12px; }

    .order-item {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }
    .order-item .item-info { flex: 1; min-width: 150px; }
    .order-item .item-name { font-weight: bold; font-size: 13px; }
    .order-item .item-detail { font-size: 11px; color: #666; }
    .order-item .item-total { font-weight: bold; font-size: 14px; }

    .qty-controls { display: inline-flex; align-items: center; gap: 4px; }
    .qty-controls button {
        width: 26px; height: 26px;
        border: 1px solid #ccc;
        border-radius: 6px;
        background: #fff;
        font-weight: bold;
        cursor: pointer;
    }

    .footer-bar {
        background: #fff;
        border-top: 2px solid #ddd;
        padding: 12px 15px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }

    .action-btn { min-width: 130px; }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
        padding: 12px;
        overflow-y: auto;
        flex: 1;
        align-content: start;
    }
    .product-card {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        padding: 12px;
        text-align: center;
        cursor: pointer;
        transition: all 0.15s;
    }
    .product-card:hover { border-color: #007bff; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
    .product-card .p-name { font-size: 12px; font-weight: bold; min-height: 32px; }
    .product-card .p-price { font-size: 12px; color: #28a745; font-weight: bold; }

    .price-level-chip {
        border: 2px solid #ddd;
        border-radius: 10px;
        padding: 10px 6px;
        text-align: center;
        cursor: pointer;
        transition: all 0.15s;
        background: #fff;
    }
    .price-level-chip.active { border-color: #007bff; background: #e7f1ff; }
    .price-level-chip .pl-nivel { font-size: 11px; color: #666; }
    .price-level-chip .pl-precio { font-size: 13px; font-weight: bold; }

    .modal { z-index: 20000 !important; }
    .modal-backdrop.show { z-index: 19999 !important; }
</style>
@endpush

@section('content')
<div class="pos-container">
    <div class="pos-shell">
        <div class="pos-header">
            <div>
                <h4><i class="fas fa-{{ $mode === 'compra' ? 'cart-arrow-down' : 'cash-register' }}"></i>
                    POS {{ $mode === 'compra' ? 'Compra' : 'Venta' }}</h4>
                <small>Caja abierta: S/ {{ number_format($cajaAbierta->monto_apertura, 2) }} · Apertura {{ $cajaAbierta->fecha_apertura ? $cajaAbierta->fecha_apertura->format('d/m H:i') : '' }}</small>
            </div>
            <div>
                <a href="{{ $mode === 'compra' ? route('scrap-pos.venta') : route('scrap-pos.compra') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-swap"></i> Ir a POS {{ $mode === 'compra' ? 'Venta' : 'Compra' }}
                </a>
                <a href="{{ route('cashregisters.index') }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-cash-register"></i> Caja</a>
            </div>
        </div>

        <div class="stations-grid" id="stationsGrid">
            @forelse($stations as $station)
                @php
                    $activeOrder = $station->orders->first();
                    $scrapStatus = !$activeOrder ? 'LIBRE' : ($activeOrder->status === 'SENT_TO_KITCHEN' ? 'POR_COBRAR' : 'PESANDO');
                    $scrapTotal = $activeOrder ? round((float) $activeOrder->items->sum('total'), 2) : 0;
                    $scrapItems = $activeOrder ? $activeOrder->items->count() : 0;
                @endphp
                <div class="station-card {{ $scrapStatus === 'POR_COBRAR' ? 'por-cobrar' : ($scrapStatus === 'PESANDO' ? 'pesando' : '') }}"
                     data-station-id="{{ $station->id }}" data-station-name="{{ $station->name }}"
                     data-status="{{ $scrapStatus }}" data-order-id="{{ $activeOrder?->id }}"
                     data-items-count="{{ $scrapItems }}" data-total="{{ $scrapTotal }}"
                     onclick="openStation({{ $station->id }}, '{{ $station->name }}')">
                    <button type="button" class="station-delete-btn" title="Anular operación y liberar estación"
                            onclick="event.stopPropagation(); confirmDeleteStation({{ $station->id }}, '{{ $station->name }}', '{{ $mode }}')">
                        <i class="fas fa-times"></i>
                    </button>
                    <i class="fas fa-{{ $mode === 'compra' ? 'cart-arrow-down' : 'receipt' }}"></i>
                    <div class="station-name">{{ $station->name }}</div>
                    <div class="station-status">{{ $scrapStatus === 'LIBRE' ? 'LIBRE' : ($scrapStatus === 'POR_COBRAR' ? 'POR COBRAR' : 'PESANDO') }}</div>
                    @if($activeOrder)
                        <div class="station-order">{{ $scrapItems }} item(s) · S/ {{ number_format($scrapTotal, 2) }}</div>
                        @if($scrapStatus === 'POR_COBRAR' && $activeOrder->notes)
                            <div class="station-seller">{{ $activeOrder->notes }}</div>
                        @endif
                    @endif
                </div>
            @empty
                <div class="col-12 text-center text-muted py-5">No hay estaciones configuradas. Ejecute el seeder ScrapSetupSeeder.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- Order modal --}}
<div class="pos-modal" id="orderModal">
    <div class="modal-bar">
        <h4><i class="fas fa-receipt"></i> <span id="stationName">Estación</span></h4>
        <div>
            <button id="btnSendOrder" class="btn btn-primary btn-sm action-btn" onclick="showSendModal()"><i class="fas fa-paper-plane"></i> Enviar a Caja</button>
            <button class="btn btn-info btn-sm action-btn" onclick="printList()"><i class="fas fa-print"></i> Imprimir Lista</button>
            <button class="btn btn-warning btn-sm action-btn" onclick="printPrecuenta()"><i class="fas fa-file-invoice"></i> Precuenta</button>
            <button id="btnCharge" class="btn btn-success btn-sm action-btn" onclick="showChargeModal()"><i class="fas fa-hand-holding-usd"></i> {{ $mode === 'compra' ? 'Pagar Compra' : 'Cobrar' }}</button>
            <button class="btn btn-secondary btn-sm" onclick="closeOrderModal()"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div class="order-body" id="orderBody">
        <div id="sentBanner" class="alert alert-warning text-center m-0 rounded-0" style="display:none;">
            <i class="fas fa-paper-plane"></i> <strong>ENVIADO A CAJA — PENDIENTE DE PAGO</strong> <span id="sentSeller"></span>
        </div>
        <div class="d-flex justify-content-between align-items-center px-3 pt-3">
            <button id="btnAddProduct" class="btn btn-primary" onclick="showProductPicker()"><i class="fas fa-plus"></i> Agregar Producto</button>
            <span id="orderTotalLabel" class="font-weight-bold" style="font-size:18px;">S/ 0.00</span>
        </div>
        <div class="items-area" id="itemsArea">
            <div class="text-center text-muted py-5">Sin productos todavía. Agregue productos o materiales a la operación.</div>
        </div>
    </div>
    <div class="footer-bar">
        <small id="orderNumberLabel"></small>
    </div>
</div>

{{-- Product picker modal (full screen overlay) --}}
<div class="pos-modal" id="productPickerModal">
    <div class="modal-bar">
        <h4><i class="fas fa-boxes"></i> Seleccionar Producto / Material</h4>
        <div>
            <input type="text" id="productSearch" class="form-control form-control-sm" style="width:220px;" placeholder="Buscar por nombre...">
            <button class="btn btn-secondary btn-sm" onclick="closeProductPicker()"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2 px-3 pt-2" style="gap:6px;" id="categoryFilters">
        <button class="btn btn-sm btn-primary" data-cat="" onclick="filterCategory('')">Todos</button>
        @foreach($categories as $cat)
            <button class="btn btn-sm btn-outline-secondary" data-cat="{{ $cat->id }}" onclick="filterCategory('{{ $cat->id }}')">{{ $cat->nombre }}</button>
        @endforeach
    </div>
    <div class="product-grid" id="productGrid">
        @foreach($products as $product)
            <div class="product-card" data-name="{{ strtolower($product->descripcion) }}" data-cat="{{ $product->category_id ?? '' }}"
                 data-id="{{ $product->id }}" data-desc="{{ $product->descripcion }}"
                 data-stock="{{ $product->stock }}"
                 data-v1="{{ $product->precio }}" data-v2="{{ $product->precio_venta_n2 }}" data-v3="{{ $product->precio_venta_n3 }}" data-v4="{{ $product->precio_venta_n4 }}"
                 data-c1="{{ $product->precio_compra }}" data-c2="{{ $product->precio_compra_n2 }}" data-c3="{{ $product->precio_compra_n3 }}" data-c4="{{ $product->precio_compra_n4 }}"
                 onclick="selectProduct(this)">
                <div class="p-name">{{ $product->descripcion }}</div>
                @if($mode === 'compra')
                    <div class="p-price">S/ {{ number_format($product->precio_compra, 3) }}</div>
                @else
                    <div class="p-price">S/ {{ number_format($product->precio, 3) }}</div>
                    <div class="p-stock">Stock: {{ number_format($product->stock, 3) }}</div>
                @endif
            </div>
        @endforeach
    </div>
</div>

{{-- Product quantity modal --}}
<div class="pos-modal" id="productQtyModal">
    <div class="modal-bar">
        <h4><i class="fas fa-shopping-cart"></i> <span id="qtyProductName">Producto</span></h4>
        <button class="btn btn-secondary btn-sm" onclick="closeQtyModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="p-4" style="background:#fff; flex:1; overflow-y:auto;">
        <div class="row">
            @for($n = 1; $n <= 4; $n++)
                <div class="col-md-3 col-6 mb-2">
                    <div class="price-level-chip" data-level="{{ $n }}" onclick="selectPriceLevel({{ $n }})">
                        <div class="pl-nivel">Nivel {{ $n }}</div>
                        <div class="pl-precio">S/ <span class="pl-value">0.0000</span></div>
                    </div>
                </div>
            @endfor
        </div>

        <div class="form-group">
            <label>Cantidad {{ $mode === 'compra' ? 'comprada' : 'vendida' }} (unidades o kilos)</label>
            <div class="input-group">
                <input type="number" id="qtyInput" class="form-control" step="0.0001" min="0.0001" value="1">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" onclick="changeQty(-1)"><i class="fas fa-minus"></i></button>
                    <button class="btn btn-outline-secondary" onclick="changeQty(1)"><i class="fas fa-plus"></i></button>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Notas (opcional)</label>
            <input type="text" id="qtyNotes" class="form-control" placeholder="Ej: tubos de 3/4, fierro de construcción...">
        </div>

        <div class="alert alert-success text-center" id="qtyPreview" style="font-size:18px;">
            Total: S/ 0.0000
        </div>
        <div class="alert alert-danger text-center" id="qtyError" style="display:none; font-size:14px;"></div>

        <button class="btn btn-primary btn-block btn-lg" onclick="confirmAddItem()">
            <i class="fas fa-check"></i> Agregar a la {{ $mode === 'compra' ? 'compra' : 'venta' }}
        </button>
    </div>
</div>

{{-- Send modal --}}
<div class="modal fade" id="sendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-paper-plane"></i> Enviar a Caja</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Al enviar se imprimirá la lista de productos y la operación quedará <strong>bloqueada</strong> hasta que el cajero la cobre.</p>
                <div class="form-group">
                    <label>Cliente / Vendedor <span class="text-danger">*</span></label>
                    <input type="text" id="sendSellerName" class="form-control" placeholder="Ej: Mario" required>
                </div>
                <div class="alert alert-info mb-0" id="sendPreview"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmSend()"><i class="fas fa-paper-plane"></i> Enviar e Imprimir</button>
            </div>
        </div>
    </div>
</div>

{{-- Charge modal --}}
<div class="modal fade" id="chargeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $mode === 'compra' ? 'Registrar Pago de Compra' : 'Cobrar' }}</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Cliente / Proveedor</label>
                    <select id="chargeCustomer" class="form-control">
                        <option value="">@if($mode === 'compra') Vendedor de chatarra (Clientes Varios) @else Clientes Varios @endif</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->nombre }} ({{ $customer->documento_numero }})</option>
                        @endforeach
                    </select>
                </div>
                @if($mode !== 'compra')
                <div class="form-group">
                    <label>Tipo de documento</label>
                    <select id="chargeDocType" class="form-control">
                        <option value="NV">Nota de Venta</option>
                        <option value="01">Factura</option>
                        <option value="03">Boleta</option>
                    </select>
                </div>
                @endif
                <div class="form-group">
                    <label>Método de pago</label>
                    <select id="chargeMethod" class="form-control">
                        <option value="EFECTIVO">Efectivo</option>
                        <option value="TARJETA">Tarjeta</option>
                        <option value="YAPE">Yape</option>
                        <option value="PLIN">Plin</option>
                        <option value="OTRO">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Monto recibido</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                        <input type="number" id="chargeAmount" class="form-control" step="0.01" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label>Referencia (opcional)</label>
                    <input type="text" id="chargeReference" class="form-control" placeholder="Ej: Lote, cliente, fecha...">
                </div>
                <div class="alert alert-info" id="chargeVuelto">Vuelto: S/ 0.00</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="confirmCharge()"><i class="fas fa-check"></i> Confirmar</button>
            </div>
        </div>
    </div>
</div>

{{-- Success modal --}}
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Operación Registrada</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-check-circle text-success" style="font-size:48px;"></i>
                <h4 id="successNumber" class="mt-2"></h4>
                <p id="successTotal"></p>
                <div class="btn-group-vertical w-100 mt-3">
                    @if($mode === 'compra')
                        <button class="btn btn-primary btn-block" id="btnPrintCompra80" onclick="printCompra(getLastInvoiceId(), '80mm')" disabled>
                            <i class="fas fa-print"></i> Imprimir Nota de Compra (80mm)
                        </button>
                        <button class="btn btn-outline-primary btn-block" id="btnPrintCompraA4" onclick="printCompra(getLastInvoiceId(), 'A4')" disabled>
                            <i class="fas fa-file-alt"></i> Imprimir Nota de Compra (A4)
                        </button>
                    @else
                        <button class="btn btn-primary btn-block" id="btnPrintVenta80" onclick="printVenta(getLastInvoiceId(), '80mm')" disabled>
                            <i class="fas fa-print"></i> Imprimir (80mm)
                        </button>
                        <button class="btn btn-outline-primary btn-block" id="btnPrintVentaA4" onclick="printVenta(getLastInvoiceId(), 'A4')" disabled>
                            <i class="fas fa-file-alt"></i> Imprimir (A4)
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Cancel station modal --}}
<div class="modal fade" id="cancelStationModal" tabindex="-1" aria-labelledby="cancelStationModalLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header border-0" style="background:linear-gradient(135deg,#f39c12,#e67e22);">
                <h5 class="modal-title text-white" id="cancelStationModalLabel"><i class="fas fa-exclamation-triangle"></i> Anular Operación</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center pt-4">
                <div class="animate-icon mb-3">
                    <div class="icon-circle bg-danger-soft">
                        <i class="fas fa-times fa-2x text-danger"></i>
                    </div>
                </div>
                <h4 class="mb-1" style="font-weight:600;">¿Anular la operación?</h4>
                <p class="text-muted mb-3">
                    Se eliminarán todos los productos de la estación
                    <strong class="text-dark" id="cancelStationName"></strong>
                    y quedará disponible para una nueva operación.
                </p>
                <div class="text-left d-inline-block text-muted small" style="text-align:left !important;">
                    <div><i class="fas fa-box-open text-warning mr-2"></i>Se eliminan los productos añadidos</div>
                    <div><i class="fas fa-id-card text-warning mr-2"></i>La estación queda <b>Libre</b></div>
                    <div><i class="fas fa-history text-warning mr-2"></i>Se registra el historial de anulación</div>
                </div>
                <div id="cancelAdminWrap" class="mt-3 text-left" style="display:none;">
                    <div class="alert alert-danger py-2">
                        <i class="fas fa-lock mr-1"></i> Esta operación fue enviada a caja. Ingresa la contraseña de administrador para anularla.
                    </div>
                    <input type="password" id="cancelAdminPassword" class="form-control" placeholder="Contraseña de administrador" autocomplete="off">
                </div>
                <div id="cancelStationError" class="alert alert-danger py-2 mt-2 mb-0 text-left" style="display:none;"></div>
            </div>
            <div class="modal-footer justify-content-center border-0 pb-4">
                <button type="button" class="btn btn-lg btn-light px-4" data-dismiss="modal" style="border-radius:30px;"><i class="fas fa-times mr-1"></i> Cancelar</button>
                <button type="button" class="btn btn-lg btn-danger px-4" id="btnConfirmCancelStation" onclick="doCancelStation()" style="border-radius:30px;">
                    <i class="fas fa-ban mr-1"></i> Anular operación
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Station cancelled success modal --}}
<div class="modal fade" id="stationCancelledModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:14px; overflow:hidden; border:2px solid #28a745;">
            <div class="modal-body text-center pt-4 pb-4">
                <div class="icon-circle mx-auto mb-3" style="width:70px; height:70px; border-radius:50%; background:rgba(40,167,69,0.12); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-check-circle text-success" style="font-size:40px;"></i>
                </div>
                <h4 class="mb-1" style="font-weight:600; color:#28a745;">Operación Anulada</h4>
                <p class="text-muted mb-0">La estación <strong id="cancelledStationName"></strong> quedó disponible para nuevas operaciones.</p>
            </div>
            <div class="modal-footer justify-content-center border-0 pb-4 pt-0">
                <button type="button" class="btn btn-success btn-lg px-5" data-dismiss="modal" style="border-radius:30px;"><i class="fas fa-check mr-1"></i> Entendido</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const MODE = '{{ $mode }}';
    const CSRF = '{{ csrf_token() }}';
    const BASE = '{{ url('/') }}';
    let currentOrderId = null;
    let currentStationName = '';
    let currentOrderStatus = 'OPEN';
    let currentSeller = '';
    let selectedProduct = null;
    let selectedPriceLevel = 1;
    let lastInvoiceId = null;
    let cancelStationId = null;
    let cancelStationMode = 'venta';
    let cancelNeedsAdmin = false;

    function getLastInvoiceId() { return lastInvoiceId; }

    function fetchJson(url, options = {}) {
        options.headers = Object.assign({
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': CSRF,
        }, options.headers || {});
        return fetch(url, options).then(r => r.json());
    }

    function showError(msg) {
        alert(msg || 'Ocurrió un error');
    }

    function openStation(stationId, name) {
        currentStationName = name;
        fetchJson(BASE + '/scrap-pos/' + MODE + '/open/' + stationId, { method: 'POST' })
            .then(data => {
                if (!data.success) throw new Error(data.message);
                currentOrderId = data.order_id;
                loadOrder();
            })
            .catch(err => showError(err.message));
    }

    function confirmDeleteStation(stationId, name, mode) {
        cancelStationId = stationId;
        cancelStationMode = mode;
        cancelNeedsAdmin = false;
        document.getElementById('cancelStationName').textContent = name;
        document.getElementById('cancelAdminWrap').style.display = 'none';
        document.getElementById('cancelAdminPassword').value = '';
        hideCancelError();
        $('#cancelStationModal').modal('show');
    }

    function hideCancelError() {
        document.getElementById('cancelStationError').style.display = 'none';
    }

    function showCancelError(msg) {
        const el = document.getElementById('cancelStationError');
        el.textContent = msg;
        el.style.display = 'block';
    }

    function doCancelStation() {
        const btn = document.getElementById('btnConfirmCancelStation');
        const password = cancelNeedsAdmin ? document.getElementById('cancelAdminPassword').value : null;
        hideCancelError();

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Anulando...';

        fetchJson(BASE + '/scrap-pos/stations/' + cancelStationId + '?mode=' + cancelStationMode, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_password: password || '' }),
        })
            .then(data => {
                if (!data.success && data.requires_admin) {
                    cancelNeedsAdmin = true;
                    document.getElementById('cancelAdminWrap').style.display = '';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-ban"></i> Anular operación';
                    return;
                }
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-ban"></i> Anular operación';
                if (!data.success) throw new Error(data.message);
                $('#cancelStationModal').modal('hide');
                if (currentOrderId) {
                    $('#orderModal').removeClass('show');
                    currentOrderId = null;
                }
                pollStations();
                document.getElementById('cancelledStationName').textContent = document.getElementById('cancelStationName').textContent;
                $('#stationCancelledModal').modal('show');
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-ban"></i> Anular operación';
                showCancelError(err.message);
            });
    }

    function loadOrder() {
        fetchJson(BASE + '/scrap-pos/orders/' + currentOrderId)
            .then(data => {
                if (!data.success) throw new Error(data.message);
                currentOrderStatus = data.order_status || 'OPEN';
                currentSeller = data.seller || '';
                renderOrder(data.order, data.items, data.total, currentOrderStatus, currentSeller);
            })
            .catch(err => showError(err.message));
    }

    function renderOrder(order, items, total, orderStatus, seller) {
        currentOrderStatus = orderStatus || 'OPEN';
        currentSeller = seller || '';
        $('#stationName').text(currentStationName + ' · ' + (order.order_number || ''));
        $('#orderNumberLabel').text('N° ' + (order.order_number || ''));
        $('#orderTotalLabel').text('S/ ' + Number(total).toFixed(2));

        const sent = orderStatus === 'SENT_TO_KITCHEN';
        document.getElementById('sentBanner').style.display = sent ? 'block' : 'none';
        document.getElementById('sentSeller').textContent = sent && currentSeller ? '· ' + currentSeller : '';
        document.getElementById('btnSendOrder').style.display = sent ? 'none' : '';
        document.getElementById('btnCharge').style.display = sent ? '' : 'none';
        document.getElementById('btnAddProduct').style.display = sent ? 'none' : '';

        const area = document.getElementById('itemsArea');
        if (!items || items.length === 0) {
            area.innerHTML = '<div class="text-center text-muted py-5">Sin productos todavía. Agregue productos o materiales a la operación.</div>';
        } else {
            area.innerHTML = items.map(item => {
                const qty = Number(item.quantity);
                const qtyStr = qty === Math.floor(qty) ? qty : qty.toFixed(3).replace(/0+$/, '').replace(/\.$/, '');
                const controls = sent ? '' : `
                    <div class="qty-controls">
                        <button onclick="updateQty(${item.id}, -0.5)">-</button>
                        <span class="px-1 font-weight-bold">${qtyStr}</span>
                        <button onclick="updateQty(${item.id}, 0.5)">+</button>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${item.id})"><i class="fas fa-trash"></i></button>`;
                return `
                <div class="order-item">
                    <div class="item-info">
                        <div class="item-name">${item.product_name}</div>
                        <div class="item-detail">Nivel ${item.price_level} · S/ ${Number(item.unit_price).toFixed(3)} · ${qtyStr} ${MODE === 'compra' ? 'kg/und' : 'und/kg'}${item.notes ? ' · ' + item.notes : ''}</div>
                    </div>
                    ${controls}
                    <div class="item-total">S/ ${Number(item.total).toFixed(2)}</div>
                </div>`;
            }).join('');
        }
        $('#orderModal').addClass('show');
    }

    function closeOrderModal() {
        $('#orderModal').removeClass('show');
        currentOrderId = null;
        location.reload();
    }

    function updateQty(itemId, delta) {
        fetchJson(BASE + '/scrap-pos/items/' + itemId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ quantity_delta: delta }),
        })
            .then(data => { if (!data.success) throw new Error(data.message); loadOrder(); })
            .catch(err => showError(err.message));
    }

    function removeItem(itemId) {
        if (!confirm('¿Eliminar este producto de la operación?')) return;
        fetchJson(BASE + '/scrap-pos/items/' + itemId, { method: 'DELETE' })
            .then(data => { if (!data.success) throw new Error(data.message); loadOrder(); })
            .catch(err => showError(err.message));
    }

    function printList() {
        fetchJson(BASE + '/scrap-pos/orders/' + currentOrderId + '/print-list', { method: 'POST' })
            .then(data => { if (!data.success) throw new Error(data.message); showError('Lista enviada a imprimir'); })
            .catch(err => showError(err.message));
    }

    function printPrecuenta() {
        fetchJson(BASE + '/scrap-pos/orders/' + currentOrderId + '/precuenta', { method: 'POST' })
            .then(data => { if (!data.success) throw new Error(data.message); showError('Precuenta enviada a imprimir'); })
            .catch(err => showError(err.message));
    }

    function showSendModal() {
        const total = parseFloat((document.getElementById('orderTotalLabel').textContent || '0').replace(/[^\d.-]/g, ''));
        document.getElementById('sendPreview').textContent = 'Total: S/ ' + total.toFixed(2) + '. Se imprimirá la lista y la operación quedará bloqueada.';
        document.getElementById('sendSellerName').value = currentSeller || '';
        $('#sendModal').modal('show');
    }

    function confirmSend() {
        const seller = document.getElementById('sendSellerName').value.trim();
        if (!seller) { showError('Ingrese el nombre del cliente/vendedor'); return; }
        fetchJson(BASE + '/scrap-pos/orders/' + currentOrderId + '/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ seller_name: seller }),
        })
            .then(data => {
                if (!data.success) throw new Error(data.message);
                $('#sendModal').modal('hide');
                loadOrder();
                pollStations();
            })
            .catch(err => showError(err.message));
    }

    function pollStations() {
        fetchJson(BASE + '/scrap-pos/' + MODE + '/stations')
            .then(data => {
                if (data.success) renderStationGrid(data.stations);
            })
            .catch(() => {});
    }

    function renderStationGrid(stations) {
        const grid = document.getElementById('stationsGrid');
        if (!grid) return;
        const activeId = currentOrderId;
        let stillActive = !activeId;
        const icon = MODE === 'compra' ? 'cart-arrow-down' : 'receipt';
        grid.innerHTML = stations.map(s => {
            if (activeId && s.order_id == activeId) stillActive = true;
            const cls = s.status === 'POR_COBRAR' ? 'por-cobrar' : (s.status === 'PESANDO' ? 'pesando' : '');
            const label = s.status === 'LIBRE' ? 'LIBRE' : (s.status === 'POR_COBRAR' ? 'POR COBRAR' : 'PESANDO');
            let extra = '';
            if (s.status !== 'LIBRE') {
                extra = `<div class="station-order">${s.items_count} item(s) · S/ ${Number(s.total).toFixed(2)}</div>`;
                if (s.status === 'POR_COBRAR' && s.seller) extra += `<div class="station-seller">${s.seller}</div>`;
            }
            return `
            <div class="station-card ${cls}" data-station-id="${s.id}" data-station-name="${s.name}"
                 onclick="openStation(${s.id}, '${s.name}')">
                <button type="button" class="station-delete-btn" title="Anular operación y liberar estación"
                        onclick="event.stopPropagation(); confirmDeleteStation(${s.id}, '${s.name}', MODE)">
                    <i class="fas fa-times"></i>
                </button>
                <i class="fas fa-${icon}"></i>
                <div class="station-name">${s.name}</div>
                <div class="station-status">${label}</div>
                ${extra}
            </div>`;
        }).join('');

        if (activeId && !stillActive) {
            $('#orderModal').removeClass('show');
            currentOrderId = null;
        }
    }

    setInterval(pollStations, 10000);
    pollStations();

    function showProductPicker() {
        document.getElementById('productSearch').value = '';
        $('#productPickerModal').addClass('show');
    }
    function closeProductPicker() { $('#productPickerModal').removeClass('show'); }

    function filterCategory(catId) {
        document.querySelectorAll('#categoryFilters button').forEach(b => {
            b.classList.remove('btn-primary'); b.classList.add('btn-outline-secondary');
        });
        const btn = document.querySelector(`#categoryFilters button[data-cat="${catId}"]`);
        if (btn) { btn.classList.add('btn-primary'); btn.classList.remove('btn-outline-secondary'); }
        applyProductFilter();
    }

    function applyProductFilter() {
        const q = (document.getElementById('productSearch').value || '').toLowerCase();
        const cat = document.querySelector('#categoryFilters .btn-primary')?.dataset?.cat ?? '';
        document.querySelectorAll('.product-card').forEach(card => {
            const matchQ = !q || card.dataset.name.includes(q);
            const matchC = cat === '' || card.dataset.cat === cat;
            card.style.display = (matchQ && matchC) ? '' : 'none';
        });
    }

    document.getElementById('productSearch').addEventListener('input', applyProductFilter);

    function selectProduct(card) {
        const raw = {
            id: card.dataset.id, name: card.dataset.desc,
            v1: parseFloat(card.dataset.v1 || 0), v2: parseFloat(card.dataset.v2 || 0),
            v3: parseFloat(card.dataset.v3 || 0), v4: parseFloat(card.dataset.v4 || 0),
            c1: parseFloat(card.dataset.c1 || 0), c2: parseFloat(card.dataset.c2 || 0),
            c3: parseFloat(card.dataset.c3 || 0), c4: parseFloat(card.dataset.c4 || 0),
        };
        selectedProduct = raw;
        selectedPriceLevel = 1;
        $('#qtyProductName').text(raw.name);
        document.querySelectorAll('.price-level-chip').forEach(chip => {
            const level = parseInt(chip.dataset.level);
            const price = MODE === 'compra' ? raw['c' + level] : raw['v' + level];
            chip.querySelector('.pl-value').textContent = price.toFixed(4);
        });
        document.getElementById('qtyInput').value = '1';
        document.getElementById('qtyNotes').value = '';
        hideQtyError();
        selectPriceLevel(1);
        $('#productPickerModal').removeClass('show');
        $('#productQtyModal').addClass('show');
    }

    function hideQtyError() { document.getElementById('qtyError').style.display = 'none'; }
    function showQtyError(msg) {
        const el = document.getElementById('qtyError');
        el.textContent = msg;
        el.style.display = 'block';
    }

    function selectPriceLevel(level) {
        selectedPriceLevel = level;
        document.querySelectorAll('.price-level-chip').forEach(chip => {
            chip.classList.toggle('active', parseInt(chip.dataset.level) === level);
        });
        updateQtyPreview();
    }

    function currentPrice() {
        if (!selectedProduct) return 0;
        return MODE === 'compra' ? selectedProduct['c' + selectedPriceLevel] : selectedProduct['v' + selectedPriceLevel];
    }

    function changeQty(delta) {
        const input = document.getElementById('qtyInput');
        let v = parseFloat(input.value) || 0;
        v = Math.max(0.0001, v + delta);
        input.value = v;
        updateQtyPreview();
    }

    document.getElementById('qtyInput').addEventListener('input', updateQtyPreview);

    function updateQtyPreview() {
        const qty = parseFloat(document.getElementById('qtyInput').value) || 0;
        const total = qty * currentPrice();
        document.getElementById('qtyPreview').textContent = 'Total: S/ ' + total.toFixed(4);
    }

    function closeQtyModal() { $('#productQtyModal').removeClass('show'); }

    function confirmAddItem() {
        const qty = parseFloat(document.getElementById('qtyInput').value);
        const notes = document.getElementById('qtyNotes').value.trim();
        hideQtyError();
        if (!selectedProduct || !qty || qty <= 0) { showQtyError('Ingrese una cantidad válida'); return; }
        if (currentPrice() <= 0) { showQtyError('El nivel de precio seleccionado está en 0. Configure el multiprecio del producto.'); return; }

        fetchJson(BASE + '/scrap-pos/orders/' + currentOrderId + '/items', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: selectedProduct.id, quantity: qty, price_level: selectedPriceLevel, notes: notes }),
        })
            .then(data => {
                if (!data.success) throw new Error(data.message);
                $('#productQtyModal').removeClass('show');
                loadOrder();
            })
            .catch(err => showQtyError(err.message));
    }

    function showChargeModal() {
        const total = parseFloat((document.getElementById('orderTotalLabel').textContent || '0').replace(/[^\d.-]/g, ''));
        document.getElementById('chargeAmount').value = total.toFixed(2);
        document.getElementById('chargeReference').value = currentSeller || '';
        updateVuelto();
        $('#chargeModal').modal('show');
    }

    document.getElementById('chargeAmount').addEventListener('input', updateVuelto);

    function updateVuelto() {
        const total = parseFloat((document.getElementById('orderTotalLabel').textContent || '0').replace(/[^\d.-]/g, ''));
        const amount = parseFloat(document.getElementById('chargeAmount').value) || 0;
        document.getElementById('chargeVuelto').textContent = 'Vuelto: S/ ' + Math.max(0, amount - total).toFixed(2);
    }

    function confirmCharge() {
        const total = parseFloat((document.getElementById('orderTotalLabel').textContent || '0').replace(/[^\d.-]/g, ''));
        const amount = parseFloat(document.getElementById('chargeAmount').value) || total;
        const payload = {
            customer_id: document.getElementById('chargeCustomer').value || null,
            document_type: MODE === 'compra' ? 'CO' : document.getElementById('chargeDocType').value,
            payments: [{ method: document.getElementById('chargeMethod').value, amount: amount }],
            reference: document.getElementById('chargeReference').value || '',
        };
        fetchJson(BASE + '/scrap-pos/orders/' + currentOrderId + '/charge', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        })
            .then(data => {
                if (!data.success) throw new Error(data.message);
                $('#chargeModal').modal('hide');
                lastInvoiceId = data.invoice_id;
                $('#successNumber').text(data.full_number);
                $('#successTotal').text('S/ ' + Number(data.total).toFixed(2));
                $('#btnPrintCompra80').attr('disabled', false);
                $('#btnPrintCompraA4').attr('disabled', false);
                $('#btnPrintVenta80').attr('disabled', false);
                $('#btnPrintVentaA4').attr('disabled', false);
                $('#successModal').modal('show');
                currentOrderId = null;
                $('#orderModal').removeClass('show');
            })
            .catch(err => showError(err.message));
    }

    function printCompra(invoiceId, format) {
        if (!invoiceId) return;
        if (format === '80mm') {
            printThermal(invoiceId);
            return;
        }
        window.open(BASE + '/scrap-pos/print/' + invoiceId + '/' + format, '_blank');
    }

    function printVenta(invoiceId, format) {
        if (!invoiceId) return;
        if (format === '80mm') {
            printThermal(invoiceId);
            return;
        }
        window.open(BASE + '/pos/print/' + invoiceId + '/' + format, '_blank');
    }

    function printThermal(invoiceId) {
        fetchJson(BASE + '/scrap-pos/print/' + invoiceId + '/thermal', { method: 'POST' })
            .then(data => {
                if (!data.success) throw new Error(data.message);
                showError(data.message);
            })
            .catch(err => showError(err.message));
    }

    $('#successModal').on('hidden.bs.modal', function () { location.reload(); });
</script>
@endpush
