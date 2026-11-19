@extends('adminlte::master')
@section('title', 'Acceso | Carpintería El Tajibo')
@section('classes_body', 'login-page tajibo-auth')
@section('adminlte_css_pre')
    @include('admin.partials.tema-inicial')
@stop

@section('adminlte_css')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@stop
@section('body')
    <main class="auth-shell">
        <section class="auth-story" aria-label="Carpintería El Tajibo">
            <div class="auth-brand"><img src="{{ asset('images/tajibo.svg') }}" alt="" width="48" height="48"><span>El Tajibo<small>CARPINTERÍA</small></span></div>
            <div class="auth-story-main"><p class="eyebrow">LA PAZ · EL ALTO</p><h1>El oficio de crear.<br>La confianza de<br><em>hacerlo bien.</em></h1><p>Un espacio para organizar cada detalle de tu taller, desde la primera idea hasta la última entrega.</p><div class="wood-lines" aria-hidden="true"><span></span><span></span><span></span></div></div>
            <p class="auth-story-footer"><i class="fas fa-tree mr-2" aria-hidden="true"></i> Hecho a medida, de principio a fin.</p>
        </section>
        <section class="auth-form-area"><div class="auth-theme-control"><x-theme-toggle /></div><div class="auth-form-wrap"><span class="auth-kicker">BIENVENIDO A TU TALLER DIGITAL</span>@yield('form')</div><p class="auth-footer">Carpintería El Tajibo · {{ now()->year }}</p></section>
    </main>
@stop
