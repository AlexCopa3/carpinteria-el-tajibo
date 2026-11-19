<?php

namespace App\Providers;

use App\Models\Ajuste;
use App\Policies\AjustePolicy;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\Material;
use App\Models\User;
use App\Policies\CategoriaPolicy;
use App\Policies\ClientePolicy;
use App\Policies\CotizacionPolicy;
use App\Policies\MaterialPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Ajuste::class, AjustePolicy::class);
        Gate::policy(Cotizacion::class, CotizacionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Cliente::class, ClientePolicy::class);
        Gate::policy(Categoria::class, CategoriaPolicy::class);
        Gate::policy(Material::class, MaterialPolicy::class);
        Gate::policy(\App\Models\TipoMueble::class, \App\Policies\TipoMueblePolicy::class);
        Gate::policy(\App\Models\Pedido::class, \App\Policies\PedidoPolicy::class);
        Gate::policy(\App\Models\CasoHistorico::class, \App\Policies\CasoHistoricoPolicy::class);
        Gate::policy(\App\Models\Pago::class, \App\Policies\PagoPolicy::class);
        Paginator::useBootstrapFour();
        View::composer(['adminlte::partials.sidebar.left-sidebar', 'adminlte::page'], function ($view) {
            $ajusteNegocio = Schema::hasTable('ajustes') ? Ajuste::first() : null;
            $view->with('ajusteNegocio', $ajusteNegocio);
        });
    }
}
