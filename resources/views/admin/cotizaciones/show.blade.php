@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" :title="'Cotización #'.$cotizacion->id" eyebrow="OPERACIONES / COTIZACIONES" :subtitle="$cotizacion->estado === 'rechazada' ? 'Cotización rechazada por el cliente.' : ($cotizacion->aprobado_at ? 'Cotización aprobada.' : 'Estimación preliminar; pendiente de aprobación.')">
        <a class="btn btn-return" href="{{ (request('desde') === 'pedido' && $cotizacion->pedido && auth()->user()->can('view', $cotizacion->pedido) ? route('admin.pedidos.show', $cotizacion->pedido) : route('admin.cotizaciones.index')) }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver</a>
        @can('update', $cotizacion)<a class="btn btn-success" href="{{ route('admin.cotizaciones.edit', [$cotizacion, 'desde' => request()->query('desde') === 'pedido' ? 'pedido' : null]) }}"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Editar</a>@endcan
        @if($cotizacion->estado === 'aprobada' && $cotizacion->aprobado_at)<a class="btn btn-history" href="{{ route('admin.cotizaciones.pdf', $cotizacion) }}" target="_blank" rel="noopener"><i class="fas fa-file-pdf mr-2" aria-hidden="true"></i>Ver PDF</a>@endif
    @if($cotizacion->pedido)
        @can('view',$cotizacion->pedido)<a class="btn btn-info" href="{{ route('admin.pedidos.show',$cotizacion->pedido) }}"><i class="fas fa-hammer mr-2" aria-hidden="true"></i>Ver pedido</a>@endcan
    @elseif($cotizacion->estado === 'aprobada' && $cotizacion->aprobado_at)
        @can('create',App\Models\Pedido::class)<a class="btn btn-primary" href="{{ route('admin.pedidos.create',['cotizacion'=>$cotizacion->id]) }}"><i class="fas fa-plus mr-2" aria-hidden="true"></i>Crear pedido</a>@endcan
    @endif
    </x-page-heading>
