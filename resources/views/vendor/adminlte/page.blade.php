@extends('adminlte::master')

@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')
@inject('preloaderHelper', 'JeroenNoten\LaravelAdminLte\Helpers\PreloaderHelper')

@section('adminlte_css_pre')
    @include('admin.partials.tema-inicial')
@stop

@section('adminlte_css')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('css')
    @yield('css')
@stop

@section('classes_body', $layoutHelper->makeBodyClasses())

@section('body_data', $layoutHelper->makeBodyData())

@section('body')
    <div class="wrapper">

        {{-- Preloader Animation (fullscreen mode) --}}
        @if($preloaderHelper->isPreloaderEnabled())
            @include('adminlte::partials.common.preloader')
        @endif

        {{-- Top Navbar --}}
        @if($layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.navbar.navbar-layout-topnav')
        @else
            @include('adminlte::partials.navbar.navbar')
        @endif

        {{-- Left Main Sidebar --}}
        @if(!$layoutHelper->isLayoutTopnavEnabled())
            @include('adminlte::partials.sidebar.left-sidebar')
        @endif

        {{-- Content Wrapper --}}
        @empty($iFrameEnabled)
            @include('adminlte::partials.cwrapper.cwrapper-default')
        @else
            @include('adminlte::partials.cwrapper.cwrapper-iframe')
        @endempty

        {{-- Footer --}}
        @hasSection('footer')
            @include('adminlte::partials.footer.footer')
        @endif

        {{-- Right Control Sidebar --}}
        @if($layoutHelper->isRightSidebarEnabled())
            @include('adminlte::partials.sidebar.right-sidebar')
        @endif

    </div>
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
    @include('admin.partials.alertas')
    <script>
        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form.matches('[data-confirmar-eliminacion]') || form.dataset.confirmado === 'si') return;
            event.preventDefault();
            Swal.fire({
                title: '¿Desea eliminar este registro?',
                text: 'Solo se eliminará si no tiene relaciones ni historial de negocio.',
                icon: 'question', showCancelButton: true,
                confirmButtonText: 'Eliminar', cancelButtonText: 'Cancelar',
                confirmButtonColor: '#bd2130'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.dataset.confirmado = 'si';
                    form.requestSubmit();
                }
            });
        });
    </script>
@stop

@section('footer')
    <span>{{ $ajusteNegocio?->nombre ?? 'Carpintería El Tajibo' }}</span><span class="float-right">Hecho a medida, de principio a fin.</span>
@stop
