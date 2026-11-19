@extends('adminlte::page')
@section('title', 'Casos similares')
@section('content_header')
    <x-page-heading title="Casos similares" eyebrow="COTIZACIONES / REFERENCIAS" subtitle="Consulta las diferencias antes de utilizar un antecedente.">
        <a class="btn btn-cancel" href="{{ route('admin.cotizaciones.show',$cotizacion) }}#mueble-{{ $detalle->id }}"><i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>Volver a la cotización</a>
    </x-page-heading>
@stop
@section('content')
    <x-alerts />
    <div class="card dashboard-card"><div class="card-header"><h2><i class="fas fa-chair mr-2" aria-hidden="true"></i>Mueble {{ $detalle->posicion }} · {{ $detalle->tipo_nombre }}</h2></div><div class="card-body">
        <div class="row">
            <div class="col-12 col-md-4 mb-3"><h3 class="h6">Dimensiones (cm)</h3>{{ App\Support\Decimal::mostrar($detalle->largo_cm) }} × {{ App\Support\Decimal::mostrar($detalle->ancho_cm) }} × {{ App\Support\Decimal::mostrar($detalle->alto_cm) }}</div>
            <div class="col-12 col-md-4 mb-3"><h3 class="h6">Material y acabado</h3>{{ $detalle->material_principal_nombre }} · {{ $detalle->acabado }}</div>
            <div class="col-12 col-md-4 mb-3"><h3 class="h6">Cantidad y características</h3>{{ $detalle->cantidad_muebles }} {{ $detalle->cantidad_muebles == 1 ? 'mueble' : 'muebles' }} · {{ $detalle->puertas }} puertas · {{ $detalle->cajones }} cajones · {{ $detalle->divisiones }} divisiones</div>
        </div>
        <p class="small text-muted mt-3 mb-0">Se usan las características guardadas. Esta consulta no cambia la cotización ni aprueba precios. Para aplicar una propuesta editable, abre el formulario de registro o edición.</p>
    </div></div>
    @include('admin.similitud._resultados')
@stop
