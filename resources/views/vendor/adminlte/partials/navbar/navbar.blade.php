<nav class="main-header navbar navbar-expand navbar-white navbar-light navbar-profile-feature" aria-label="Barra superior">
    <ul class="navbar-nav align-items-center">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Abrir o cerrar menú" aria-controls="sidebar-menu" aria-expanded="false">
                <i class="fas fa-bars" aria-hidden="true"></i>
            </a>
        </li>
        <li class="nav-item d-none d-xl-block">
            <span class="workspace-label workspace-classic">
                <span>Tienda & taller</span>
                <span class="workspace-location">La Paz · El Alto</span>
            </span>
        </li>
    </ul>
    <ul class="navbar-nav ml-auto align-items-center header-toolbar">
        <li class="nav-item"><x-theme-toggle /></li>
        <li class="nav-item dropdown header-account">
            <button class="btn dropdown-toggle header-account-toggle" id="header-account-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-controls="header-account-menu" aria-label="Opciones de mi cuenta">
                <span class="profile-avatar" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <span class="header-account-copy">
                    <strong title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</strong>
                    <small id="header-account-role">
                        @if(auth()->user()->hasRole('propietario'))
                            Propietario
                        @elseif(auth()->user()->hasRole('maestro'))
                            Maestro carpintero
                        @else
                            Usuario
                        @endif
                    </small>
                </span>
            </button>
            <div class="dropdown-menu dropdown-menu-right header-account-menu" id="header-account-menu" aria-labelledby="header-account-toggle">
                <a class="dropdown-item" href="{{ route('admin.perfil.index') }}" aria-label="Mi perfil">
                    <i class="far fa-user" aria-hidden="true"></i>Mi perfil
                </a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}" class="account-logout-form">
                    @csrf
                    <button class="dropdown-item account-logout" type="submit" aria-label="Salir">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i>Salir
                    </button>
                </form>
            </div>
        </li>
    </ul>
</nav>
