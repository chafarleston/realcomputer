<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de {{ $tipo === 'compra' ? 'Compras' : 'Ventas' }} - {{ $desde }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; padding: 10px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 12px; }
        .header h2 { margin: 0; font-size: 15px; }
        .header .company { font-size: 11px; color: #666; }
        .header .period { font-size: 12px; font-weight: bold; margin-top: 4px; }
        .sep { border-top: 1px dashed #999; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; font-size: 9px; }
        th { background: #333; color: #fff; padding: 5px 4px; text-align: left; }
        th.text-right, td.text-right { text-align: right; }
        td { padding: 4px; border-bottom: 1px solid #ddd; }
        .total-row td { font-weight: bold; border-top: 2px solid #333; background: #f5f5f5; }
        .subtitle { font-weight: bold; margin-bottom: 5px; font-size: 10px; }
        .footer { text-align: center; font-size: 8px; color: #999; border-top: 1px solid #ddd; padding-top: 6px; }
        .kpi { display: inline-block; margin-right: 20px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">{{ $company->razon_social }}<br>RUC: {{ $company->ruc }}</div>
        <h2>REPORTE DE {{ $tipo === 'compra' ? 'COMPRAS' : 'VENTAS' }}</h2>
        <div class="period">{{ $tituloPeriodo }}</div>
    </div>

    <div>
        <span class="kpi"><strong>Documentos:</strong> {{ (int) $totales->documentos }}</span>
        <span class="kpi"><strong>Cantidad:</strong> {{ number_format((float) $totales->cantidad, 2) }}</span>
        <span class="kpi"><strong>Importe:</strong> S/ {{ number_format((float) $totales->importe, 2) }}</span>
        <span class="kpi"><strong>IGV:</strong> S/ {{ number_format((float) $totales->igv, 2) }}</span>
    </div>

    <div class="sep"></div>

    <div class="subtitle">Detalle por Producto</div>
    <table>
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
            <tr><td colspan="6">Sin resultados para el periodo seleccionado</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="subtitle">Resumen por Día</div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th class="text-right">Documentos</th>
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
            <tr><td colspan="3">Sin resultados</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} - {{ $company->razon_social }}
    </div>
</body>
</html>