@extends('adminlte::page')
@section('title', 'Editar categoría')
@section('content_header')
    <x-page-heading :refined="true" title="Editar categoría" eyebrow="CATÁLOGO / CATEGORÍAS" />
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card card-success form-panel form-themed form-action-update">
                <div class="card-header"><h3 class="card-title">Edite los datos del formulario</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.categorias.update', $categoria) }}">
                        <p class="form-legend"><span aria-hidden="true">*</span> Campos obligatorios</p>
                        <div class="row form-fields">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <div class="form-section-heading">
                                    <span class="form-section-icon" aria-hidden="true"><i class="fas fa-tags"></i></span>
                                    <div><h2>Datos de la categoría</h2><p>Usa un nombre claro para agrupar materiales similares.</p></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-8 col-xl-6">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="nombre"><i class="fas fa-tags field-label-icon" aria-hidden="true"></i>Nombre de la categoría</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $categoria->nombre) }}"
                                        class="form-control @error('nombre') is-invalid @enderror"
                                        aria-describedby="nombre-help @error('nombre') nombre-error @enderror"
                                        @error('nombre') aria-invalid="true" @enderror
                                        required maxlength="100">
                                    <small id="nombre-help" class="form-text text-muted">{{ 'Por ejemplo: Maderas, Herrajes o Acabados.' }}</small>
                                    @error('nombre')
                                        <span id="nombre-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-4 col-xl-6"><div class="form-group">
                                    <div class="form-label-line">
                                        <label for="activo"><i class="fas fa-toggle-on field-label-icon" aria-hidden="true"></i>Estado</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <select id="activo" name="activo" class="form-control @error('activo') is-invalid @enderror" required
                                        aria-describedby="activo-help @error('activo') activo-error @enderror"
                                        @error('activo') aria-invalid="true" @enderror>
                                        <option value="1" @selected(old('activo', (int) ($categoria->activo)) == 1)>Activo</option>
                                        <option value="0" @selected(old('activo', (int) ($categoria->activo)) == 0)>Inactivo</option>
                                    </select>
                                    <small id="activo-help" class="form-text text-muted">Los registros inactivos conservan su historial.</small>
                                    @error('activo')
                                        <span id="activo-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div></div>
                            <div class="col-12 form-actions">
                                <a class="btn btn-default btn-cancel" href="{{ route('admin.categorias.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Cancelar</a>
                                <button class="btn btn-success" type="submit"><i class="fas fa-save mr-2" aria-hidden="true"></i>Actualizar categoría</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
