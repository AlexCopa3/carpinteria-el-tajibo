@if($resultado['estado'] !== 'encontrados')
    <div class="alert alert-secondary" role="status">
        {{ $resultado['mensaje'] }}
        @if($resultado['fuera_cobertura'])<span class="d-block mt-1">Fuera de cobertura: {{ implode(', ', $resultado['fuera_cobertura']) }}.</span>@endif
    </div>
@endif
@if($resultado['resultados'])
    <div class="similar-results">
        <div class="similar-results-heading">
            <div><h3><i class="fas fa-search mr-2" aria-hidden="true"></i>Casos que se parecen a tu mueble</h3><p>Compara {{ count($resultado['resultados']) }} {{ count($resultado['resultados']) === 1 ? 'referencia' : 'referencias' }}.@if($enFormulario ?? false) Puedes editar la propuesta. Revisa cantidades si cambian las medidas; mano de obra por mueble y otros costos como total fijo.@else Los datos corresponden al grupo original.@endif</p></div>
        </div>
        <div class="similar-cases-grid">
            @foreach($resultado['resultados'] as $referencia)
                @php
                    $indice = $loop->index;
                    $propuesta = ($propuestas ?? [])[$indice] ?? null;
                    $principal = $propuesta ? $propuesta['principal'] : $loop->first;
                @endphp
                <section class="similar-case {{ $principal ? 'is-principal' : '' }}" aria-label="Caso similar {{ $referencia['id'] }}" data-caso-propuesta="{{ $indice }}">
                    <div class="similar-case-heading">
                        <span class="similar-case-rank"><i class="fas {{ $principal ? 'fa-star' : 'fa-layer-group' }}" aria-hidden="true"></i>{{ $principal ? 'Recomendación principal' : 'Alternativa '.$indice }}</span>
                        <span class="similar-case-score"><strong>{{ App\Support\Decimal::mostrar($referencia['porcentaje']) }} %</strong><small>de similitud</small></span>
                    </div>
                    <div class="similar-case-body">
                        <div class="similar-case-title"><span class="similar-case-icon"><i class="fas fa-chair" aria-hidden="true"></i></span><div><h4>{{ $referencia['tipo'] }}</h4><span class="small text-muted">Caso {{ $referencia['id'] }} · {{ $referencia['cantidad_muebles'] }} {{ $referencia['cantidad_muebles'] == 1 ? 'mueble original' : 'muebles originales' }}</span></div></div>
                        <div class="similar-case-tags"><span title="Material principal"><i class="fas fa-tree" aria-hidden="true"></i>{{ $referencia['material_principal'] }}</span><span title="Acabado"><i class="fas fa-paint-brush" aria-hidden="true"></i>{{ $referencia['acabado'] }}</span></div>
                        @if($propuesta)
                            <div class="similar-case-cost">
                                <span>Costo estimado para {{ $propuesta['cantidad_solicitada'] }} {{ $propuesta['cantidad_solicitada'] == 1 ? 'mueble' : 'muebles' }}</span>
                                <strong data-costo-actual>{{ $propuesta['costo_actual_total'] === null ? 'Pendiente de costos' : 'Bs '.App\Support\Decimal::mostrar($propuesta['costo_actual_total'], 2) }}</strong>
                                <small>Con precios actuales del catálogo. El precio de venta se define en la cotización.</small>
                            </div>
                            <div class="similar-case-comparison" aria-label="Comparación económica de la propuesta">
                                <span>Con costos del caso<strong data-costo-historico-adaptado>{{ $propuesta['costo_historico_adaptado_total'] === null ? 'Pendiente de costos' : 'Bs '.App\Support\Decimal::mostrar($propuesta['costo_historico_adaptado_total'], 2) }}</strong></span>
                            </div>
                        @else
                            <div class="similar-case-cost"><span>Costo estimado del grupo original</span><strong>Bs {{ App\Support\Decimal::mostrar($referencia['costo_total'], 2) }}</strong><small>{{ $referencia['cantidad_muebles'] }} {{ $referencia['cantidad_muebles'] == 1 ? 'mueble' : 'muebles' }} en este antecedente.</small></div>
                        @endif
                        <details class="similar-case-details"><summary><i class="fas fa-ruler-combined mr-2" aria-hidden="true"></i>Ver características y diferencias</summary>
                            <div class="table-responsive mt-2" tabindex="0" role="region" aria-label="Diferencias con el caso {{ $referencia['id'] }}"><table class="table table-sm tajibo-table similar-case-table"><thead><tr><th>Dato</th><th>Solicitado</th><th>Referencia</th></tr></thead><tbody>@foreach($referencia['diferencias'] as $diferencia)<tr class="{{ !$diferencia['coincide'] ? 'similar-case-different' : '' }}"><th>{{ $diferencia['nombre'] }}</th><td>{{ $diferencia['solicitado'] }}</td><td>{{ $diferencia['referencia'] }}@if(!$diferencia['coincide'])<i class="fas fa-exchange-alt ml-1" aria-label="Diferente"></i>@endif</td></tr>@endforeach</tbody></table></div>
                        </details>
                        <details class="similar-case-details"><summary><i class="fas fa-boxes mr-2" aria-hidden="true"></i>Materiales y costos del caso</summary>
                            <p class="small text-muted mt-3">Cantidades y costos del grupo original · Revisión: {{ ucfirst($referencia['revision']) }}.</p>
                            <ul class="similar-case-materials">@foreach($referencia['materiales'] as $material)<li><span>{{ $material['nombre'] }}@if($material['inactivo'])<small class="d-block text-danger">Material inactivo: requiere revisión.</small>@endif</span><strong>{{ App\Support\Decimal::mostrar($material['cantidad']) }} {{ $material['unidad'] }}</strong></li>@endforeach</ul>
                            <dl class="similar-case-totals">@foreach(['costo_materiales'=>'Materiales estimados','mano_obra'=>'Mano de obra estimada','otros_costos'=>'Otros costos estimados','costo_total'=>'Costo total estimado'] as $campo=>$titulo)<div><dt>{{ $titulo }}</dt><dd>Bs {{ App\Support\Decimal::mostrar($referencia[$campo], 2) }}</dd></div>@endforeach<div class="similar-case-history-price"><dt>Precio histórico del grupo</dt><dd>Bs {{ App\Support\Decimal::mostrar($referencia['precio_grupo'], 2) }}</dd></div></dl>
                        </details>
                        @if($propuesta)
                            @if($propuesta['lote_diferente'])<p class="similar-case-note" data-aviso-lote>El caso agrupaba {{ $propuesta['cantidad_original'] }} {{ $propuesta['cantidad_original'] == 1 ? 'mueble' : 'muebles' }} y ahora solicitas {{ $propuesta['cantidad_solicitada'] }}. Revisa los ahorros por trabajo conjunto; otros costos se mantienen como total fijo.</p>@endif
                            @if($propuesta['redondeadas'])<p class="similar-case-note">Cantidades por mueble redondeadas hacia arriba según la unidad; revisa sobrantes y trabajo conjunto.</p>@endif
                            @foreach($propuesta['advertencias'] as $advertencia)<p class="small text-danger mb-2" role="note">{{ $advertencia }}</p>@endforeach
                        @endif
                        @if($referencia['acabado'] === 'Otros')<p class="similar-case-note">Revisa los detalles del acabado Otros; las observaciones no se interpretan automáticamente.</p>@endif
                        <div class="similar-case-actions">
                            <span class="similar-case-selected" data-propuesta-seleccionada hidden><i class="fas fa-check-circle mr-1" aria-hidden="true"></i>Propuesta seleccionada</span>
                            @if($propuesta)<button type="button" class="btn {{ $principal ? 'btn-success' : 'btn-primary' }}" data-usar-caso="{{ $indice }}" @disabled(!$propuesta['aplicable'])><i class="fas fa-check mr-2" aria-hidden="true"></i>{{ $principal ? 'Usar propuesta principal' : 'Usar este caso' }}</button>@endif
                            <a class="similar-case-link" href="{{ route('admin.casos.show', $referencia['id']) }}" @if($enFormulario ?? false) target="_blank" rel="noopener" @endif><i class="fas fa-eye mr-1" aria-hidden="true"></i>Ver caso {{ $referencia['id'] }}@if($enFormulario ?? false)<i class="fas fa-external-link-alt ml-1" aria-hidden="true"></i><span class="sr-only"> (abre en otra pestaña)</span>@endif</a>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
@endif
@if($resultado['perfil'])<details class="quote-options mt-3 mb-4"><summary><i class="fas fa-sliders-h mr-2" aria-hidden="true"></i>Cómo se comparan estas referencias</summary><p class="small text-muted mt-3 mb-0">Perfil {{ $resultado['perfil']['version'] }} · mínimo {{ $resultado['perfil']['umbral'] }} % · hasta {{ $resultado['perfil']['k'] }} casos. @php($pesos = config('similitud.pesos')) Dimensiones {{ $pesos['largo_cm'] + $pesos['ancho_cm'] + $pesos['alto_cm'] }} % (largo {{ $pesos['largo_cm'] }} %, ancho {{ $pesos['ancho_cm'] }} %, alto {{ $pesos['alto_cm'] }} %), material principal {{ $pesos['material_principal_id'] }} %, acabado {{ $pesos['acabado'] }} %, puertas {{ $pesos['puertas'] }} %, cajones {{ $pesos['cajones'] }} %, divisiones {{ $pesos['divisiones'] }} % y cantidad {{ $pesos['cantidad_muebles'] }} %. Los rangos están fijados por tipo y no se extrapolan materiales ni precios.</p></details>@endif
