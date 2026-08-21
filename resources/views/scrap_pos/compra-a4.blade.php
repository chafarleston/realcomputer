<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota de Compra</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #333; padding: 15px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
        .header .logo { max-height: 70px; max-width: 120px; margin-bottom: 5px; }
        .header h2 { margin: 0; font-size: 18px; }
        .header .doc-number { font-size: 14px; font-weight: bold; margin-top: 4px; }
        .info { margin-bottom: 15px; }
        .info table { width: 100%; border-collapse: collapse; }
        .info td { padding: 3px 5px; }
        .info .label { font-weight: bold; width: 140px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.items th { background: #333; color: #fff; padding: 8px 5px; text-align: left; }
        table.items td { padding: 6px 5px; border-bottom: 1px solid #ddd; }
        table.items .text-right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 13px; border-top: 2px solid #333; }
        .totals { width: 100%; table-layout: fixed; }
        .totals table { width: 100%; border-collapse: collapse; float: right; max-width: 280px; }
        .totals td { padding: 3px 5px; }
        .totals .text-right { text-align: right; }
        .totals .grand-total { font-weight: bold; font-size: 13px; border-top: 2px solid #333; }
        .footer { text-align: center; margin-top: 20px; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
        .firmas { margin-top: 40px; }
        .firmas table { width: 100%; border-collapse: collapse; }
        .firmas td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 10px; height: 60px; }
        .firmas .line { border-top: 1px dashed #000; margin-top: 40px; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        @if($company->logo && file_exists(storage_path('app/public/' . $company->logo)))
            <img src="data:{{ \Illuminate\Support\Facades\File::mimeType(storage_path('app/public/' . $company->logo)) }};base64,{{ base64_encode(\Illuminate\Support\Facades\File::get(storage_path('app/public/' . $company->logo))) }}" class="logo">
        @endif
        <h2>NOTA DE COMPRA</h2>
        <div class="doc-number">{{ $invoice->full_number }}</div>
    </div>

    <div class="info">
        <table>
            <tr><td class="label">Razón Social:</td><td>{{ $company->razon_social }}</td></tr>
            <tr><td class="label">RUC:</td><td>{{ $company->ruc }}</td></tr>
            <tr><td class="label">Dirección:</td><td>{{ $company->direccion }}</td></tr>
        </table>
        <table style="margin-top:10px;">
            <tr><td class="label">F. Emisión:</td><td>{{ date('d/m/Y', strtotime($invoice->fecha_emision)) }} {{ $invoice->hora_emision ? '| ' . substr($invoice->hora_emision, 0, 8) : '' }}</td></tr>
            <tr><td class="label">Vendedor:</td><td>{{ $invoice->customer->nombre ?? 'CLIENTES VARIOS' }}</td></tr>
            @if($invoice->customer && $invoice->customer->documento_numero)
            <tr><td class="label">Doc.:</td><td>{{ $invoice->customer->documento_numero }}</td></tr>
            @endif
            @if($invoice->referencia_pago)
            <tr><td class="label">Referencia:</td><td>{{ $invoice->referencia_pago }}</td></tr>
            @endif
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:12%">Cant.</th>
                <th style="width:48%">Producto</th>
                <th style="width:15%" class="text-right">P. Unitario</th>
                <th style="width:25%" class="text-right">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ rtrim(rtrim(number_format($item->cantidad, 3), '0'), '.') }}</td>
                <td>{{ $item->descripcion }}</td>
                <td class="text-right">S/ {{ number_format($item->precio_unitario, 2) }}</td>
                <td class="text-right">S/ {{ number_format($item->precio_venta, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL:</td>
                <td class="text-right">S/ {{ number_format($invoice->total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="totals" style="float:right;">
        <table>
            <tr><td>Subtotal:</td><td class="text-right">S/ {{ number_format($invoice->subtotal, 2) }}</td></tr>
            <tr><td>IGV ({{ $company->getActiveIgvPercent() }}%):</td><td class="text-right">S/ {{ number_format($invoice->igv, 2) }}</td></tr>
            <tr class="grand-total"><td>TOTAL:</td><td class="text-right">S/ {{ number_format($invoice->total, 2) }}</td></tr>
        </table>
    </div>

    <div class="firmas">
        <table>
            <tr>
                <td>
                    <div class="line">Vendedor / Entregó</div>
                </td>
                <td>
                    <div class="line">Recibido Conforme</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ $company->razon_social }} - {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>