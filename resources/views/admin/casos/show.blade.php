@extends('adminlte::page')
@section('title', 'Detalle del caso')
@section('content_header')
    <x-page-heading :refined="true" title="Detalle del caso" eyebrow="CASOS HISTÓRICOS" />
@stop
@section('content')
    <x-alerts />
    <div class="module-detail-layout case-detail-layout record-detail-cards">
    @if($caso->motivo_no_elegible)<div class="alert alert-warning"><strong>Datos pendientes para reutilizar este caso:</strong> {{ $caso->motivo_no_elegible }}</div>@endif
    <div class="module-overview-grid">
        <section class="card dashboard-card record-card">
            <x-ficha-heading :title="'Caso '.$caso->id.' · '.$caso->tipo_nombre" subtitle="Características y referencia del mueble." icon="fas fa-chair" />
            <div class="card-body">
                <p><span class="record-status {{ $caso->estado_revision === 'revisado' ? 'is-active' : 'is-inactive' }}">{{ ucfirst($caso->estado_revision) }}</span></p>
                <dl class="record-fields record-fields-boxed mb-0">
                    <div class="record-field"><dt><i class="fas fa-cubes record-value-icon" aria-hidden="true"></i>Material principal</dt><dd><strong>{{ $caso->material_principal_nombre }}</strong></dd></div>
                    <div class="record-field"><dt><i class="fas fa-paint-brush record-value-icon" aria-hidden="true"></i>Acabado</dt><dd>{{ $caso->acabado }}</dd></div>
                    <div class="record-field"><dt><i class="fas fa-ruler-combined record-value-icon" aria-hidden="true"></i>Dimensiones en centímetros</dt><dd>{{ App\Support\Decimal::mostrar($caso->largo_cm) }} × {{ App\Support\Decimal::mostrar($caso->ancho_cm) }} × {{ App\Support\Decimal::mostrar($caso->alto_cm) }}</dd></div>
                    <div class="record-field"><dt><i class="fas fa-chair record-value-icon" aria-hidden="true"></i>Cantidad de muebles</dt><dd><strong>{{ $caso->cantidad_muebles }}</strong></dd></div>
                    <div class="record-field"><dt><i class="fas fa-th-large record-value-icon" aria-hidden="true"></i>Características</dt><dd>{{ $caso->puertas }} puertas · {{ $caso->cajones }} cajones · {{ $caso->divisiones }} divisiones</dd></div>
                    <div class="record-field"><dt><i class="far fa-calendar-alt record-value-icon" aria-hidden="true"></i>Fecha de referencia</dt><dd>{{ $caso->fecha_referencia->format('d/m/Y') }}</dd></div>
                </dl>
                @if($caso->descripcion)<p class="mt-3 mb-0">{{ $caso->descripcion }}</p>@endif
                @if($caso->contexto)<p class="text-muted mt-2 mb-0">{{ $caso->contexto }}</p>@endif
            </div>
        </section>
        <section class="card dashboard-card record-card">
            <x-ficha-heading title="Costos y precio del grupo" subtitle="Importes del grupo completo de muebles." icon="fas fa-calculator" />
            <div class="card-body">
                @can('verCostos', $caso)
                    <dl class="record-fields record-fields-boxed mb-3">
                        @foreach(['costo_materiales_estimado_total'=>'Materiales estimados','mano_obra_estimada_total'=>'Mano de obra estimada','otros_costos_estimados_total'=>'Otros costos estimados','costo_estimado_total'=>'Costo total estimado'] as $campo=>$etiqueta)
                            <div class="record-field"><dt><i class="fas fa-coins record-value-icon" aria-hidden="true"></i>{{ $etiqueta }}</dt><dd>@if($campo === 'costo_estimado_total')<strong>@endif{{ $caso->{$campo} === null ? 'Pendiente' : 'Bs '.App\Support\Decimal::mostrar($caso->{$campo},2) }}@if($campo === 'costo_estimado_total')</strong>@endif</dd></div>
                        @endforeach
                    </dl>
                    <p class="small text-muted">{{ $caso->fuente_costos === 'estimacion_cotizacion' ? 'Estimación de materiales con las cantidades utilizadas y costos unitarios capturados en la cotización; mano de obra y otros costos conservan lo estimado en ella.' : 'Costos registrados del caso.' }} Son estimaciones sujetas a revisión.@if($caso->costo_presupuestado_total !== null) Costo originalmente presupuestado: Bs {{ App\Support\Decimal::mostrar($caso->costo_presupuestado_total,2) }}.@endif</p>
                @endcan
                <div class="pedido-create-total"><span>{{ $caso->origen === 'real' ? 'Precio acordado del grupo' : 'Precio de referencia del grupo' }}</span><strong>Bs {{ App\Support\Decimal::mostrar($caso->precio_historico_total,2) }}</strong></div>
            </div>
        </section>
    </div>
    <section class="card dashboard-card record-card">
        <x-ficha-heading title="Materiales del caso" subtitle="Cantidades e importes del grupo completo, sin multiplicarlos nuevamente." icon="fas fa-cubes" />
        <div class="card-body">
            <p class="small text-muted">{{ $caso->fuente_caracteristicas === 'cotizacion_acordada' ? 'Características acordadas en la cotización y cantidades registradas al fabricar.' : 'Características y cantidades del caso.' }}</p>
            <div class="table-responsive" tabindex="0" role="region" aria-label="Materiales del caso"><table class="table table-hover tajibo-table mb-0"><thead><tr><th>Material</th><th>Unidad</th><th>Cantidad total</th>@can('verCostos',$caso)<th>Costo unitario estimado Bs</th><th>Subtotal estimado Bs</th>@endcan</tr></thead><tbody>
                @foreach($caso->materiales as $material)<tr><td>{{ $material->material_nombre }}@if($material->componente !== 'General')<small class="d-block text-muted">{{ $material->componente }}</small>@endif</td><td>{{ $material->unidad_nombre }}</td><td>{{ App\Support\Decimal::mostrar($material->cantidad_total) }}</td>@can('verCostos',$caso)<td>{{ $material->costo_unitario_estimado === null ? 'Desconocido / pendiente' : App\Support\Decimal::mostrar($material->costo_unitario_estimado,6) }}</td><td>{{ $material->subtotal_estimado === null ? 'Pendiente' : App\Support\Decimal::mostrar($material->subtotal_estimado,2) }}</td>@endcan</tr>@endforeach
            </tbody></table></div>
        </div>
    </section>
    <div class="card dashboard-card record-card case-review-panel"><x-ficha-heading title="Revisión del caso" subtitle="Validación de materiales y referencia económica." icon="fas fa-clipboard-check" /><div class="card-body">
        <div class="row">
            <div class="col-12 col-md-6 mb-3 case-review-item"><h3 class="h6">Materiales</h3><p>{{ $caso->materiales_revisados_at ? $caso->materialesRevisadosPor?->name.' · '.$caso->materiales_revisados_at->format('d/m/Y H:i') : 'Pendientes de revisión' }}</p></div>
            <div class="col-12 col-md-6 mb-3"><h3 class="h6">Precio y referencia económica</h3><p>{{ $caso->precio_revisado_at ? $caso->precioRevisadoPor?->name.' · '.$caso->precio_revisado_at->format('d/m/Y H:i') : 'Pendientes de revisión del precio' }}</p></div>
        </div>
        @if($caso->estado_revision !== 'descartado' && !$caso->motivo_no_elegible)
            <div class="module-actions">
                @can('validarMateriales',$caso) @if(!$caso->materiales_revisados_at)
                    <form method="POST" action="{{ route('admin.casos.revisar',$caso) }}" data-cierre-pedido data-cierre-titulo="¿Validar los materiales del caso?" data-cierre-texto="Confirma que revisaste materiales, unidades y cantidades. El origen del caso no cambia.">@csrf<input type="hidden" name="version" value="{{ $caso->version }}"><input type="hidden" name="accion" value="materiales"><input type="hidden" name="confirmacion" value="1"><button class="btn btn-primary"><i class="fas fa-check-circle mr-2" aria-hidden="true"></i>Validar materiales del caso</button></form>
                @endif @endcan
                @can('validarPrecio',$caso) @if(!$caso->precio_revisado_at)
                    <form method="POST" action="{{ route('admin.casos.revisar',$caso) }}" data-cierre-pedido data-cierre-titulo="¿Validar el precio del caso?" data-cierre-texto="Se valida la referencia económica. Los costos estimados seguirán identificados como estimaciones y el origen no cambia.">@csrf<input type="hidden" name="version" value="{{ $caso->version }}"><input type="hidden" name="accion" value="precio"><input type="hidden" name="confirmacion" value="1"><button class="btn btn-success"><i class="fas fa-check-double mr-2" aria-hidden="true"></i>Validar precio del caso</button></form>
                @endif @endcan
            </div>
        @endif
        @can('descartar',$caso) @if($caso->estado_revision !== 'descartado')
            <details class="mt-4"><summary>Descartar como referencia</summary><form method="POST" action="{{ route('admin.casos.revisar',$caso) }}" class="mt-3" data-cierre-pedido data-cierre-titulo="¿Descartar este caso?" data-cierre-texto="Se conservarán el caso y sus materiales; quedará descartado como referencia.">@csrf<input type="hidden" name="version" value="{{ $caso->version }}"><input type="hidden" name="accion" value="descartar"><input type="hidden" name="confirmacion" value="1"><div class="row"><div class="col-12 col-lg-8 form-group"><label for="observaciones"><i class="fas fa-comment field-label-icon" aria-hidden="true"></i>Motivo del descarte</label><textarea class="form-control" id="observaciones" name="observaciones" rows="2" required minlength="5" maxlength="2000">{{ old('observaciones') }}</textarea></div></div><button class="btn btn-danger"><i class="fas fa-ban mr-2" aria-hidden="true"></i>Descartar caso</button></form></details>
        @endif @endcan
        <p class="small text-muted mt-3 mb-0">La revisión conserva el origen y los datos históricos. Busca casos similares mientras describes un mueble en una cotización.</p>
    </div></div>
    <div class="module-actions mb-4"><a class="btn btn-cancel" href="{{ request('desde') === 'pedido' && $caso->detallePedido && auth()->user()->can('view', $caso->detallePedido->pedido) ? route('admin.pedidos.show', $caso->detallePedido->pedido) : route('admin.casos.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>{{ request('desde') === 'pedido' && $caso->detallePedido && auth()->user()->can('view', $caso->detallePedido->pedido) ? 'Volver al pedido' : 'Volver al listado' }}</a>@if($caso->detalle_pedido_id && auth()->user()->can('view',$caso->detallePedido->pedido))<a class="btn btn-info" href="{{ route('admin.pedidos.show',$caso->detallePedido->pedido_id) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver pedido de origen</a>@endif</div>
</div>
@stop
