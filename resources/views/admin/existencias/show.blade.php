@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <div class="row">
        <div class="col-12">
            <x-page-heading :refined="true" title="Movimientos del material" eyebrow="EXISTENCIAS" :subtitle="$material->nombre">
                <a class="btn btn-default btn-return" href="{{ route('admin.existencias.index') }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver</a>
            </x-page-heading>
        </div>
    </div>
@stop
@section('content')
    <x-alerts />
    <div class="row record-detail-cards">
        <div class="col-12 col-lg-10 col-xl-8">
            <section class="card dashboard-card record-card">
                <x-ficha-heading :title="$material->nombre" subtitle="Cantidad disponible y unidad de medida." icon="fas fa-cubes" :activo="$material->activo" />
                <div class="card-body">
                    <dl class="record-fields record-fields-boxed mb-0">
                        <div class="record-field">
                            <dt><i class="fas fa-cubes record-value-icon" aria-hidden="true"></i>Existencia actual</dt>
                            <dd><strong>{{ App\Support\Decimal::mostrar($material->existencia?->cantidad ?? '0') }}</strong></dd>
                        </div>
                        <div class="record-field">
                            <dt><i class="fas fa-ruler-combined record-value-icon" aria-hidden="true"></i>Unidad de medida</dt>
                            <dd>{{ $material->unidad->nombre }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>
    </div>
    <div class="row">
        <div class="col-12 col-xl-10">
    <section class="card dashboard-card record-card">
        <x-ficha-heading title="Movimientos del material" subtitle="Cantidades registradas, responsables y motivos." icon="fas fa-history" />
        <x-tabla-info />
        <div class="table-responsive" tabindex="0" role="region" aria-label="Movimientos del material">
            <table class="table table-bordered table-striped table-hover table-sm tajibo-table">
                <caption class="sr-only">Historial de cantidades de {{ $material->nombre }}</caption>
                <thead>
                    <tr>
                        <th scope="col">Fecha y hora</th>
                        <th scope="col">Responsable</th>
                        <th scope="col">Movimiento</th>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Saldo anterior</th>
                        <th scope="col">Saldo resultante</th>
                        <th scope="col">Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $movimiento)
                        <tr>
                            <td>{{ $movimiento->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $movimiento->usuario?->name ?? 'No registrado' }}</td>
                            <td>{{ match($movimiento->tipo) { 'ajuste_positivo' => 'Aumento', 'ajuste_negativo' => 'Disminución', default => ucfirst(str_replace('_', ' ', $movimiento->tipo)) } }}</td>
                            <td>{{ match($movimiento->tipo) { 'ajuste_positivo' => '+', 'ajuste_negativo' => '−', default => '' } }}{{ App\Support\Decimal::mostrar($movimiento->cantidad) }} {{ $movimiento->unidad_codigo }}</td>
                            <td>{{ App\Support\Decimal::mostrar($movimiento->existencia_anterior) }} {{ $movimiento->unidad_codigo }}</td>
                            <td>{{ App\Support\Decimal::mostrar($movimiento->existencia_resultante) }} {{ $movimiento->unidad_codigo }}</td>
                            <td>{{ $movimiento->motivo }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state title="Aún no hay movimientos" description="Los cambios aparecerán al agregar o editar la cantidad de este material." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $movimientos->links() }}</div>
    </section>
        </div>
    </div>
@stop
