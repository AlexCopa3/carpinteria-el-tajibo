@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" title="Nueva cotización" eyebrow="OPERACIONES / COTIZACIONES" subtitle="Describe el mueble, busca casos similares y elige una propuesta o continúa manualmente." />
@stop
@section('content')
    <div class="row">
        <div class="col-12 col-xl-10 quote-create-layout">
            @include('admin.cotizaciones._form')
        </div>
    </div>
@stop
