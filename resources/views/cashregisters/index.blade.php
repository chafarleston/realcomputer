@extends('layouts.admin')
@section('title', 'Caja')
@section('page_title', 'Caja')

@section('content')
<div class="row">
    <div class="col-md-12">
        @if($cajaAbierta)
        <div class="alert alert-success">
            <h4><i class="fas fa-cash-register"></i> Caja Abierta</h4>
            <p>Fecha apertura: {{ $cajaAbierta->fecha_apertura ? $cajaAbierta->fecha_apertura->format('d/m/Y H:i') : '-' }}</p>
            <p>Monto apertura: S/ {{ number_format($cajaAbierta->monto_apertura, 2) }}</p>

            @if($flujo)
            <hr>
            <h5><i class="fas fa-exchange-alt"></i> Flujo de Caja del Día</h5>
            <table class="table table-sm table-bordered mb-2" style="max-width:520px; background:#fff;">
                <tbody>
                    <tr>
                        <th>Efectivo Inicial (Apertura)</th>
                        <td class="text-right">S/ {{ number_format($flujo['apertura'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>+ Ventas ({{ $flujo['ventas_count'] }} operaciones)</th>
                        <td class="text-right text-success">S/ {{ number_format($flujo['ventas'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>− Compras de Chatarra ({{ $flujo['compras_count'] }} operaciones)</th>
                        <td class="text-right text-danger">S/ {{ number_format($flujo['compras'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>+ Otros Ingresos</th>
                        <td class="text-right text-success">S/ {{ number_format($flujo['ingresos'], 2) }}</td>
                    </tr>
                    <tr>
                        <th>− Gastos</th>
                        <td class="text-right text-danger">S/ {{ number_format($flujo['gastos'], 2) }}</td>
                    </tr>
                    <tr class="font-weight-bold" style="background:#d4edda;">
                        <th>SALDO ACTUAL (efectivo en caja)</th>
                        <td class="text-right" style="font-size:16px;">S/ {{ number_format($flujo['saldo'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            <form method="POST" action="{{ route('cashregisters.close') }}" class="mt-3">
                @csrf
                <input type="hidden" name="cashregister_id" value="{{ $cajaAbierta->id }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Monto contado en caja (opcional)</label>
                            <input type="number" name="monto_cierre" class="form-control" step="0.01" placeholder="Dejar vacío = saldo automático">
                        </div>
                    </div>
                </div>
                @can('permission', 'close_cashregister')
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-lock"></i> Cerrar Caja
                </button>
                @endcan
            </form>
        </div>
        @else
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Abrir Caja</h3>
            </div>
            <form method="POST" action="{{ route('cashregisters.open') }}">
                @csrf
                <input type="hidden" name="company_id" value="{{ $companyId }}">
                <div class="card-body">
                    <div class="form-group">
                        <label>Monto de apertura (efectivo inicial)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">S/</span>
                            </div>
                            <input type="number" name="monto_apertura" class="form-control" step="0.01" min="0" value="3000" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Nombre de referencia</label>
                        <input type="text" name="referencia" class="form-control" placeholder="Ej: 25-05-mañana">
                    </div>
                </div>
                <div class="card-footer">
                    @can('permission', 'open_cashregister')
                    <button type="submit" class="btn btn-primary"><i class="fas fa-cash-register"></i> Abrir Caja</button>
                    @endcan
                </div>
            </form>
        </div>
        @endif
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Historial de Cajas</h3>
            </div>
            <div class="card-body table-responsive p-0">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Ref.</th>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Apertura</th>
                                <th>Cierre</th>
                                <th>Ventas</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cajas as $caja)
                            <tr>
                                <td><span class="badge badge-secondary">{{ $caja->referencia ?? '-' }}</span></td>
                                <td>{{ $caja->fecha_apertura ? $caja->fecha_apertura->format('d/m/Y') : '-' }}</td>
                            <td>{{ $caja->user?->name ?? 'Eliminado' }}</td>
                            <td>S/ {{ number_format($caja->monto_apertura, 2) }}</td>
                            <td>{{ $caja->monto_cierre ? 'S/ ' . number_format($caja->monto_cierre, 2) : '-' }}</td>
                            <td>{{ $caja->cantidad_ventas }}</td>
                            <td>S/ {{ number_format($caja->total_ventas, 2) }}</td>
                            <td>
                                <span class="badge badge-{{ $caja->estado == 'ABIERTA' ? 'success' : 'secondary' }}">
                                    {{ $caja->estado }}
                                </span>
                            </td>
                            <td>
                                @if($caja->estado === 'CERRADA')
                                <a href="{{ route('cashregisters.show', $caja) }}" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('cashregisters.pdf', $caja) }}" class="btn btn-primary btn-sm" target="_blank"><i class="fas fa-file-pdf"></i> A4</a>
                                <a href="{{ route('cashregisters.ticket', $caja) }}" class="btn btn-warning btn-sm" target="_blank"><i class="fas fa-print"></i> 80mm</a>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center">No hay cajas registradas</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="card-footer">{{ $cajas->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection