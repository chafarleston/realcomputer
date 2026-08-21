<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota de Compra</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Courier New', monospace; font-size: 10px; color: #000; padding: 5px; width: 76mm; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .sep { border-top: 1px dashed #000; margin: 4px 0; }
        .sep-double { border-top: 2px solid #000; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; }
        .logo { max-height: 50px; max-width: 120px; margin-bottom: 4px; }
        body.is-80mm { width: 76mm; }
    </style>
</head>
<body class="is-80mm">
    <div class="text-center">
        @if($company->logo && file_exists(storage_path('app/public/' . $company->logo)))
            <img src="data:{{ \Illuminate\Support\Facades\File::mimeType(storage_path('app/public/' . $company->logo)) }};base64,{{ base64_encode(\Illuminate\Support\Facades\File::get(storage_path('app/public/' . $company->logo))) }}" class="logo">
        @endif
        <div class="bold" style="font-size:11px;">{{ $company->nombre_comercial ?? $company->razon_social }}</div>
        <div>{{ $company->razon_social }}</div>
        <div>RUC: {{ $company->ruc }}</div>
    </div>
    <div class="text-center bold" style="font-size:12px; margin-top:4px;">NOTA DE COMPRA</div>
    <div class="text-center bold" style="font-size:12px;">{{ $invoice->full_number }}</div>
    <div class="sep"></div>
    <table>
        <tr><td>F. Emisión: {{ date('d/m/Y', strtotime($invoice->fecha_emision)) }} {{ $invoice->hora_emision ? '| H. ' . substr($invoice->hora_emision, 0, 8) : '' }}</td></tr>
        <tr><td>Vendedor: {{ $invoice->customer->nombre ?? 'CLIENTES VARIOS' }}</td></tr>
        @if($invoice->customer && $invoice->customer->documento_numero)
            <tr><td>Doc: {{ $invoice->customer->documento_numero }}</td></tr>
        @endif
        @if($invoice->referencia_pago)
            <tr><td>Ref: {{ $invoice->referencia_pago }}</td></tr>
        @endif
    </table>
    <div class="sep"></div>
    <table>
        <tr class="bold"><td>CANT</td><td>PRODUCTO</td><td class="text-right">IMPORTE</td></tr>
        @foreach($invoice->items as $item)
        <tr>
            <td valign="top">{{ rtrim(rtrim(number_format($item->cantidad, 3), '0'), '.') }}</td>
            <td valign="top">{{ \Illuminate\Support\Str::limit($item->descripcion, 22) }}</td>
            <td class="text-right" valign="top">S/ {{ number_format($item->precio_venta, 2) }}</td>
        </tr>
        @endforeach
    </table>
    <div class="sep-double"></div>
    <table>
        <tr><td>Subtotal:</td><td class="text-right">S/ {{ number_format($invoice->subtotal, 2) }}</td></tr>
        <tr><td>IGV ({{ $company->getActiveIgvPercent() }}%):</td><td class="text-right">S/ {{ number_format($invoice->igv, 2) }}</td></tr>
        <tr class="bold" style="font-size:11px;">
            <td>TOTAL:</td>
            <td class="text-right">S/ {{ number_format($invoice->total, 2) }}</td>
        </tr>
    </table>
    <div class="sep"></div>
    <table>
        <tr><td>Pago: {{ $invoice->metodo_pago }}</td></tr>
    </table>
    <div class="text-center" style="margin-top:6px; font-size:8px;">
        {{ $company->razon_social }} - {{ date('d/m/Y H:i') }}
    </div>
</body>
</html>