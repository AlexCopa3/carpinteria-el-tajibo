@extends('adminlte::page')
@section('title', 'Reportes')
@section('content_header')
    <x-page-heading :refined="true" title="Reportes" eyebrow="ADMINISTRACIÓN" subtitle="Consulta los registros del sistema y genera un PDF imprimible." />
@stop
@section('content')
    <x-alerts />
    <div class="reports-home">
        <div class="reports-options">
            @foreach([
                ['tipo'=>'cotizaciones','icono'=>'fas fa-file-invoice','titulo'=>'Cotizaciones','texto'=>'Por fecha y estado; precios aprobados y costos estimados según tu acceso.','permiso'=>'cotizaciones.consultar'],
                ['tipo'=>'pedidos','icono'=>'fas fa-hammer','titulo'=>'Pedidos','texto'=>'Pedidos aceptados en un período, estado y entrega prevista.','permiso'=>'pedidos.consultar'],
                ['tipo'=>'pagos','icono'=>'fas fa-wallet','titulo'=>'Pagos recibidos','texto'=>'Importes registrados en un período, por efectivo o QR.','permiso'=>'pagos.consultar'],
                ['tipo'=>'saldos','icono'=>'fas fa-coins','titulo'=>'Saldos actuales','texto'=>'Pendientes y pagos acumulados de los pedidos, incluidos los antiguos.','permiso'=>'pagos.consultar']
            ] as $opcion)
                @if(App\Support\Acceso::permite(auth()->user(), $opcion['permiso']) && ($opcion['tipo'] === 'cotizaciones' || App\Support\Acceso::permite(auth()->user(), 'pedidos.consultar')))
                    <div class="reports-option">
                        <section class="card dashboard-card reports-option-card h-100">
                            <div class="record-heading">
                                <span class="record-heading-icon"><i class="{{ $opcion['icono'] }}" aria-hidden="true"></i></span>
                                <div><h2>{{ $opcion['titulo'] }}</h2><p>Consulta y PDF imprimible</p></div>
                            </div>
                            <div class="card-body">
                                <p class="reports-description">{{ $opcion['texto'] }}</p>
                                <a class="btn btn-info" href="{{ route('admin.reportes.show', $opcion['tipo']) }}">
                                    <i class="fas fa-eye mr-2" aria-hidden="true"></i>Consultar {{ mb_strtolower($opcion['titulo']) }}
                                </a>
                            </div>
                        </section>
                    </div>
                @endif
            @endforeach
        </div>
        <p class="reports-footnote"><i class="fas fa-info-circle mr-2" aria-hidden="true"></i>Los reportes incluyen todos los registros guardados. Los precios acordados y los costos estimados no equivalen a cobros ni ganancias verificadas.</p>
    </div>
@stop
