@extends('layouts.admin')
@section('title', 'Ingresos y Gastos')
@section('page_title', 'Ingresos y Gastos')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Registrar Movimiento</h3>
            </div>
            <form method="POST" action="{{ route('cash-movements.store') }}">
                @csrf
                <div class="card-body">
                    @if(!$cajaAbierta)
                    <div class="alert alert-warning mb-3">
                        No hay caja abierta. Debe abrir la caja para registrar ingresos o gastos.
                    </div>
                    @endif
                    <div class="form-group">
                        <label>Tipo</label>
                        <select name="tipo" class="form-control" required>
                            <option value="INGRESO">Ingreso (+)</option>
                            <option value="GASTO">Gasto (-)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Monto (S/)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">S/</span>
                            </div>
                            <input type="number" name="monto" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Concepto</label>
                        <input type="text" name="concepto" class="form-control" placeholder="Ej: Alquiler, Venta de fierro, Pago de luz...">
                    </div>
                    <div class="form-group">
                        <label>Fecha</label>
                        <input type="datetime-local" name="fecha" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" @if(!$cajaAbierta) disabled @endif>
                        <i class="fas fa-plus"></i> Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>S/ {{ number_format($movimientos->where('tipo', 'INGRESO')->sum('monto'), 2) }}</h3>
                        <p>Total Ingresos</p>
                    </div>
                    <div class="icon"><i class="fas fa-arrow-down"></i></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>S/ {{ number_format($movimientos->where('tipo', 'GASTO')->sum('monto'), 2) }}</h3>
                        <p>Total Gastos</p>
                    </div>
                    <div class="icon"><i class="fas fa-arrow-up"></i></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Movimientos Registrados</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th>Monto</th>
                            <th>Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movimientos as $mov)
                        <tr>
                            <td>{{ $mov->fecha ? $mov->fecha->format('d/m/Y H:i') : '-' }}</td>
                            <td>
                                <span class="badge badge-{{ $mov->tipo == 'INGRESO' ? 'success' : 'danger' }}">
                                    {{ $mov->tipo }}
                                </span>
                            </td>
                            <td>{{ $mov->concepto ?? '-' }}</td>
                            <td class="{{ $mov->tipo == 'INGRESO' ? 'text-success' : 'text-danger' }} font-weight-bold">
                                {{ $mov->tipo == 'INGRESO' ? '+' : '-' }} S/ {{ number_format($mov->monto, 2) }}
                            </td>
                            <td>{{ $mov->user?->name ?? '-' }}</td>
                            <td>
                                <form method="POST" action="{{ route('cash-movements.destroy', $mov) }}" onsubmit="return confirm('¿Eliminar este movimiento?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center">No hay movimientos registrados</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="card-footer">{{ $movimientos->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
