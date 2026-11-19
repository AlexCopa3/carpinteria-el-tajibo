@extends('adminlte::page')
@section('title', 'Historial de cambios')
@section('content_header')
    <x-page-heading title="Historial de cambios" eyebrow="ADMINISTRACIÓN" subtitle="Responsables y cambios de los registros, incluidas las eliminaciones autorizadas." />
@stop
@section('content')
    <form method="GET" action="{{ route('admin.auditoria.index') }}" class="row mb-3">
        <div class="col-12 col-md-6">
            <label for="tipo"><i class="fas fa-list-ul field-label-icon" aria-hidden="true"></i>Tipo de registro</label>
            <select name="tipo" id="tipo" class="form-control">
                <option value="">Todos</option>@foreach($tipos as $valor => $nombre)<option value="{{ $valor }}" @selected(request('tipo') === $valor)>{{ $nombre }}</option>@endforeach
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label for="registro"><i class="fas fa-id-card field-label-icon" aria-hidden="true"></i>Número de registro</label>
            <input class="form-control" type="number" name="registro" id="registro" min="1" value="{{ request('registro') }}">
        </div>
        <div class="col-12 col-md-3 d-flex align-items-end">
            <button class="btn btn-primary" type="submit">
                <i class="fas fa-filter mr-2" aria-hidden="true">
                </i>Filtrar</button>
        </div>
    </form>
    <section class="card dashboard-card">
        <div class="table-responsive">
            <table class="table tajibo-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Responsable</th>
                        <th>Registro</th>
                        <th>Acción</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditorias as $evento)
                        <tr>
                            <td>{{ $evento->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $evento->usuario?->name ?? 'Proceso del sistema' }}</td>
                            <td>
                                <strong>{{ $evento->nombre_registro }}</strong>
                                <small class="text-muted d-block">{{ $tipos[$evento->registro_type] ?? 'Registro' }} #{{ $evento->registro_id }}</small>
                            </td>
                            <td>{{ ['creado'=>'Registrado','modificado'=>'Modificado','eliminado'=>'Eliminado','permisos_actualizados'=>'Permisos actualizados', 'contrasena_restablecida'=>'Contraseña restablecida'][$evento->accion] ?? $evento->accion }}</td>
                            <td>
                                <details>
                                    <summary>Ver cambios</summary>
                                    <div class="table-responsive mt-2">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Campo</th>
                                                    <th>Anterior</th>
                                                    <th>Nuevo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach(array_unique(array_merge(array_keys($evento->datos_anteriores ?? []), array_keys($evento->datos_nuevos ?? []))) as $campo)
                                                    @php
                                                        $anterior = $evento->datos_anteriores[$campo] ?? null;
                                                        $nuevo = $evento->datos_nuevos[$campo] ?? null;
                                                    @endphp
                                                    @if($anterior !== $nuevo)
                                                        <tr>
                                                            <td>{{ config('auditoria.campos.'.$campo, ucfirst($campo)) }}</td>
                                                            <td>{{ is_array($anterior) ? implode(', ', $anterior) : ($campo === 'activo' && $anterior !== null ? ($anterior ? 'Activo' : 'Inactivo') : ($anterior ?? '—')) }}</td>
                                                            <td>{{ is_array($nuevo) ? implode(', ', $nuevo) : ($campo === 'activo' && $nuevo !== null ? ($nuevo ? 'Activo' : 'Inactivo') : ($nuevo ?? '—')) }}</td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state title="Sin cambios registrados" description="Los registros anteriores conservan sus datos; la auditoría comienza con las nuevas operaciones." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $auditorias->links() }}</div>
    </section>
@stop
