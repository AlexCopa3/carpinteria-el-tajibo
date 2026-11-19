@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" title="Editar cliente" eyebrow="OPERACIONES / CLIENTES" subtitle="No se requieren documentos de identidad ni datos tributarios." />
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-12 col-xl-10">
            <div class="card card-success dashboard-card form-panel form-themed form-action-update">
                <div class="card-header">
                    <h3 class="card-title">Edite los datos del formulario</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.clientes.update',$cliente) }}">
                        <p class="form-legend"><span aria-hidden="true">*</span> Campos obligatorios</p>
                        <div class="row form-fields">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <div class="form-section-heading">
                                    <span class="form-section-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                                    <div><h2>Datos generales</h2><p>Registra a la persona o empresa que solicita el trabajo.</p></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-8">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="nombre"><i class="fas fa-user field-label-icon" aria-hidden="true"></i>Nombre completo o razón social</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $cliente->nombre) }}"
                                        class="form-control @error('nombre') is-invalid @enderror"
                                        @error('nombre') aria-describedby="nombre-error" @enderror
                                        @error('nombre') aria-invalid="true" @enderror
                                        required maxlength="255">
                                    @error('nombre')
                                        <span id="nombre-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <div class="form-label-line"><label for="tipo"><i class="fas fa-user field-label-icon" aria-hidden="true"></i>Tipo de cliente</label><span class="form-required is-required" aria-hidden="true" title="Campo obligatorio">*</span></div>
                                    <select id="tipo" name="tipo" class="form-control @error('tipo') is-invalid @enderror" @error('tipo') aria-invalid="true" aria-describedby="tipo-error" @enderror>
                                        <option value="persona" @selected(old('tipo',$cliente->tipo)==='persona')>Persona
                                        </option>
                                        <option value="empresa" @selected(old('tipo',$cliente->tipo)==='empresa')>Empresa
                                        </option>
                                    </select>
                                    @error('tipo')<span id="tipo-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-section-heading">
                                    <span class="form-section-icon" aria-hidden="true"><i class="fas fa-address-book"></i></span>
                                    <div><h2>Información de contacto</h2><p>Indica cómo contactar al cliente y dónde encontrarlo.</p></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="telefono"><i class="fas fa-phone field-label-icon" aria-hidden="true"></i>Teléfono</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $cliente->telefono) }}"
                                        class="form-control @error('telefono') is-invalid @enderror"
                                        @error('telefono') aria-describedby="telefono-error" @enderror
                                        @error('telefono') aria-invalid="true" @enderror
                                        required maxlength="40">
                                    @error('telefono')
                                        <span id="telefono-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="email"><i class="fas fa-envelope field-label-icon" aria-hidden="true"></i>Correo electrónico (opcional)</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <input id="email" name="email" type="email" value="{{ old('email', $cliente->email) }}"
                                        class="form-control @error('email') is-invalid @enderror"
                                        @error('email') aria-describedby="email-error" @enderror
                                        @error('email') aria-invalid="true" @enderror
                                        maxlength="255">
                                    @error('email')
                                        <span id="email-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-12 col-xl-4">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="direccion"><i class="fas fa-map-marker-alt field-label-icon" aria-hidden="true"></i>Dirección (opcional)</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <input id="direccion" name="direccion" type="text" value="{{ old('direccion', $cliente->direccion) }}"
                                        class="form-control @error('direccion') is-invalid @enderror"
                                        @error('direccion') aria-describedby="direccion-error" @enderror
                                        @error('direccion') aria-invalid="true" @enderror
                                        maxlength="500">
                                    @error('direccion')
                                        <span id="direccion-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-section-heading">
                                    <span class="form-section-icon" aria-hidden="true"><i class="fas fa-comment-alt"></i></span>
                                    <div><h2>Observaciones y estado</h2><p>Añade información útil para atender al cliente.</p></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-8">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="observaciones"><i class="fas fa-align-left field-label-icon" aria-hidden="true"></i>Observaciones</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <textarea id="observaciones" name="observaciones" rows="3"
                                        class="form-control @error('observaciones') is-invalid @enderror"
                                        @error('observaciones') aria-describedby="observaciones-error" @enderror
                                        @error('observaciones') aria-invalid="true" @enderror
                                        maxlength="3000">{{ old('observaciones', $cliente->observaciones) }}</textarea>
                                    @error('observaciones')
                                        <span id="observaciones-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="activo"><i class="fas fa-toggle-on field-label-icon" aria-hidden="true"></i>Estado</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <select id="activo" name="activo" class="form-control @error('activo') is-invalid @enderror" required
                                        aria-describedby="activo-help @error('activo') activo-error @enderror"
                                        @error('activo') aria-invalid="true" @enderror>
                                        <option value="1" @selected(old('activo', (int) ($cliente->activo)) == 1)>Activo</option>
                                        <option value="0" @selected(old('activo', (int) ($cliente->activo)) == 0)>Inactivo</option>
                                    </select>
                                    <small id="activo-help" class="form-text text-muted">Los registros inactivos conservan su historial.</small>
                                    @error('activo')
                                        <span id="activo-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-actions">
                                    <a class="btn btn-default btn-cancel" href="{{ route('admin.clientes.index') }}">
                                        <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
                                        Cancelar
                                    </a>
                                    <button class="btn btn-success" type="submit">
                                        <i class="fas fa-save mr-2" aria-hidden="true"></i>
                                        Actualizar cliente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