@stop
@section('content')
    <x-alerts />
    @if($cotizacion->estado === 'rechazada')
        <div class="alert alert-secondary">
            <strong>El cliente no aceptó esta cotización.</strong>
            <p class="mb-0">{{ $rechazo?->datos_nuevos['motivo'] ?? 'Se conserva como referencia del presupuesto presentado.' }}</p>
            @if($rechazo)<small>{{ $rechazo->usuario?->name ?? 'Sin responsable registrado' }} · {{ $rechazo->created_at->format('d/m/Y H:i') }}</small>@endif
        </div>
    @endif
    @if($cotizacion->pedido && $cotizacion->pedido->cotizacion_aceptada_version !== $cotizacion->version)
        @include('admin.pedidos._aceptar-cotizacion')
    @endif
    <div class="row">
        <div class="col-12 col-xl-10 quote-show-layout">
            <div class="record-detail-cards">
                <div class="quote-show-overview">
                    <section class="card dashboard-card record-card">
                        <x-ficha-heading title="Datos de la cotización" subtitle="Fechas, responsable y estado de la estimación." icon="fas fa-file-invoice-dollar" />
                        <div class="card-body">
                            <dl class="record-fields record-fields-boxed">
                                <div class="record-field">
                                    <dt><i class="fas fa-calendar-alt record-value-icon" aria-hidden="true"></i>Fecha de cotización</dt>
                                    <dd>{{ $cotizacion->fecha->format('d/m/Y') }}</dd>
                                </div>
                                <div class="record-field">
                                    <dt><i class="fas fa-clock record-value-icon" aria-hidden="true"></i>Última actualización</dt>
                                    <dd>{{ $cotizacion->updated_at?->format('d/m/Y H:i') ?? 'Sin actualizaciones registradas' }}</dd>
                                </div>
                                <div class="record-field">
                                    <dt><i class="fas fa-user record-value-icon" aria-hidden="true"></i>Responsable</dt>
                                    <dd>{{ $cotizacion->responsable->name }}</dd>
                                </div>
                                <div class="record-field">
                                    <dt><i class="fas fa-info-circle record-value-icon" aria-hidden="true"></i>Estado</dt>
                                    <dd><span class="record-status {{ $cotizacion->estado === 'aprobada' ? 'is-active' : 'is-inactive' }}">{{ $cotizacion->estado_nombre }}</span></dd>
                                </div>
                                <div class="record-field">
                                    <dt><i class="fas fa-code-branch record-value-icon" aria-hidden="true"></i>Versión</dt>
                                    <dd>{{ $cotizacion->version }}</dd>
                                </div>
                                @if($cotizacion->observaciones)
                                    <div class="record-field record-field-wide">
                                        <dt><i class="fas fa-comment-alt record-value-icon" aria-hidden="true"></i>Observaciones</dt>
                                        <dd>{{ $cotizacion->observaciones }}</dd>
                                    </div>
                                @endif
                                @if($cotizacion->condiciones)
                                    <div class="record-field record-field-wide">
                                        <dt><i class="fas fa-clipboard-list record-value-icon" aria-hidden="true"></i>Condiciones para el cliente</dt>
                                        <dd>{{ $cotizacion->condiciones }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </section>
                    <section class="card dashboard-card record-card">
                        <x-ficha-heading title="Resumen de importes" subtitle="Costos estimados y precio de la cotización." icon="fas fa-coins" />
                        <div class="card-body">
                            <dl class="record-fields record-fields-boxed mb-0">
                                @if(App\Support\Acceso::permite(auth()->user(), 'costos.consultar'))
                                    <div class="record-field">
                                        <dt><i class="fas fa-calculator record-value-icon" aria-hidden="true"></i>Costo total aplicado</dt>
                                        <dd><strong>Bs {{ App\Support\Decimal::mostrar($cotizacion->costo_total, 2) }}</strong></dd>
                                    </div>
                                @endif
                                @if(App\Support\Acceso::permite(auth()->user(), 'margenes.consultar'))
                                    <div class="record-field">
                                        <dt><i class="fas fa-percentage record-value-icon" aria-hidden="true"></i>Ganancia sobre el costo</dt>
                                        <dd>{{ $cotizacion->recargo_porcentaje === null ? 'Sin configurar · precio manual' : App\Support\Decimal::mostrar($cotizacion->recargo_porcentaje).' %' }}</dd>
                                    </div>
                                    <div class="record-field">
                                        <dt><i class="fas fa-coins record-value-icon" aria-hidden="true"></i>Precio sugerido</dt>
                                        <dd>Bs {{ App\Support\Decimal::mostrar($cotizacion->precio_sugerido, 2) }}</dd>
                                    </div>
                                @endif
                                @if($cotizacion->aprobado_at)
                                    <div class="record-field">
                                        <dt><i class="fas fa-check-circle record-value-icon" aria-hidden="true"></i>Total aprobado</dt>
                                        <dd class="text-success"><strong>Bs {{ App\Support\Decimal::mostrar($cotizacion->precio_final, 2) }}</strong></dd>
                                    </div>
                                    <div class="record-field record-field-wide">
                                        <dt><i class="fas fa-user-check record-value-icon" aria-hidden="true"></i>Validado y aprobado por</dt>
                                        <dd>{{ $cotizacion->aprobador->name }} · {{ $cotizacion->aprobado_at->format('d/m/Y H:i') }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </section>
                </div>
                @foreach($cotizacion->detalles as $detalle)
                    <section class="card dashboard-card record-card" id="mueble-{{ $detalle->id }}">
                        <x-ficha-heading :title="$detalle->posicion.'. '.$detalle->tipo_nombre.' · '.$detalle->cantidad_muebles.' '.($detalle->cantidad_muebles == 1 ? 'mueble' : 'muebles')" subtitle="Características, materiales y costos del mueble." icon="fas fa-chair" />
                        <div class="card-body">
                            <dl class="record-fields record-fields-boxed">
                                <div class="record-field">
                                    <dt><i class="fas fa-ruler-combined record-value-icon" aria-hidden="true"></i>Dimensiones (largo × ancho × alto)</dt>
                                    <dd>{{ App\Support\Decimal::mostrar($detalle->largo_cm) }} × {{ App\Support\Decimal::mostrar($detalle->ancho_cm) }} × {{ App\Support\Decimal::mostrar($detalle->alto_cm) }} cm</dd>
                                </div>
                                <div class="record-field">
                                    <dt><i class="fas fa-cubes record-value-icon" aria-hidden="true"></i>Material principal</dt>
                                    <dd><strong>{{ $detalle->material_principal_nombre }}</strong></dd>
                                </div>
                                <div class="record-field">
                                    <dt><i class="fas fa-paint-brush record-value-icon" aria-hidden="true"></i>Acabado</dt>
                                    <dd>{{ $detalle->acabado }}</dd>
                                </div>
                                <div class="record-field">
                                    <dt><i class="fas fa-th-large record-value-icon" aria-hidden="true"></i>Componentes</dt>
                                    <dd>Puertas: {{ $detalle->puertas }} · Cajones: {{ $detalle->cajones }} · Divisiones: {{ $detalle->divisiones }}</dd>
                                </div>
                                @if($detalle->observaciones)
                                    <div class="record-field record-field-wide">
                                        <dt><i class="fas fa-comment-alt record-value-icon" aria-hidden="true"></i>Observaciones del mueble</dt>
                                        <dd>{{ $detalle->observaciones }}</dd>
                                    </div>
                                @endif
                            </dl>
                            @if(App\Support\Acceso::permite(auth()->user(), 'costos.consultar'))
                                <div class="table-responsive" tabindex="0" role="region" aria-label="Materiales cotizados">
                                    <table class="table table-bordered table-sm tajibo-table">
                                        <thead><tr><th scope="col">Material / componente</th><th scope="col">Por mueble</th><th scope="col">Nominal total</th><th scope="col">Aplicado total</th><th scope="col">Costo unidad Bs</th><th scope="col">Subtotal Bs</th></tr></thead>
                                        <tbody>
                                            @foreach($detalle->materiales as $material)
                                                <tr>
                                                    <td><strong class="categoria-nombre">{{ $material->material_nombre }}</strong> · {{ $material->componente }}<br><small>{{ $material->unidad_nombre }} · {{ $material->estado_costo === 'pendiente' ? 'Desconocido: revisar' : (['conocido' => 'Conocido', 'pendiente' => 'Pendiente'][$material->estado_costo] ?? 'Histórico') }}</small>
                                                        @if($material->motivo_ajuste_cantidad)<p class="small quote-text mb-0">Ajuste: {{ $material->motivo_ajuste_cantidad }}</p>@endif
                                                    </td>
                                                    <td>{{ App\Support\Decimal::mostrar($material->cantidad_unitaria) }}</td>
                                                    <td>{{ App\Support\Decimal::mostrar($material->cantidad_total_nominal) }}</td>
                                                    <td>{{ App\Support\Decimal::mostrar($material->cantidad_total_aplicada) }}</td>
                                                    <td>{{ App\Support\Decimal::mostrar($material->costo_unitario_estimado, 6) }}</td>
                                                    <td>{{ App\Support\Decimal::mostrar($material->subtotal_aplicado, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <dl class="record-fields record-fields-boxed quote-show-costs mt-3">
                                    <div class="record-field">
                                        <dt><i class="fas fa-user-cog record-value-icon" aria-hidden="true"></i>Mano de obra por mueble</dt>
                                        <dd>Bs {{ App\Support\Decimal::mostrar($detalle->mano_obra_unitaria, 2) }}</dd>
                                    </div>
                                    <div class="record-field">
                                        <dt><i class="fas fa-calculator record-value-icon" aria-hidden="true"></i>Mano de obra nominal</dt>
                                        <dd>Bs {{ App\Support\Decimal::mostrar($detalle->mano_obra_total_nominal, 2) }}</dd>
                                    </div>
                                    <div class="record-field">
                                        <dt><i class="fas fa-tools record-value-icon" aria-hidden="true"></i>Mano de obra aplicada</dt>
                                        <dd>Bs {{ App\Support\Decimal::mostrar($detalle->mano_obra_total_aplicada, 2) }}</dd>
                                    </div>
                                    <div class="record-field">
                                        <dt><i class="fas fa-receipt record-value-icon" aria-hidden="true"></i>Otros costos (total del detalle)</dt>
                                        <dd>Bs {{ App\Support\Decimal::mostrar($detalle->otros_costos_total, 2) }}</dd>
                                    </div>
                                </dl>
                                @if($detalle->motivo_ajuste_mano_obra)<p class="quote-text mt-2">Justificación: {{ $detalle->motivo_ajuste_mano_obra }}</p>@endif
                                <p class="mb-1"><strong>Costo aplicado: Bs {{ App\Support\Decimal::mostrar($detalle->costo_total, 2) }}</strong></p>
                                @if($detalle->precio_propuesto !== null)<p class="mb-1">Precio preliminar propuesto: Bs {{ App\Support\Decimal::mostrar($detalle->precio_propuesto, 2) }}</p>@endif
                            @endif
                            @if(config('similitud.habilitado') && App\Support\Acceso::permite(auth()->user(),'casos.consultar') && App\Support\Acceso::permite(auth()->user(),'costos.consultar'))
                                <div class="mt-3"><a class="btn btn-info" href="{{ route('admin.similitud.show',[$cotizacion,$detalle]) }}"><i class="fas fa-search mr-2" aria-hidden="true"></i>Buscar casos similares</a></div>
                            @endif
                            @if($detalle->precio_final !== null || ($cotizacion->pedido && $detalle->precio_sugerido !== null && App\Support\Acceso::permite(auth()->user(), 'margenes.consultar')))
                                <div class="quote-detail-price mt-3">
                                    <span>Precio final estimado{{ $detalle->precio_final === null ? ' · Pendiente de aprobación' : '' }}</span>
                                    <strong>Bs {{ App\Support\Decimal::mostrar($detalle->precio_final ?? $detalle->precio_sugerido, 2) }}</strong>
                                </div>
                            @endif
                        </div>
                    </section>
                @endforeach

                <div class="quote-show-ending">
                    @can('approve', $cotizacion)
                        @can('validatePrice', $cotizacion)
                            <div class="card dashboard-card form-panel form-themed form-action-create">
                                <div class="card-header"><h2 class="card-title"><i class="fas fa-check-circle mr-2" aria-hidden="true"></i>Revisar y aprobar precios</h2></div>
                                <div class="card-body">
                                    @if($cotizacion->costo_total === null)<div class="alert alert-warning">Hay costos desconocidos. Edita la cotización y revísalos antes de aprobar.</div>@endif
                                    <p>Revisa los materiales, los ajustes justificados y el precio total de cada detalle. Una sola confirmación registra la validación y aprobación; después no se podrá modificar esta cotización.</p>
                                    <form method="POST" action="{{ route('admin.cotizaciones.aprobar', [$cotizacion, 'desde' => request()->query('desde') === 'pedido' ? 'pedido' : null]) }}">
                                        @csrf
                                        <input type="hidden" name="version" value="{{ $cotizacion->version }}">
                                        <div class="row">
                                            @foreach($cotizacion->detalles as $detalle)
                                                <div class="col-12 col-md-6"><div class="form-group">
                                                    <label for="precio-{{ $detalle->id }}"><i class="fas fa-coins field-label-icon" aria-hidden="true"></i>Precio final total · {{ $detalle->tipo_nombre }} (Bs)</label>
                                                    <input id="precio-{{ $detalle->id }}" name="precios[{{ $detalle->id }}]" value="{{ App\Support\Decimal::entrada(old('precios.'.$detalle->id, $detalle->precio_sugerido ?? $detalle->precio_propuesto)) }}" class="form-control" inputmode="decimal" required>
                                                    @error('precios.'.$detalle->id)<span class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                                </div></div>
                                            @endforeach
                                        </div>
                                        <button class="btn btn-primary" type="submit" @disabled($cotizacion->costo_total === null)><i class="fas fa-check-circle mr-2" aria-hidden="true"></i>Validar y aprobar</button>
                                    </form>
                                </div>
                            </div>
                        @endcan
                    @endcan
                    @can('reject', $cotizacion)
                        <section class="card dashboard-card record-card">
                            <x-ficha-heading title="Respuesta del cliente" subtitle="Registra si el cliente decidió no aceptar el presupuesto." icon="fas fa-comment-dots" />
                            <div class="card-body">
                                <details class="quote-options" @if($errors->has('motivo')) open @endif>
                                    <summary>Marcar como rechazada</summary>
                                    <form method="POST" action="{{ route('admin.cotizaciones.rechazar', $cotizacion) }}" class="mt-3" data-cierre-pedido data-cierre-titulo="¿Marcar como rechazada?" data-cierre-texto="Se conservará el presupuesto y no podrá convertirse en pedido.">
                                        @csrf
                                        <input type="hidden" name="version" value="{{ $cotizacion->version }}">
                                        <input type="hidden" name="confirmacion" value="1">
                                        <div class="form-group"><label for="motivo-rechazo"><i class="fas fa-comment field-label-icon" aria-hidden="true"></i>Motivo del rechazo</label><textarea id="motivo-rechazo" name="motivo" class="form-control @error('motivo') is-invalid @enderror" minlength="5" maxlength="2000" rows="2" required>{{ old('motivo') }}</textarea>@error('motivo')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
                                        <button type="submit" class="btn btn-cancel"><i class="fas fa-ban mr-2" aria-hidden="true"></i>Registrar rechazo</button>
                                    </form>
                                </details>
                            </div>
                        </section>
                    @endcan
                    <x-auditoria :registro="$cotizacion" />
                </div>
            </div>

        </div>
    </div>
@stop
