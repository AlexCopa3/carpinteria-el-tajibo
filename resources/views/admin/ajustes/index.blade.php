@extends('adminlte::page')

@section('title', 'Configuración de la carpintería')
@section('content_header')
    <div class="ajustes-heading-compact">
        <x-page-heading :refined="true" title="Configuración" eyebrow="ADMINISTRACIÓN" subtitle="Datos de la carpintería y su identidad visual." />
    </div>
@stop
@section('content')
    <x-alerts />
    <form method="POST" action="{{ route('admin.ajustes.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="row ajustes-columns">
            <div class="col-12 col-lg-8 ajustes-data-column">
                <section class="card dashboard-card form-panel form-themed {{ $ajuste ? 'form-action-update' : 'form-action-create' }}">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-sliders-h mr-2" aria-hidden="true"></i>{{ $ajuste ? 'Actualizar datos de la carpintería' : 'Registrar datos de la carpintería' }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="form-legend"><span aria-hidden="true">*</span> Campos obligatorios</p>
                        <div class="row form-fields">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="nombre"><i class="fas fa-store field-label-icon" aria-hidden="true"></i>Nombre de la carpintería</label>
                                        <span class="form-required is-required" aria-hidden="true">*</span>
                                    </div>
                                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $ajuste?->nombre ?? config('app.name')) }}"
                                        class="form-control @error('nombre') is-invalid @enderror" maxlength="255" required
                                        @error('nombre') aria-invalid="true" aria-describedby="nombre-error" @enderror>
                                    @error('nombre')
                                        <span id="nombre-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="telefono"><i class="fas fa-phone field-label-icon" aria-hidden="true"></i>Teléfono</label>
                                        <span class="form-required is-required" aria-hidden="true">*</span>
                                    </div>
                                    <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $ajuste?->telefono) }}"
                                        class="form-control @error('telefono') is-invalid @enderror" maxlength="50" required
                                        @error('telefono') aria-invalid="true" aria-describedby="telefono-error" @enderror>
                                    @error('telefono')
                                        <span id="telefono-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row form-fields">
                            <div class="col-12 col-md-12">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="direccion"><i class="fas fa-map-marker-alt field-label-icon" aria-hidden="true"></i>Dirección</label>
                                        <span class="form-required is-required" aria-hidden="true">*</span>
                                    </div>
                                    <input id="direccion" name="direccion" type="text" value="{{ old('direccion', $ajuste?->direccion) }}"
                                        class="form-control @error('direccion') is-invalid @enderror" maxlength="255" required
                                        @error('direccion') aria-invalid="true" aria-describedby="direccion-error" @enderror>
                                    @error('direccion')
                                        <span id="direccion-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row form-fields">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="email"><i class="fas fa-envelope field-label-icon" aria-hidden="true"></i>Correo electrónico</label>
                                        <span class="form-required is-required" aria-hidden="true">*</span>
                                    </div>
                                    <input id="email" name="email" type="email" value="{{ old('email', $ajuste?->email) }}"
                                        class="form-control @error('email') is-invalid @enderror" maxlength="255" required
                                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                                    @error('email')
                                        <span id="email-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="web"><i class="fas fa-globe field-label-icon" aria-hidden="true"></i>Sitio web (opcional)</label>
                                        
                                    </div>
                                    <input id="web" name="web" type="text" value="{{ old('web', $ajuste?->web) }}"
                                        class="form-control @error('web') is-invalid @enderror" maxlength="255" 
                                        @error('web') aria-invalid="true" aria-describedby="web-error" @enderror>
                                    @error('web')
                                        <span id="web-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row form-fields">
                            <div class="col-12 col-md-12">
                                <div class="form-group">
                                    <label for="descripcion"><i class="fas fa-align-left field-label-icon" aria-hidden="true"></i>Descripción (opcional)</label>
                                    <textarea id="descripcion" name="descripcion" rows="3" maxlength="500"
                                        class="form-control @error('descripcion') is-invalid @enderror"
                                        @error('descripcion') aria-invalid="true" aria-describedby="descripcion-error" @enderror>{{ old('descripcion', $ajuste?->descripcion) }}</textarea>
                                    @error('descripcion')
                                        <span id="descripcion-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-actions">
                            <a class="btn btn-default btn-return" href="{{ route('admin.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver</a>
                            <button class="btn {{ $ajuste ? 'btn-success' : 'btn-primary' }}" type="submit"><i class="fas fa-save mr-2" aria-hidden="true"></i>{{ $ajuste ? 'Actualizar ajustes' : 'Guardar ajustes' }}</button>
                        </div>
                    </div>
                </section>
            </div>
            <div class="col-12 col-lg-4 ajustes-logo-column">
                <section class="card dashboard-card form-panel form-themed {{ $ajuste ? 'form-action-update' : 'form-action-create' }}">
                    <div class="card-header">
                        <h2 class="card-title"><i class="fas fa-image mr-2" aria-hidden="true"></i>Logo de la carpintería</h2>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <img class="ajuste-logo-preview" src="{{ $ajuste?->logo ? route('admin.ajustes.logo') : asset('images/tajibo.svg') }}" alt="Logo actual de la carpintería" width="96" height="96">
                            <p class="form-text text-muted mt-2 mb-0">Logo actual</p>
                        </div>
                        <div class="form-group">
                            <label for="logo"><i class="fas fa-image field-label-icon" aria-hidden="true"></i>Seleccionar logo (opcional)</label>
                            <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/svg+xml"
                                class="form-control-file @error('logo') is-invalid @enderror"
                                aria-describedby="logo-help @error('logo') logo-error @enderror"
                                @error('logo') aria-invalid="true" @enderror>
                            <small id="logo-help" class="form-text text-muted">JPG, PNG o SVG sin código ni referencias externas. Máximo 2 MB. Si no seleccionas otro archivo, se conserva el logo actual.</small>
                            @error('logo')
                                <span id="logo-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                            @enderror
                        </div>
                        <p class="form-text text-muted mb-0">Se mostrará junto al nombre en el menú del sistema.</p>
                    </div>
                </section>
            </div>
        </div>
    </form>
@stop
