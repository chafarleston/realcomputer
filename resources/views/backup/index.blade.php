@extends('layouts.admin')
@section('title', 'Backup DB')
@section('page_title', 'Respaldo de Base de Datos')

@section('content')
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-database"></i> Generar Backup</h3>
            </div>
            <form method="POST" action="{{ route('backup.run') }}">
                @csrf
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
                    <div class="form-group">
                        <label>Ruta de destino</label>
                        <input type="text" name="path" class="form-control" required value="{{ old('path', $defaultPath) }}">
                        <small class="form-text text-muted">
                            Especificá la ruta completa donde se guardará el archivo .sql.
                            Ej: <code>C:\backups\realcomputer.sql</code>
                        </small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Generar Backup
                    </button>
                </div>
            </form>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle"></i> Información</h3>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>El backup contiene toda la base de datos <strong>realcomputer</strong>: empresas, usuarios, productos, facturas, pedidos, etc.</li>
                    <li>El archivo generado es un script SQL estándar.</li>
                    <li><strong>Para restaurar</strong>, desde la terminal de Laragon (CMD), recordar que la BD del sistema se llama <code>realcomputer</code>:</li>
                </ul>
                <pre class="bg-light border p-2 mt-2 mb-0"><code>mysql -u root realcomputer &lt; ruta\del\backup.sql</code></pre>
                <ul class="mt-2 mb-0">
                    <li>El usuario <em>root</em> de MySQL local no usa contraseña por defecto. Si tuviera contraseña, usar <code>-p</code>.</li>
                    <li>Recomendación: guardar en un disco externo o en la nube después de generarlo.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
