@extends('adminlte::page')
@section('title', 'Nuevo pago')
@section('content_header')
    <x-page-heading :refined="true" title="Nuevo pago" eyebrow="ADMINISTRACIÓN / PAGOS" />
@stop
@section('content')
    @include('admin.pagos._form')
@stop
