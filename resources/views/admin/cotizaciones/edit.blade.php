@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" title="Editar cotización" eyebrow="OPERACIONES / COTIZACIONES" subtitle="Describe el mueble, completa sus materiales y revisa los costos." />
@stop
@section('content')
    <div class="row">
        <div class="col-12 col-xl-10">
            @include('admin.cotizaciones._form')
        </div>
    </div>
@stop
