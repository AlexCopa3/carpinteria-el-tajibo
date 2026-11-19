@extends('adminlte::page')

@section('title', config('app.name'))
@section('content_header')
    <x-page-heading :refined="true" title="Editar cantidad" eyebrow="EXISTENCIAS DE MATERIALES" :subtitle="$material->nombre" />
@stop
@section('content')
    <x-alerts />
    <div class="row">
        <div class="col-12 col-lg-10 col-xl-8">
            @include('admin.existencias._form')
        </div>
    </div>
@stop
