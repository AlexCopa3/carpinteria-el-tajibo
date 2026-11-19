@extends('adminlte::page')
@section('title', 'Editar pago')
@section('content_header')
    <x-page-heading :refined="true" title="Editar pago" eyebrow="ADMINISTRACIÓN / PAGOS" />
@stop
@section('content')
    @include('admin.pagos._form')
@stop
