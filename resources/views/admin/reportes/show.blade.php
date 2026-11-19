@extends('adminlte::page')
@section('title', $reporte['titulo'])

@section('content_header')
    <x-page-heading :refined="true" :title="$reporte['titulo']" eyebrow="ADMINISTRACIÓN / REPORTES" subtitle="Registros del sistema según los filtros seleccionados.">
        <a class="btn btn-history" target="_blank" rel="noopener" href="{{ route('admin.reportes.pdf', ['tipo' => $reporte['tipo'], ...Illuminate\Support\Arr::except($reporte['filtros'], 'page')]) }}">
            <i class="fas fa-file-pdf mr-2" aria-hidden="true"></i>PDF imprimible
        </a>
    </x-page-heading>
@stop

@section('content')
    <x-alerts />
    <div class="reports-layout">
        <section @class(['card dashboard-card reports-filter-card', 'reports-single-filter' => $reporte['tipo'] === 'saldos']) aria-labelledby="reportes-filtros">
            <div class="record-heading">
                <span class="record-heading-icon"><i class="fas fa-filter" aria-hidden="true"></i></span>
                <div><h2 id="reportes-filtros">Filtros del reporte</h2><p>Selecciona los registros que quieres consultar.</p></div>
            </div>
            <div class="card-body">
                <form method="GET" class="reports-filter-grid">
                    @if($reporte['tipo'] !== 'saldos')
                        <div class="form-group">
                            <label for="desde"><i class="far fa-calendar-alt field-label-icon" aria-hidden="true"></i>Desde</label>
                            <input id="desde" name="desde" type="date" value="{{ old('desde', $reporte['filtros']['desde']) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="hasta"><i class="far fa-calendar-alt field-label-icon" aria-hidden="true"></i>Hasta</label>
                            <input id="hasta" name="hasta" type="date" value="{{ old('hasta', $reporte['filtros']['hasta']) }}" class="form-control">
                        </div>
                    @endif
                    @if($reporte['estados'])
                        <div class="form-group">
                            <label for="estado"><i class="fas fa-clipboard-check field-label-icon" aria-hidden="true"></i>{{ $reporte['tipo'] === 'saldos' ? 'Estado de cobro' : 'Estado' }}</label>
                            <select id="estado" name="estado" class="form-control">
                                <option value="">Todos</option>
                                @foreach($reporte['estados'] as $valor => $nombre)
                                    <option value="{{ $valor }}" @selected(($reporte['filtros']['estado'] ?? '') === $valor)>{{ $nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($reporte['tipo'] === 'pagos')
                        <div class="form-group">
                            <label for="medio"><i class="fas fa-money-bill-wave field-label-icon" aria-hidden="true"></i>Medio de pago</label>
                            <select id="medio" name="medio" class="form-control">
                                <option value="">Todos</option>
                                <option value="efectivo" @selected(($reporte['filtros']['medio'] ?? '') === 'efectivo')>Efectivo</option>
                                <option value="qr" @selected(($reporte['filtros']['medio'] ?? '') === 'qr')>QR</option>
                            </select>
                        </div>
                    @endif
                    <div class="form-group table-actions reports-filter-actions">
                        <button class="btn btn-primary"><i class="fas fa-search mr-2" aria-hidden="true"></i>Consultar</button>
                        <a class="btn btn-clear" href="{{ route('admin.reportes.show', $reporte['tipo']) }}"><i class="fas fa-eraser mr-2" aria-hidden="true"></i>Limpiar</a>
                    </div>
                </form>
            </div>
        </section>
        <div class="reports-meta">
                <p>{{ implode(' · ', $reporte['etiquetas']) }} · Generado: {{ $reporte['generado_at'] }}</p>
        </div>
                <div class="reports-totals" aria-label="Resumen del reporte">
                    @foreach($reporte['totales'] as $titulo => $valor)
                        <div class="reports-total">
                            <div>
                                <h2>{{ $titulo }}</h2>
                                <p data-reporte-total>{{ $valor }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="reports-note" role="note"><i class="fas fa-info-circle" aria-hidden="true"></i><span>{{ $reporte['nota'] }}</span></div>
        <section class="card dashboard-card reports-results" aria-labelledby="reportes-resultados">
            <div class="record-heading">
                <span class="record-heading-icon"><i class="fas fa-list-ul" aria-hidden="true"></i></span>
                <div><h2 id="reportes-resultados">Detalle del reporte</h2><p>{{ $filas->total() }} {{ $filas->total() === 1 ? 'registro encontrado' : 'registros encontrados' }}</p></div>
            </div>
                <div class="table-responsive" tabindex="0" role="region" aria-label="Detalle del reporte">
                    <table class="table table-hover tajibo-table w-auto mb-0">
                        <caption class="sr-only">{{ $reporte['titulo'] }}</caption>
                        <thead>
                            <tr>
                                @foreach($reporte['columnas'] as $titulo)
                                    <th scope="col">{{ $titulo }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($filas as $fila)
                                <tr>
                                    @foreach($fila as $celda)
                                        <td @class(['reports-primary-cell' => $loop->first])>{{ $celda }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($reporte['columnas']) }}"><x-empty-state title="Sin resultados" description="No hay registros para estos filtros. Prueba otro período o limpia los filtros." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            <div class="reports-results-footer">
                {{ $filas->links() }}
                <p class="small text-muted">Los totales incluyen todos los resultados filtrados, no solo esta página. El PDF exporta el mismo filtro completo (hasta 500 registros).</p>
            </div>
        </section>
        <div class="module-actions">
                <a class="btn btn-cancel" href="{{ route('admin.reportes.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver a reportes</a>
        </div>
    </div>
@stop
