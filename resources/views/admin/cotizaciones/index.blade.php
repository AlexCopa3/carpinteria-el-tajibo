@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <div class="row">
        <div class="col-12">
            <x-page-heading :refined="true" title="Cotizaciones" eyebrow="OPERACIONES" subtitle="Consulta las cotizaciones, sus costos y los precios aprobados.">
                @can('create', App\Models\Cotizacion::class)
                    <a class="btn btn-primary" href="{{ route('admin.cotizaciones.create') }}"><i class="fas fa-plus mr-2" aria-hidden="true"></i>Nueva cotización</a>
                @endcan
            </x-page-heading>
        </div>
    </div>
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-auto table-list-column">
    <form method="GET" class="card dashboard-card">
        <div class="card-body row align-items-end list-filters">
            <div class="col-12 col-md-5"><div class="form-group"><label for="q"><i class="fas fa-search field-label-icon" aria-hidden="true"></i>Buscar ID</label><input id="q" name="q" value="{{ request('q') }}" class="form-control" maxlength="200"></div></div>
            <div class="col-12 col-md-3">
                <div class="form-group"><label for="estado"><i class="fas fa-toggle-on field-label-icon" aria-hidden="true"></i>Estado</label>
                    <select id="estado" name="estado" class="form-control">
                        <option value="">Todos</option>
                        @foreach(['borrador' => 'Borrador', 'pendiente_costos' => 'Costos pendientes', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada'] as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(request('estado') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 col-md-4 form-actions mb-3">
                <button class="btn btn-search" type="submit"><i class="fas fa-search mr-2" aria-hidden="true"></i>Buscar</button>
                <a class="btn btn-clear" href="{{ route('admin.cotizaciones.index') }}"><i class="fas fa-eraser mr-2" aria-hidden="true"></i>Limpiar</a>
            </div>
        </div>
    </form>
    <section class="card dashboard-card">
        <x-tabla-info />
        <div class="table-responsive" tabindex="0" role="region" aria-label="Cotizaciones">
            <table class="table table-bordered table-striped table-hover table-sm tajibo-table w-auto">
                <thead><tr><th scope="col">ID</th><th scope="col">Fecha</th><th scope="col">Estado</th>@if(App\Support\Acceso::permite(auth()->user(), 'costos.consultar'))<th scope="col">Costo total Bs</th>@endif<th scope="col">Precio aprobado Bs</th><th scope="col">Acciones</th></tr></thead>
                <tbody>
                    @forelse($cotizaciones as $cotizacion)
                        <tr>
                            <td><a class="categoria-nombre" href="{{ route('admin.cotizaciones.show', $cotizacion) }}">{{ $cotizacion->id }}</a></td>
                            <td>{{ $cotizacion->fecha->format('d/m/Y') }}</td>
                            <td><span class="record-status {{ $cotizacion->estado === 'aprobada' ? 'is-active' : 'is-inactive' }}">{{ $cotizacion->estado_nombre }}</span></td>
                            @if(App\Support\Acceso::permite(auth()->user(), 'costos.consultar'))<td>{{ App\Support\Decimal::mostrar($cotizacion->costo_total, 2) }}</td>@endif
                            <td>{{ App\Support\Decimal::mostrar($cotizacion->precio_final, 2) }}</td>
                            <td><div class="table-actions">
                                <a class="btn btn-sm btn-info" href="{{ route('admin.cotizaciones.show', $cotizacion) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver</a>
                                @can('update', $cotizacion)<a class="btn btn-sm btn-success" href="{{ route('admin.cotizaciones.edit', $cotizacion) }}"><i class="fas fa-edit mr-2" aria-hidden="true"></i>Editar</a>@endcan
                                @if($cotizacion->estado === 'aprobada' && $cotizacion->aprobado_at)<a class="btn btn-sm btn-history" href="{{ route('admin.cotizaciones.pdf', $cotizacion) }}" target="_blank" rel="noopener"><i class="fas fa-file-pdf mr-2" aria-hidden="true"></i>PDF</a>@endif
                                @if(in_array($cotizacion->estado, ['borrador', 'pendiente_costos'], true) && !$cotizacion->aprobado_at && !$cotizacion->pedido)
                                    <x-eliminar :registro="$cotizacion" :ruta="route('admin.cotizaciones.destroy', $cotizacion)" />
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ App\Support\Acceso::permite(auth()->user(), 'costos.consultar') ? 6 : 5 }}"><x-empty-state title="Sin cotizaciones" description="Registra los materiales para preparar el primer presupuesto." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body pagination-wrap">{{ $cotizaciones->links() }}</div>
    </section>
        </div>
    </div>
@stop
