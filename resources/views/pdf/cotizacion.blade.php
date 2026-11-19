<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cotización</title>
    <style>
        @page { margin: 36pt 38pt 46pt; }
        body { font-family: "DejaVu Sans", sans-serif; color: #233c4b; font-size: 9pt; line-height: 1.5; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { overflow-wrap: break-word; word-wrap: break-word; vertical-align: top; }
        .brand { table-layout: auto; margin-bottom: 9pt; }
        .brand td { padding: 0; }
        .brand-name { margin: 0 0 3pt; color: #1c3543; font-size: 14pt; font-weight: bold; line-height: 1.2; }
        .brand-contact { color: #637784; font-size: 7.5pt; line-height: 1.4; }
        .logo-cell { width: 48pt; }
        .logo { max-width: 38pt; max-height: 40pt; }
        .document-title { color: #263f53; font-size: 15pt; line-height: 1.2; font-weight: bold; margin-top: 0; }
        .document-side { width: 140pt; text-align: right; }
        .meta { border-collapse: separate; border-spacing: 6pt 0; margin: 0 -6pt 10pt; width: 100%; }
        .meta td { padding: 5pt 10pt; background: #fafcfe; border: 1pt solid #e8eef5; border-radius: 8pt; }
        .meta-label { display: block; color: #7b8b9a; font-size: 7pt; margin-bottom: 2pt; }
        .meta-value { color: #233c4b; font-size: 8.5pt; font-weight: bold; }
        .client { border-left: 3pt solid #83d9bc; padding: 1pt 0 1pt 12pt; margin-bottom: 18pt; }
        .section-title { margin: 0; padding: 6pt 12pt; background: #f0f6fc; border: 1pt solid #e8eef5; border-radius: 8pt 8pt 0 0; color: #263f53; font-size: 11pt; font-weight: bold; }
        .items { margin-bottom: 16pt; }
        .items thead { display: table-header-group; }
        .items th { background: #f5f8fc; color: #557d9e; font-size: 7.5pt; padding: 10pt; text-align: left; }
        .items td { padding: 8pt 10pt; border-bottom: 1pt solid #e8eef5; }
        .items tr { page-break-inside: avoid; }
        .items tbody tr:nth-child(odd) { background: #fafcfe; }
        .item-name { color: #245e82; font-size: 11pt; font-weight: bold; margin-bottom: 5pt; }
        .item-spec { color: #5e7380; font-size: 8pt; line-height: 1.45; }
        .item-spec b { color: #344e5c; font-weight: normal; }
        .quantity { text-align: center; font-size: 11pt; font-weight: bold; }
        .items th.quantity { text-align: center; font-size: 7.5pt; }
        .amount { text-align: right; white-space: nowrap; }
        .items th.amount { text-align: right; }
        .item-price { color: #233c4b; font-size: 10pt; font-weight: bold; }
        .summary { margin-bottom: 12pt; page-break-inside: avoid; }
        .summary td { padding: 10pt 14pt; background: #fafcfe; border-top: 1pt solid #e8eef5; border-bottom: 1pt solid #e8eef5; }
        .total-label { color: #263f53; font-size: 8pt; letter-spacing: .6pt; font-weight: bold; }
        .total-help { font-size: 7.5pt; color: #718395; margin-top: 3pt; }
        .total-amount { color: #23734e; font-size: 18pt; font-weight: bold; }
        .note { margin: 0 0 8pt; padding: 7pt 12pt; border: 1pt solid #e8eef5; border-radius: 8pt; background: #fafcfe; }
        .note-title { color: #345969; font-size: 9pt; margin: 0 0 5pt; font-weight: bold; page-break-after: avoid; }
        .text { white-space: pre-wrap; overflow-wrap: break-word; word-wrap: break-word; }
        .note-text { color: #5b6f7c; font-size: 8.5pt; line-height: 1.65; }
        .item-note { margin-top: 6pt; color: #5e7380; font-size: 8pt; }
        .footer { position: fixed; bottom: -27pt; left: 0; right: 0; border-top: 1pt solid #dce7ea; padding-top: 7pt; font-size: 7pt; color: #71838e; }
        .footer td { padding: 0; }
        .icon { width: 10pt; height: 10pt; vertical-align: middle; margin-right: 5pt; }
        .section-title .icon { display: inline-block; background: #e2edf8; padding: 5pt 6pt; border-radius: 6pt; color: #2f689b; }
        .mint-rule { height: 2pt; width: 28pt; background: #84dfc3; margin: 4pt 0 0 auto; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">
        <table><tr>
            <td>Importes expresados en bolivianos (Bs).</td>
            <td style="width: 90pt; text-align: right;">Página <span class="page-number"></span></td>
        </tr></table>
    </div>
    <table class="brand">
        <tr>
            @if($logo)
                <td class="logo-cell"><img class="logo" src="{{ $logo }}" alt="Logo"></td>
            @endif
            <td>
                <h1 class="brand-name">{{ $negocio['nombre'] }}</h1>
                <div class="brand-contact">
                    @if($negocio['direccion'])<div><strong>Dirección:</strong> {{ $negocio['direccion'] }}</div>@endif
                    @if($negocio['telefono'])<div><strong>Teléfono:</strong> {{ $negocio['telefono'] }}</div>@endif
                    @if($negocio['email'])<div><strong>Correo:</strong> {{ $negocio['email'] }}</div>@endif
                </div>
            </td>
            <td class="document-side">
                <div class="document-title">Cotización</div>
                <div class="mint-rule"></div>
            </td>
        </tr>
    </table>
    <table class="meta">
        <tr>
            <td><span class="meta-label"><img class="icon" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjNTU3ZDllIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHJlY3QgeD0iNCIgeT0iNSIgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiByeD0iMiIvPjxwYXRoIGQ9Ik00IDEwaDE2TTggM3Y1TTE2IDN2NU04IDE0aDJNMTQgMTRoMk04IDE3aDIiLz48L3N2Zz4=" alt="">Fecha de cotización</span><span class="meta-value">{{ $documento['fecha'] }}</span></td>
            <td><span class="meta-label"><img class="icon" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjNTU3ZDllIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PGNpcmNsZSBjeD0iMTIiIGN5PSIxMiIgcj0iOSIvPjxwYXRoIGQ9Im04IDEyIDMgMyA1LTYiLz48L3N2Zz4=" alt="">Precio aprobado el</span><span class="meta-value">{{ $documento['aprobacion'] }}</span></td>
        </tr>
    </table>
    @if($cliente)
        <div class="client">
            <div class="meta-label">Preparado para</div>
            <strong>{{ $cliente['nombre'] }}</strong>
            <div class="brand-contact">{{ $cliente['telefono'] ?? '' }} {{ $cliente['email'] ?? '' }}</div>
            @if(!empty($cliente['direccion']))<div class="brand-contact">{{ $cliente['direccion'] }}</div>@endif
        </div>
    @endif
    <h2 class="section-title"><img class="icon" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjNTU3ZDllIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBhdGggZD0iTTYgMTNWNGgxMnY5TTQgMTNoMTZ2NEg0ek02IDE3djRNMTggMTd2NE0xMCA0djlNMTQgNHY5Ii8+PC9zdmc+" alt="">Detalle de muebles</h2>
    <table class="items">
        <thead>
            <tr>
                <th style="width: 63%;">Mueble y características</th>
                <th class="quantity" style="width: 12%;">Cantidad</th>
                <th class="amount" style="width: 25%;">Precio total Bs</th>
            </tr>
        </thead>
        <tbody>
            @foreach($detalles as $detalle)
                <tr>
                    <td>
                        <div class="item-name">{{ $detalle['tipo_nombre'] }}</div>
                        <div class="item-spec">
                            <div><b>Medidas:</b> {{ App\Support\Decimal::mostrar($detalle['largo_cm']) }} × {{ App\Support\Decimal::mostrar($detalle['ancho_cm']) }} × {{ App\Support\Decimal::mostrar($detalle['alto_cm']) }} cm</div>
                            <div><b>Material:</b> {{ $detalle['material_principal_nombre'] }}</div>
                            <div><b>Acabado:</b> {{ $detalle['acabado'] }}</div>
                            <div>Puertas: {{ $detalle['puertas'] }} · Cajones: {{ $detalle['cajones'] }} · Divisiones: {{ $detalle['divisiones'] }}</div>
                        </div>
                        @if($detalle['observaciones'])<div class="text item-note">{{ $detalle['observaciones'] }}</div>@endif
                    </td>
                    <td class="quantity">{{ $detalle['cantidad_muebles'] }}</td>
                    <td class="amount item-price">{{ App\Support\Decimal::mostrar($detalle['precio_final'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table class="summary">
        <tr>
            <td style="width: 52%;">
                <div class="total-label"><img class="icon" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjNTU3ZDllIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PGVsbGlwc2UgY3g9IjkiIGN5PSI2IiByeD0iNiIgcnk9IjMiLz48cGF0aCBkPSJNMyA2djVjMCAyIDUgNCAxMCAyTTMgMTF2NWMwIDIgMyAzIDYgMyIvPjxlbGxpcHNlIGN4PSIxNiIgY3k9IjE1IiByeD0iNSIgcnk9IjMiLz48cGF0aCBkPSJNMTEgMTV2NGMwIDQgMTAgNCAxMCAwdi00Ii8+PC9zdmc+" alt="">TOTAL APROBADO</div>
                <div class="total-help">Precio total de los muebles cotizados.</div>
            </td>
            <td class="amount total-amount">Bs {{ App\Support\Decimal::mostrar($documento['total'], 2) }}</td>
        </tr>
    </table>
    @if($documento['observaciones'])
        <div class="note">
            <h2 class="note-title"><img class="icon" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjNTU3ZDllIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBhdGggZD0iTTQgNGgxNnYxM0g5bC01IDR6Ii8+PC9zdmc+" alt="">Observaciones</h2>
            <div class="text note-text">{{ $documento['observaciones'] }}</div>
        </div>
    @endif
    @if($documento['condiciones'])
        <div class="note">
            <h2 class="note-title"><img class="icon" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjNTU3ZDllIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHJlY3QgeD0iNSIgeT0iNSIgd2lkdGg9IjE0IiBoZWlnaHQ9IjE2IiByeD0iMiIvPjxyZWN0IHg9IjkiIHk9IjMiIHdpZHRoPSI2IiBoZWlnaHQ9IjQiIHJ4PSIxIi8+PHBhdGggZD0iTTggMTFoOE04IDE1aDgiLz48L3N2Zz4=" alt="">Condiciones</h2>
            <div class="text note-text">{{ $documento['condiciones'] }}</div>
        </div>
    @endif
</body>
</html>
