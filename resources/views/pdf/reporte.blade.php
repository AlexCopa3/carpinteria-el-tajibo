<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reporte['titulo'] }} · {{ $negocio['nombre'] }}</title>
    <style>
        @page { margin: 28px 32px 36px; }
        body { font-family: "DejaVu Sans", sans-serif; color: #25354a; font-size: 9px; }
        h1 { font-size: 18px; color: #087e75; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 14px 0 6px; }
        .header { border-bottom: 3px solid #19a88e; padding-bottom: 10px; }
        .muted { color: #55677a; }
        table { width: 100%; border-collapse: collapse; table-layout: auto; margin: 12px 0; }
        thead { display: table-header-group; }
        th { background: #2e61a8; color: #fff; padding: 7px 5px; text-align: left; word-wrap: break-word; }
        td { border-bottom: 1px solid #d8e2ed; padding: 7px 5px; vertical-align: top; word-wrap: break-word; }
        tr { page-break-inside: avoid; }
        .totales td { background: #e8f6f1; font-weight: bold; }
        .nota { padding: 8px; border-left: 3px solid #19a88e; background: #f3f7fa; }
        .footer { position: fixed; bottom: -20px; font-size: 8px; color: #55677a; }
    </style>
</head>
<body>
    <div class="footer">Reporte interno de registros del sistema. Los precios acordados y estimaciones no equivalen a ganancias verificadas.</div>
    <div class="header">
        <h1>{{ $negocio['nombre'] }}</h1>
        <div>{{ $negocio['direccion'] }} · {{ $negocio['telefono'] }}</div>
    </div>
    <h2>{{ $reporte['titulo'] }}</h2>
    <p>
        {{ implode(' · ', $reporte['etiquetas']) }}<br>
        <span class="muted">Generado {{ $reporte['generado_at'] }} · Registros del sistema · Importes en Bs</span>
    </p>
    <table class="totales">
        <tr>
            @foreach($reporte['totales'] as $titulo => $valor)
                <td>{{ $titulo }}<br>{{ $valor }}</td>
            @endforeach
        </tr>
    </table>
    <p class="nota">{{ $reporte['nota'] }}</p>
    <table>
        <thead>
            <tr>
                @foreach($reporte['columnas'] as $titulo)
                    <th>{{ $titulo }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($reporte['filas'] as $fila)
                <tr>
                    @foreach($fila as $celda)
                        <td>{{ $celda }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($reporte['columnas']) }}">No hay registros para estos filtros.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
