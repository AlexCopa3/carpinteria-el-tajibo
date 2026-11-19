<section class="card dashboard-card" aria-labelledby="revision-final-titulo">
    <div class="card-header">
        <h2 id="revision-final-titulo"><i class="fas fa-clipboard-check mr-2" aria-hidden="true"></i>Revisión final y entrega</h2>
    </div>
    <div class="card-body">
        @can('verUsoMateriales', $pedido)
            <div class="mb-3">
                @if($pedido->materiales_validados_at)
                    <span class="record-status is-active"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i>Materiales validados</span>
                @else
                    <span class="record-status is-inactive">Materiales pendientes de validación</span>
                    <p class="small text-muted mt-2">Revisa las cantidades antes de terminar.</p>
                @endif
            </div>
        @endcan
        @if($pedido->estado === 'en_fabricacion')
                @can('validarMateriales', $pedido)
                    @if(!$pedido->materiales_validados_at && ! auth()->user()->can('terminar', $pedido))
                        <div class="table-actions mb-3">
                        <form method="POST" action="{{ route('admin.fabricacion.validar', $pedido) }}" data-cierre-pedido data-cierre-titulo="¿Validar los materiales?" data-cierre-texto="Confirma que revisaste todas las cantidades utilizadas. Esta acción no termina el pedido.">
                            @csrf
                            <input type="hidden" name="version" value="{{ $pedido->version }}">
                            <input type="hidden" name="confirmacion" value="1">
                            <button class="btn btn-primary"><i class="fas fa-clipboard-check mr-2" aria-hidden="true"></i>Validar materiales</button>
                        </form>
                        </div>
                    @endif
                @endcan
            @can('terminar', $pedido)
                <form method="POST" action="{{ route('admin.fabricacion.terminar', $pedido) }}" data-cierre-pedido data-cierre-titulo="¿Terminar la fabricación?" data-cierre-texto="Confirma el cierre de la fabricación del pedido.">
                    @csrf
                    <input type="hidden" name="version" value="{{ $pedido->version }}">
                    <input type="hidden" name="clave_operacion" value="{{ Illuminate\Support\Str::uuid() }}">
                    <input type="hidden" name="confirmacion" value="1">
                    <div class="row">
                        <div class="col-12 form-group">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="confirmar-precio" name="confirmar_precio" value="1" required>
                                <label class="custom-control-label" for="confirmar-precio">{{ ! $pedido->materiales_validados_at && auth()->user()->can('validarMateriales', $pedido) ? 'Revisé los materiales y confirmo el precio acordado:' : 'Confirmo el precio acordado:' }} Bs {{ number_format($pedido->precioVigente(), 2, ',', '.') }}</label>
                            </div>
                        </div>
                        @if(!$pedido->materiales_validados_at)
                            @can('validarMateriales', $pedido)
                                <input type="hidden" name="validar_materiales" value="1">
                            @else
                                <div class="col-12"><p class="small text-muted">Una persona con permiso para validar materiales debe revisarlos antes del cierre.</p></div>
                            @endcan
                        @endif
                    </div>
                    <button class="btn btn-success"><i class="fas fa-check-circle mr-2" aria-hidden="true"></i>Terminar fabricación</button>
                </form>
            @endcan
        @elseif($pedido->estado === 'terminado')
            <p>La fabricación está terminada. Las cantidades y el precio acordado están protegidos.</p>
            @can('entregar', $pedido)
                @if(($resumenPagos ?? null) && Brick\Math\BigDecimal::of($resumenPagos['saldo'])->isPositive())
                    <div class="alert alert-warning" data-saldo-entrega>Saldo pendiente según pagos registrados: Bs {{ App\Support\Decimal::mostrar($resumenPagos['saldo'], 2) }}. Puedes confirmar la entrega y cobrar el saldo después.</div>
                @endif
                <form method="POST" action="{{ route('admin.pedidos.entregar', $pedido) }}" data-cierre-pedido data-cierre-titulo="¿Registrar la entrega?" data-cierre-texto="{{ ($resumenPagos ?? null) && Brick\Math\BigDecimal::of($resumenPagos['saldo'])->isPositive() ? 'Hay saldo pendiente de Bs '.App\Support\Decimal::mostrar($resumenPagos['saldo'], 2).'. La entrega se puede registrar y el saldo seguirá pendiente.' : 'Confirma que el pedido fue entregado. Se registrarán la fecha actual y tu usuario.' }}">
                    @csrf
                    <input type="hidden" name="version" value="{{ $pedido->version }}">
                    <input type="hidden" name="clave_operacion" value="{{ Illuminate\Support\Str::uuid() }}">
                    <input type="hidden" name="confirmacion" value="1">
                    <div class="row"><div class="col-12 col-lg-8 form-group">
                        <label for="observaciones-entrega"><i class="fas fa-comment field-label-icon" aria-hidden="true"></i>Observaciones de entrega <small class="text-muted">(opcional)</small></label>
                        <textarea id="observaciones-entrega" name="observaciones" class="form-control" rows="2" maxlength="2000">{{ old('observaciones') }}</textarea>
                    </div></div>
                    <button class="btn btn-primary"><i class="fas fa-truck mr-2" aria-hidden="true"></i>Registrar entrega</button>
                </form>
            @endcan
        @elseif($pedido->estado === 'entregado')
            <p class="mb-0"><i class="fas fa-truck mr-2" aria-hidden="true"></i>Pedido entregado. Consulta la fecha y el responsable en Seguimiento.</p>
        @else
            <p class="mb-0 text-muted">La revisión final estará disponible al iniciar la fabricación y completar los materiales utilizados.</p>
        @endif
    </div>
</section>
