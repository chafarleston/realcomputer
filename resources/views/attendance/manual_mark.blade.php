@extends('layouts.admin')
@section('title', 'Marcador Manual')
@section('page_title', 'Marcador Manual de Asistencia')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-edit"></i> Registrar Marcación Manual</h3>
            </div>
            <div class="card-body">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('success') }}
                </div>
                @endif
                @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('error') }}
                </div>
                @endif

                <form method="POST" action="{{ route('attendance.manual-mark.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Trabajador</label>
                        <select name="personal_id" class="form-control" required>
                            <option value="">Seleccionar...</option>
                            @foreach($personal as $p)
                                <option value="{{ $p->id }}" {{ old('personal_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->nombre_completo }} ({{ $p->dni }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha</label>
                                <input type="date" name="fecha" class="form-control" value="{{ old('fecha', now()->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hora</label>
                                <input type="time" name="hora" class="form-control" value="{{ old('hora', now()->format('H:i')) }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Evento</label>
                        <select name="tipo_evento" class="form-control" required>
                            <option value="ENTRADA1" {{ old('tipo_evento') == 'ENTRADA1' ? 'selected' : '' }}>Entrada 1</option>
                            <option value="SALIDA1" {{ old('tipo_evento') == 'SALIDA1' ? 'selected' : '' }}>Salida 1</option>
                            <option value="ENTRADA2" {{ old('tipo_evento') == 'ENTRADA2' ? 'selected' : '' }}>Entrada 2</option>
                            <option value="SALIDA2" {{ old('tipo_evento') == 'SALIDA2' ? 'selected' : '' }}>Salida 2</option>
                        </select>
                        <small class="form-text text-muted">Se aplica la tardanza/descuento según el horario y reglas de asistencia.</small>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Registrar Marcación</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection