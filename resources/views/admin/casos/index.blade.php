@extends('adminlte::page')
@section('title', 'Casos históricos')
@section('content_header')
    <x-page-heading :refined="true" title="Casos históricos" eyebrow="ANTECEDENTES DE MUEBLES" />
@stop
@section('content')
    <x-alerts />
    <div class="alert alert-info"><i class="fas fa-info-circle mr-2" aria-hidden="true"></i>Consulta materiales, cantidades, costos estimados y precios por mueble o grupo. Revisa las referencias y sus estimaciones antes de utilizarlas en una nueva cotización.</div>
    <div class="row"><div class="col-auto mw-100 module-list-content">
    <div class="card dashboard-card"><div class="card-body">
        <form method="GET" class="row list-filters module-list-filters">
            <div class="col-12 col-md-6 col-lg-4 form-group"><label for="q"><i class="fas fa-search field-label-icon" aria-hidden="true"></i>Buscar por ID, tipo o material</label><input class="form-control" id="q" name="q" value="{{ request('q') }}" maxlength="200"></div>
            <div class="col-12 col-md-6 col-lg-4 form-group"><label for="tipo"><i class="fas fa-chair field-label-icon" aria-hidden="true"></i>Tipo de mueble</label><select class="form-control" id="tipo" name="tipo"><option value="">Todos</option>@foreach($tipos as $tipo)<option value="{{ $tipo->id }}" @selected((string)request('tipo') === (string)$tipo->id)>{{ $tipo->nombre }}</option>@endforeach</select></div>
            <div class="col-12 col-md-6 col-lg-4 form-group"><label for="revision"><i class="fas fa-clipboard-check field-label-icon" aria-hidden="true"></i>Revisión</label><select class="form-control" id="revision" name="revision"><option value="">Todas</option>@foreach(['pendiente'=>'Pendiente','revisado'=>'Revisado','descartado'=>'Descartado'] as $valor=>$nombre)<option value="{{ $valor }}" @selected(request('revision') === $valor)>{{ $nombre }}</option>@endforeach</select></div>
            <div class="col-12 form-group table-actions flex-wrap"><button class="btn btn-primary"><i class="fas fa-search mr-2" aria-hidden="true"></i>Buscar</button><a class="btn btn-clear" href="{{ route('admin.casos.index') }}"><i class="fas fa-eraser mr-2" aria-hidden="true"></i>Limpiar</a></div>
        </form>
        <p class="text-muted">Total de casos históricos: <strong>{{ $totalCasos }}</strong>@if($casos->total() !== $totalCasos) · Resultados del filtro: {{ $casos->total() }}@endif</p>
        <div class="table-responsive" tabindex="0" role="region" aria-label="Lista de casos históricos"><table class="table table-hover tajibo-table w-auto mb-0">
            <thead><tr><th>ID</th><th>Mueble / material</th><th>Dimensiones cm</th><th>Muebles</th><th>Revisión</th><th>Precio del grupo Bs</th><th>Acciones</th></tr></thead>
            <tbody>@forelse($casos as $caso)<tr>
                <td>{{ $caso->id }}</td><td><strong class="categoria-nombre">{{ $caso->tipo_nombre }}</strong><small class="d-block text-muted">{{ $caso->material_principal_nombre }}</small></td>
                <td>{{ App\Support\Decimal::mostrar($caso->largo_cm) }} × {{ App\Support\Decimal::mostrar($caso->ancho_cm) }} × {{ App\Support\Decimal::mostrar($caso->alto_cm) }}</td><td>{{ $caso->cantidad_muebles }}</td>
                <td><span class="record-status {{ $caso->estado_revision === 'revisado' ? 'is-active' : 'is-inactive' }}">{{ ucfirst($caso->estado_revision) }}</span></td>
                <td>{{ App\Support\Decimal::mostrar($caso->precio_historico_total,2) }}</td><td><div class="table-actions"><a class="btn btn-sm btn-info" href="{{ route('admin.casos.show',$caso) }}"><i class="fas fa-eye mr-2" aria-hidden="true"></i>Ver</a></div></td>
            </tr>@empty<tr><td colspan="7">No hay casos para estos filtros. Los pedidos se incorporan al terminar con sus validaciones finales.</td></tr>@endforelse</tbody>
        </table></div>
        {{ $casos->links() }}
        <p class="small text-muted mb-0">Puedes buscar casos similares mientras describes el mueble en Nueva cotización o Editar.</p>
    </div></div>
    </div></div>
@stop
