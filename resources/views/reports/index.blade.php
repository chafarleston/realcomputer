@extends('layouts.admin')
@section('title', 'Reportes de Compras y Ventas')
@section('page_title', 'Reportes de Compras y Ventas')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> Filtros</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('reports.index') }}" id="reportFilterForm">
                    <input type="hidden" name="company_id" value="{{ $companyId }}">
                    <div class="row">
                        <div class="col-md-2">
                            <label>Tipo de Reporte</label>
                            <select name="tipo" class="form-control">
                                <option value="venta" {{ $tipo === 'venta' ? 'selected' : '' }}>Ventas</option>
                                <option value="compra" {{ $tipo === 'compra' ? 'selected' : '' }}>Compras</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Periodo</label>
                            <select name="periodo" class="form-control" id="periodoSelect">
                                <option value="diario" {{ $periodo === 'diario' ? 'selected' : '' }}>Diario</option>
                                <option value="semanal" {{ $periodo === 'semanal' ? 'selected' : '' }}>Semanal</option>
                                <option value="mensual" {{ $periodo === 'mensual' ? 'selected' : '' }}>Mensual</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Fecha</label>
                            <input type="date" name="fecha" class="form-control" value="{{ $fecha->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="btn-group">
                                <a href="{{ route('reports.index', array_merge(request()->except('fecha'), ['fecha' => $anterior])) }}" class="btn btn-default"><i class="fas fa-chevron-left"></i></a>
                                <a href="{{ route('reports.index', array_merge(request()->except('fecha'), ['fecha' => $siguiente])) }}" class="btn btn-default"><i class="fas fa-chevron-right"></i></a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label>Selección de productos</label>
                            <select name="seleccion" class="form-control" id="seleccionSelect">
                                <option value="todos" {{ $seleccion === 'todos' ? 'selected' : '' }}>Todos los productos</option>
                                <option value="categoria" {{ $seleccion === 'categoria' ? 'selected' : '' }}>Por categoría</option>
                                <option value="productos" {{ $seleccion === 'productos' ? 'selected' : '' }}>Varios productos</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3" id="categoriaRow" style="{{ $seleccion === 'categoria' ? '' : 'display:none;' }}">
                        <div class="col-md-6">
                            <label>Categoría</label>
                            <select name="categoria_id" class="form-control" id="categoriaSelect">
                                <option value="">Seleccione categoría</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria->id }}" {{ (string)($categoriaId ?? '') === (string)$categoria->id ? 'selected' : '' }}>{{ $categoria->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mt-3" id="productosRow" style="{{ $seleccion === 'productos' ? '' : 'display:none;' }}">
                        <div class="col-md-8">
                            <label>Seleccione uno o varios productos</label>
                            <select name="productos[]" class="form-control" multiple size="8" id="productosSelect">
                                @foreach($productos as $producto)
                                    <option value="{{ $producto->id }}" {{ in_array($producto->id, $productoIds) ? 'selected' : '' }}>
                                        {{ $producto->descripcion }} ({{ $producto->codigo }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Mantenga Ctrl (o Cmd) para seleccionar varios.</small>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Generar Reporte</button>
                            <a href="{{ route('reports.export', request()->query()) }}" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Exportar Excel
                            </a>
                            <a href="{{ route('reports.export-pdf', request()->query()) }}" class="btn btn-danger">
                                <i class="fas fa-file-pdf"></i> Exportar PDF
                            </a>
                            <p class="form-text text-muted d-inline ml-2">{{ $tituloPeriodo }}</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@if($totales)
<div class="row">
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-{{ $tipo === 'compra' ? 'info' : 'success' }}"><i class="fas fa-file-invoice"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Documentos</span>
                <span class="info-box-number">{{ (int) $totales->documentos }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-balance-scale"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Cantidad</span>
                <span class="info-box-number">{{ number_format((float) $totales->cantidad, 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-dollar-sign"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Importe Total</span>
                <span class="info-box-number">S/ {{ number_format((float) $totales->importe, 2) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-secondary"><i class="fas fa-percent"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">IGV</span>
                <span class="info-box-number">S/ {{ number_format((float) $totales->igv, 2) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-box"></i> Detalle por Producto</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-right">Doc.</th>
                            <th class="text-right">Cantidad</th>
                            <th class="text-right">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($porProducto as $p)
                        <tr>
                            <td>{{ $p->codigo }}</td>
                            <td>{{ $p->descripcion }}</td>
                            <td>{{ $p->categoria ?? 'Sin categoría' }}</td>
                            <td class="text-right">{{ (int) $p->documentos }}</td>
                            <td class="text-right">{{ number_format((float) $p->cantidad, 2) }}</td>
                            <td class="text-right">S/ {{ number_format((float) $p->importe, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">Sin resultados para el periodo seleccionado</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Resumen por Día</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th class="text-right">Doc.</th>
                            <th class="text-right">Importe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($porFecha as $d)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($d->fecha_emision)->format('d/m/Y') }}</td>
                            <td class="text-right">{{ (int) $d->documentos }}</td>
                            <td class="text-right">S/ {{ number_format((float) $d->importe, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center">Sin resultados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list"></i> Comprobantes</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Comprobante</th>
                            <th>Fecha</th>
                            <th>Cliente / Vendedor</th>
                            <th>Pago</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documentos as $doc)
                        <tr>
                            <td>{{ $doc->full_number }}</td>
                            <td>{{ \Carbon\Carbon::parse($doc->fecha_emision)->format('d/m/Y') }}</td>
                            <td>{{ $doc->cliente ?? 'CLIENTES VARIOS' }}</td>
                            <td>{{ $doc->metodo_pago }}</td>
                            <td class="text-right">S/ {{ number_format((float) $doc->total, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">Sin comprobantes en el periodo</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        var seleccion = document.getElementById('seleccionSelect');
        var categoriaRow = document.getElementById('categoriaRow');
        var productosRow = document.getElementById('productosRow');

        function toggleFilas() {
            if (!seleccion) return;
            var v = seleccion.value;
            categoriaRow.style.display = v === 'categoria' ? '' : 'none';
            productosRow.style.display = v === 'productos' ? '' : 'none';
        }

        seleccion.addEventListener('change', toggleFilas);
        toggleFilas();
    })();
</script>
@endpush