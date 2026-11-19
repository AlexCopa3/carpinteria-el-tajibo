@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" title="Editar material" eyebrow="CATÁLOGO / MATERIALES" subtitle="Materiales y costos de referencia para preparar cotizaciones." />
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
                    <form method="POST" action="{{ route('admin.materiales.update',$material) }}">
                        <p class="form-legend"><span aria-hidden="true">*</span> Campos obligatorios</p>
                        <div class="row form-fields">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <div class="form-section-heading">
                                    <span class="form-section-icon" aria-hidden="true"><i class="fas fa-cubes"></i></span>
                                    <div><h2>Datos del material</h2><p>Identifica el material y selecciona su categoría.</p></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="nombre"><i class="fas fa-cubes field-label-icon" aria-hidden="true"></i>Nombre del material</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <input id="nombre" name="nombre" type="text" value="{{ old('nombre', $material->nombre) }}"
                                        class="form-control @error('nombre') is-invalid @enderror"
                                        @error('nombre') aria-describedby="nombre-error" @enderror
                                        @error('nombre') aria-invalid="true" @enderror
                                        required maxlength="255">
                                    @error('nombre')
                                        <span id="nombre-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="form-label-line"><label for="categoria_id"><i class="fas fa-tags field-label-icon" aria-hidden="true"></i>Categoría</label><span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span></div>
                                    <select id="categoria_id" name="categoria_id" class="form-control @error('categoria_id') is-invalid @enderror" required @error('categoria_id') aria-invalid="true" aria-describedby="categoria_id-error" @enderror>
                                        <option value="">Selecciona una categoría</option>
                                        @foreach($categorias as $categoria)
                                            <option value="{{ $categoria->id }}" @selected(old('categoria_id', $material->categoria_id) == $categoria->id)>{{ $categoria->nombre }}{{ ! $categoria->activo ? ' (inactiva)' : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('categoria_id')<span id="categoria_id-error" class="invalid-feedback field-error">{{ $message }}</span>@enderror
                                    @can('create', App\Models\Categoria::class)
                                        <a href="{{ route('admin.categorias.index') }}" class="form-text">Administrar categorías</a>
                                    @endcan
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-section-heading">
                                    <span class="form-section-icon" aria-hidden="true"><i class="fas fa-align-left"></i></span>
                                    <div><h2>Características</h2><p>Describe las medidas, el acabado y los detalles necesarios.</p></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="especificaciones"><i class="fas fa-paint-brush field-label-icon" aria-hidden="true"></i>Especificaciones (espesor, medidas, acabado...)</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <textarea id="especificaciones" name="especificaciones" rows="3"
                                        class="form-control @error('especificaciones') is-invalid @enderror"
                                        @error('especificaciones') aria-describedby="especificaciones-error" @enderror
                                        @error('especificaciones') aria-invalid="true" @enderror
                                        maxlength="3000">{{ old('especificaciones', $material->especificaciones) }}</textarea>
                                    @error('especificaciones')
                                        <span id="especificaciones-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="descripcion"><i class="fas fa-align-left field-label-icon" aria-hidden="true"></i>Descripción</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <textarea id="descripcion" name="descripcion" rows="3"
                                        class="form-control @error('descripcion') is-invalid @enderror"
                                        @error('descripcion') aria-describedby="descripcion-error" @enderror
                                        @error('descripcion') aria-invalid="true" @enderror
                                        maxlength="3000">{{ old('descripcion', $material->descripcion) }}</textarea>
                                    @error('descripcion')
                                        <span id="descripcion-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-section-heading">
                                    <span class="form-section-icon" aria-hidden="true"><i class="fas fa-ruler-combined"></i></span>
                                    <div><h2>Unidad y costos</h2><p>Indica la unidad de medida y su costo de referencia en bolivianos.</p></div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="form-group">
                                    <div class="form-label-line"><label for="unidad_id"><i class="fas fa-ruler field-label-icon" aria-hidden="true"></i>Unidad de medida</label><span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span></div>
                                    <select id="unidad_id" name="unidad_id" class="form-control @error('unidad_id') is-invalid @enderror" required @error('unidad_id') aria-invalid="true" aria-describedby="unidad_id-error" @enderror>
                                        @foreach($unidades as $unidad)
                                            <option value="{{ $unidad->id }}" @selected(old('unidad_id',$material->unidad_id)==$unidad->id)>{{ $unidad->nombre }} · {{ $unidad->fraccionaria?'admite fracciones':'solo enteros' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unidad_id')<span id="unidad_id-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>@enderror
                                    <small class="form-text text-muted">
                                        No se cambia si el material tiene cotizaciones o registros relacionados.
                                    </small>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="precio_referencia"><i class="fas fa-coins field-label-icon" aria-hidden="true"></i>Costo de referencia por unidad (Bs)</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <input id="precio_referencia" name="precio_referencia" type="number" value="{{ old('precio_referencia', $material->precio_referencia) }}"
                                        class="form-control @error('precio_referencia') is-invalid @enderror"
                                        aria-describedby="precio_referencia-help @error('precio_referencia') precio_referencia-error @enderror"
                                        @error('precio_referencia') aria-invalid="true" @enderror
                                        min="0" step="0.000001" required>
                                    <small id="precio_referencia-help" class="form-text text-muted">{{ 'Costo por unidad de medida utilizado como referencia para cotizar.' }}</small>
                                    @error('precio_referencia')
                                        <span id="precio_referencia-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="form-group">
                                    <div class="form-label-line">
                                        <label for="activo"><i class="fas fa-toggle-on field-label-icon" aria-hidden="true"></i>Estado</label>
                                        <span class="form-required" aria-hidden="true" title="Campo obligatorio">*</span>
                                    </div>
                                    <select id="activo" name="activo" class="form-control @error('activo') is-invalid @enderror" required
                                        aria-describedby="activo-help @error('activo') activo-error @enderror"
                                        @error('activo') aria-invalid="true" @enderror>
                                        <option value="1" @selected(old('activo', (int) ($material->activo)) == 1)>Activo</option>
                                        <option value="0" @selected(old('activo', (int) ($material->activo)) == 0)>Inactivo</option>
                                    </select>
                                    <small id="activo-help" class="form-text text-muted">Los registros inactivos conservan su historial.</small>
                                    @error('activo')
                                        <span id="activo-error" class="invalid-feedback d-block field-error" role="alert">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-actions">
                                    <a class="btn btn-default btn-cancel" href="{{ route('admin.materiales.index') }}">
                                        <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
                                        Cancelar
                                    </a>
                                    <button class="btn btn-success" type="submit">
                                        <i class="fas fa-save mr-2" aria-hidden="true"></i>
                                        Actualizar material
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
