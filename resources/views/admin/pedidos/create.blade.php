@extends('adminlte::page')

@section('title', 'Crear pedido')
@section('content_header')
    <x-page-heading :refined="true" title="Crear pedido" eyebrow="OPERACIONES / PEDIDOS" subtitle="Completa los datos del cliente y coordina la entrega de los muebles." />
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-12 col-xl-10 pedido-create-layout">
            <div class="phase-notice pedido-create-notice">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <span>Confirma que el cliente aceptó la cotización #{{ $cotizacion->id }}. Los muebles y precios aprobados se conservarán en el pedido.</span>
            </div>
            <form method="POST" action="{{ route('admin.pedidos.store') }}" data-crear-pedido>
                @csrf
                <input type="hidden" name="cotizacion_id" value="{{ $cotizacion->id }}">
                <input type="hidden" name="clave_registro" value="{{ old('clave_registro', $clave) }}">
                <div class="pedido-create-grid">
                    <div class="pedido-create-main">
                        <section class="card dashboard-card form-panel form-themed form-action-create">
                            <div class="card-header"><h2 class="card-title"><i class="fas fa-user mr-2" aria-hidden="true"></i>Cliente del pedido</h2></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="cliente_id"><i class="fas fa-address-book field-label-icon" aria-hidden="true"></i>Cliente</label>
                                    <select id="cliente_id" name="cliente_id" class="form-control @error('cliente_id') is-invalid @enderror" data-cliente-existente aria-describedby="cliente-help @error('cliente_id') cliente-error @enderror" @error('cliente_id') aria-invalid="true" @enderror>
                                        <option value="">Registrar cliente nuevo</option>
                                        @foreach($clientes as $cliente)
                                            <option value="{{ $cliente->id }}" @selected((string) old('cliente_id') === (string) $cliente->id)>{{ $cliente->nombre }} · {{ $cliente->telefono }}</option>
                                        @endforeach
                                    </select>
                                    <small id="cliente-help" class="form-text text-muted">Selecciona un cliente existente o completa sus datos abajo.</small>
                                    @error('cliente_id')<span id="cliente-error" class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                                <fieldset class="pedido-create-client" data-cliente-nuevo>
                                    <legend><i class="fas fa-user-plus mr-2" aria-hidden="true"></i>Datos del cliente nuevo</legend>
                                    <p class="form-legend"><span aria-hidden="true">*</span> Campos obligatorios</p>
                                    <div class="row form-fields">
                                        <div class="col-12 form-group">
                                            <label for="cliente_nombre"><i class="fas fa-user field-label-icon" aria-hidden="true"></i>Nombre <span class="text-danger">*</span></label>
                                            <input id="cliente_nombre" name="cliente[nombre]" value="{{ old('cliente.nombre') }}" class="form-control @error('cliente.nombre') is-invalid @enderror" maxlength="255" autocomplete="name" required @error('cliente.nombre') aria-invalid="true" aria-describedby="cliente-nombre-error" @enderror>
                                            @error('cliente.nombre')<span id="cliente-nombre-error" class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                        </div>
                                        <div class="col-12 col-md-6 form-group">
                                            <label for="cliente_tipo"><i class="fas fa-list-ul field-label-icon" aria-hidden="true"></i>Tipo <span class="text-danger">*</span></label>
                                            <select id="cliente_tipo" name="cliente[tipo]" class="form-control @error('cliente.tipo') is-invalid @enderror" required @error('cliente.tipo') aria-invalid="true" aria-describedby="cliente-tipo-error" @enderror>
                                                <option value="persona" @selected(old('cliente.tipo') !== 'empresa')>Persona</option>
                                                <option value="empresa" @selected(old('cliente.tipo') === 'empresa')>Empresa</option>
                                            </select>
                                            @error('cliente.tipo')<span id="cliente-tipo-error" class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                        </div>
                                        <div class="col-12 col-md-6 form-group">
                                            <label for="cliente_telefono"><i class="fas fa-phone field-label-icon" aria-hidden="true"></i>Teléfono <span class="text-danger">*</span></label>
                                            <input id="cliente_telefono" name="cliente[telefono]" value="{{ old('cliente.telefono') }}" class="form-control @error('cliente.telefono') is-invalid @enderror" maxlength="40" autocomplete="tel" required @error('cliente.telefono') aria-invalid="true" aria-describedby="cliente-telefono-error" @enderror>
                                            @error('cliente.telefono')<span id="cliente-telefono-error" class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                        </div>
                                        <div class="col-12 form-group">
                                            <label for="cliente_email"><i class="fas fa-envelope field-label-icon" aria-hidden="true"></i>Correo (opcional)</label>
                                            <input id="cliente_email" type="email" name="cliente[email]" value="{{ old('cliente.email') }}" class="form-control @error('cliente.email') is-invalid @enderror" maxlength="255" autocomplete="email" @error('cliente.email') aria-invalid="true" aria-describedby="cliente-email-error" @enderror>
                                            @error('cliente.email')<span id="cliente-email-error" class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                        </div>
                                        <div class="col-12 form-group mb-0">
                                            <label for="cliente_direccion"><i class="fas fa-map-marker-alt field-label-icon" aria-hidden="true"></i>Dirección (opcional)</label>
                                            <input id="cliente_direccion" name="cliente[direccion]" value="{{ old('cliente.direccion') }}" class="form-control @error('cliente.direccion') is-invalid @enderror" maxlength="500" autocomplete="street-address" @error('cliente.direccion') aria-invalid="true" aria-describedby="cliente-direccion-error" @enderror>
                                            @error('cliente.direccion')<span id="cliente-direccion-error" class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                        </section>
                        <section class="card dashboard-card record-card">
                            <x-ficha-heading title="Entrega y coordinación" subtitle="Puedes completar estos datos ahora o posteriormente." icon="fas fa-calendar-check" />
                            <div class="card-body">@include('admin.pedidos._datos')</div>
                        </section>
                    </div>
                    <aside class="pedido-create-summary" aria-label="Resumen y confirmación del pedido">
                        <section class="card dashboard-card record-card">
                            <x-ficha-heading :title="'Cotización #'.$cotizacion->id" subtitle="Muebles y precios aprobados." icon="fas fa-file-invoice-dollar" />
                            <div class="table-responsive" tabindex="0" role="region" aria-label="Muebles del pedido">
                                <table class="table table-bordered table-striped table-sm tajibo-table mb-0">
                                    <thead><tr><th scope="col">Mueble</th><th scope="col" class="text-center">Cantidad</th><th scope="col" class="text-right">Precio total Bs</th></tr></thead>
                                    <tbody>
                                        @foreach($cotizacion->detalles as $detalle)
                                            <tr>
                                                <td><strong class="categoria-nombre">{{ $detalle->tipo_nombre }}</strong></td>
                                                <td class="text-center">{{ $detalle->cantidad_muebles }}</td>
                                                <td class="text-right text-nowrap">{{ number_format($detalle->precio_final, 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-body">
                                <div class="pedido-create-total">
                                    <span>Total acordado</span>
                                    <strong>Bs {{ number_format($cotizacion->precio_final, 2, ',', '.') }}</strong>
                                </div>
                            </div>
                        </section>
                        <section class="card dashboard-card record-card">
                            <x-ficha-heading title="Confirmar pedido" subtitle="Revisa el cliente y el importe antes de guardar." icon="fas fa-check-circle" />
                            <div class="card-body">
                                <div class="pedido-create-acceptance">
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input @error('aceptacion') is-invalid @enderror" id="aceptacion" type="checkbox" name="aceptacion" value="1" required @checked(old('aceptacion')) @error('aceptacion') aria-invalid="true" aria-describedby="aceptacion-error" @enderror>
                                        <label class="custom-control-label" for="aceptacion"><i class="fas fa-check-circle field-label-icon" aria-hidden="true"></i>Confirmo que el cliente aceptó esta cotización.</label>
                                    </div>
                                    @error('aceptacion')<span id="aceptacion-error" class="invalid-feedback d-block" role="alert">{{ $message }}</span>@enderror
                                </div>
                                <div class="form-actions pedido-create-actions">
                                    <a class="btn btn-cancel" href="{{ route('admin.cotizaciones.show', $cotizacion) }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Cancelar</a>
                                    <button class="btn btn-primary" type="submit"><i class="fas fa-save mr-2" aria-hidden="true"></i>Registrar pedido</button>
                                </div>
                            </div>
                        </section>
                    </aside>
                </div>
            </form>
        </div>
    </div>
@stop
