@inject('navigation', 'App\Support\Navigation')
<aside id="sidebar-menu" class="main-sidebar sidebar-dark-primary elevation-0">
    <button type="button" data-widget="pushmenu" class="sidebar-close d-lg-none" aria-label="Cerrar menú" aria-controls="sidebar-menu"><i class="fas fa-times" aria-hidden="true"></i></button>
    <a href="{{ route('admin.index') }}" class="brand-link tajibo-brand">
        <img src="{{ $ajusteNegocio?->logo ? route('admin.ajustes.logo') : asset('images/tajibo.svg') }}" alt="" width="40" height="40">
        <span><strong>{{ $ajusteNegocio?->nombre ?? 'El Tajibo' }}</strong><small>CARPINTERÍA</small></span>
    </a>
    <div class="sidebar">
        <nav class="pt-2" aria-label="Navegación principal">
            <ul class="nav nav-pills nav-sidebar flex-column">
                @foreach($navigation->forUser(auth()->user()) as $seccion => $items)
                    <li class="nav-header">{{ $seccion }}</li>
                    @foreach($items as $item)
                        <li class="nav-item">
                            @if($item['ruta'])
                                <a href="{{ route($item['ruta']) }}" class="nav-link {{ ($item['ruta'] === 'admin.index' ? request()->routeIs('admin.index') : request()->routeIs($item['ruta'], str_replace('.index', '.*', $item['ruta']))) ? 'active' : '' }}" @if(($item['ruta'] === 'admin.index' ? request()->routeIs('admin.index') : request()->routeIs($item['ruta'], str_replace('.index', '.*', $item['ruta'])))) aria-current="page" @endif>
                                    <i class="nav-icon {{ $item['icono'] }}" aria-hidden="true"></i><span>{{ $item['texto'] }}</span>
                                </a>
                            @else
                                <span class="nav-link upcoming" aria-disabled="true">
                                    <i class="nav-icon {{ $item['icono'] }}" aria-hidden="true"></i><span>{{ $item['texto'] }}</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                @endforeach
            </ul>
        </nav>
    </div>
</aside>

