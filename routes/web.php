<?php

use App\Http\Controllers\AjusteController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExistenciaMaterialController;
use App\Http\Controllers\FabricacionController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\PermisoUsuarioController;
use App\Http\Controllers\PlanificacionMaterialController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TipoMuebleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'admin.index' : 'login'))->name('inicio');

Auth::routes([
    'register' => false,
    'confirm' => false,
    'verify' => false,
    'reset' => config('autenticacion.recuperacion_habilitada') && config('mail.default') === 'smtp',
]);

// Panel administrativo
Route::get('/admin', [DashboardController::class, 'index'])->name('admin.index')->middleware(['auth', 'permiso:panel.consultar']);

// Rutas para usuarios
Route::get('/admin/usuarios', [UsuarioController::class, 'index'])->name('admin.usuarios.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.gestionar']);
Route::get('/admin/usuarios/create', [UsuarioController::class, 'create'])->name('admin.usuarios.create')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.gestionar']);
Route::post('/admin/usuarios/create', [UsuarioController::class, 'store'])->name('admin.usuarios.store')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.gestionar']);
Route::get('/admin/usuarios/{usuario}', [UsuarioController::class, 'show'])->name('admin.usuarios.show')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.gestionar']);
Route::get('/admin/usuarios/{usuario}/edit', [UsuarioController::class, 'edit'])->name('admin.usuarios.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.gestionar']);
Route::match(['put', 'patch'], '/admin/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('admin.usuarios.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.gestionar']);

// Rutas para clientes
Route::get('/admin/clientes', [ClienteController::class, 'index'])->name('admin.clientes.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:clientes.consultar']);
Route::get('/admin/clientes/create', [ClienteController::class, 'create'])->name('admin.clientes.create')->middleware(['auth', 'permiso:panel.consultar', 'permiso:clientes.consultar']);
Route::post('/admin/clientes/create', [ClienteController::class, 'store'])->name('admin.clientes.store')->middleware(['auth', 'permiso:panel.consultar', 'permiso:clientes.consultar']);
Route::get('/admin/clientes/{cliente}', [ClienteController::class, 'show'])->name('admin.clientes.show')->middleware(['auth', 'permiso:panel.consultar', 'permiso:clientes.consultar']);
Route::get('/admin/clientes/{cliente}/edit', [ClienteController::class, 'edit'])->name('admin.clientes.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:clientes.consultar']);
Route::match(['put', 'patch'], '/admin/clientes/{cliente}', [ClienteController::class, 'update'])->name('admin.clientes.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:clientes.consultar']);

// Rutas para materiales
Route::get('/admin/materiales', [MaterialController::class, 'index'])->name('admin.materiales.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::get('/admin/materiales/create', [MaterialController::class, 'create'])->name('admin.materiales.create')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::post('/admin/materiales/create', [MaterialController::class, 'store'])->name('admin.materiales.store')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::get('/admin/materiales/{material}', [MaterialController::class, 'show'])->name('admin.materiales.show')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::get('/admin/materiales/{material}/edit', [MaterialController::class, 'edit'])->name('admin.materiales.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::match(['put', 'patch'], '/admin/materiales/{material}', [MaterialController::class, 'update'])->name('admin.materiales.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);

// Existencias de materiales: cantidades en la unidad registrada.
Route::get('/admin/existencias', [ExistenciaMaterialController::class, 'index'])->name('admin.existencias.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::get('/admin/existencias/{material}', [ExistenciaMaterialController::class, 'show'])->name('admin.existencias.show')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::get('/admin/existencias/{material}/create', [ExistenciaMaterialController::class, 'create'])->name('admin.existencias.create')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::post('/admin/existencias/{material}/create', [ExistenciaMaterialController::class, 'store'])->name('admin.existencias.store')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::get('/admin/existencias/{material}/edit', [ExistenciaMaterialController::class, 'edit'])->name('admin.existencias.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::put('/admin/existencias/{material}', [ExistenciaMaterialController::class, 'update'])->name('admin.existencias.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);

// Rutas para categorías de materiales
Route::get('/admin/categorias', [CategoriaController::class, 'index'])->name('admin.categorias.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::get('/admin/categorias/create', [CategoriaController::class, 'create'])->name('admin.categorias.create')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.modificar']);
Route::post('/admin/categorias/create', [CategoriaController::class, 'store'])->name('admin.categorias.store')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.modificar']);
Route::get('/admin/categorias/{categoria}', [CategoriaController::class, 'show'])->name('admin.categorias.show')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.consultar']);
Route::get('/admin/categorias/{categoria}/edit', [CategoriaController::class, 'edit'])->name('admin.categorias.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.modificar']);
Route::match(['put', 'patch'], '/admin/categorias/{categoria}', [CategoriaController::class, 'update'])->name('admin.categorias.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.modificar']);

// Eliminación de registros sin relaciones; autorizada por políticas y servicio.
Route::delete('/admin/clientes/{cliente}', [ClienteController::class, 'destroy'])->name('admin.clientes.destroy')->middleware(['auth', 'permiso:panel.consultar', 'permiso:clientes.modificar']);
Route::delete('/admin/materiales/{material}', [MaterialController::class, 'destroy'])->name('admin.materiales.destroy')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.modificar']);
Route::delete('/admin/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('admin.categorias.destroy')->middleware(['auth', 'permiso:panel.consultar', 'permiso:materiales.modificar']);

// Administración de permisos y consulta de auditoría.
Route::get('/admin/permisos', [PermisoController::class, 'index'])->name('admin.permisos.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.asignar_roles']);
Route::get('/admin/usuarios/{usuario}/permisos', [PermisoUsuarioController::class, 'edit'])->name('admin.usuarios.permisos.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.asignar_roles']);
Route::put('/admin/usuarios/{usuario}/permisos', [PermisoUsuarioController::class, 'update'])->name('admin.usuarios.permisos.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.asignar_roles']);
Route::get('/admin/permisos/{rol}/edit', [PermisoController::class, 'edit'])->name('admin.permisos.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.asignar_roles']);
Route::put('/admin/permisos/{rol}', [PermisoController::class, 'update'])->name('admin.permisos.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.asignar_roles']);
Route::get('/admin/auditoria', [AuditoriaController::class, 'index'])->name('admin.auditoria.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:usuarios.gestionar']);

// Ajustes de la carpintería.
Route::get('/admin/ajustes', [AjusteController::class, 'index'])->name('admin.ajustes.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:configuracion.gestionar']);
Route::post('/admin/ajustes/create', [AjusteController::class, 'store'])->name('admin.ajustes.store')->middleware(['auth', 'permiso:panel.consultar', 'permiso:configuracion.gestionar']);
Route::get('/admin/ajustes/logo', [AjusteController::class, 'logo'])->name('admin.ajustes.logo')->middleware(['auth', 'permiso:panel.consultar']);

// Cotizaciones manuales: 4A, sin pedidos ni movimientos de fabricación.
Route::get('/admin/cotizaciones', [CotizacionController::class, 'index'])->name('admin.cotizaciones.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.consultar']);
Route::get('/admin/cotizaciones/create', [CotizacionController::class, 'create'])->name('admin.cotizaciones.create')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.crear']);
Route::post('/admin/cotizaciones/create', [CotizacionController::class, 'store'])->name('admin.cotizaciones.store')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.crear']);
Route::get('/admin/cotizaciones/{cotizacion}', [CotizacionController::class, 'show'])->name('admin.cotizaciones.show')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.consultar']);
Route::get('/admin/cotizaciones/{cotizacion}/edit', [CotizacionController::class, 'edit'])->name('admin.cotizaciones.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.modificar']);
Route::match(['put', 'patch'], '/admin/cotizaciones/{cotizacion}', [CotizacionController::class, 'update'])->name('admin.cotizaciones.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.modificar']);
Route::post('/admin/cotizaciones/{cotizacion}/aprobar', [CotizacionController::class, 'aprobar'])->name('admin.cotizaciones.aprobar')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.aprobar', 'permiso:cotizaciones.validar']);
Route::get('/admin/cotizaciones/{cotizacion}/pdf', [CotizacionController::class, 'pdf'])->name('admin.cotizaciones.pdf')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.consultar']);

// Perfil propio, disponible para ambas funciones sin permisos administrativos.
Route::get('/admin/perfil', [PerfilController::class, 'index'])->name('admin.perfil.index')->middleware('auth');
Route::put('/admin/perfil', [PerfilController::class, 'update'])->name('admin.perfil.update')->middleware('auth');
Route::put('/admin/perfil/password', [PerfilController::class, 'password'])->name('admin.perfil.password')->middleware('auth');

// Pedidos y planificación sencilla, sin descuentos automáticos.
Route::get('/admin/pedidos', [PedidoController::class, 'index'])->name('admin.pedidos.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.consultar']);
Route::get('/admin/pedidos/create', [PedidoController::class, 'create'])->name('admin.pedidos.create')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.crear']);
Route::post('/admin/pedidos/create', [PedidoController::class, 'store'])->name('admin.pedidos.store')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.crear']);
Route::get('/admin/pedidos/{pedido}', [PedidoController::class, 'show'])->name('admin.pedidos.show')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.consultar']);
Route::get('/admin/pedidos/{pedido}/edit', [PedidoController::class, 'edit'])->name('admin.pedidos.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.modificar']);
Route::put('/admin/pedidos/{pedido}', [PedidoController::class, 'update'])->name('admin.pedidos.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.modificar']);
Route::post('/admin/pedidos/{pedido}/estado', [PedidoController::class, 'cambiarEstado'])->name('admin.pedidos.estado')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.modificar']);
Route::get('/admin/planificacion', [PlanificacionMaterialController::class, 'index'])->name('admin.planificacion.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:planificacion.consultar']);
Route::get('/admin/planificacion/{pedido}', [PlanificacionMaterialController::class, 'show'])->name('admin.planificacion.show')->middleware(['auth', 'permiso:panel.consultar', 'permiso:planificacion.consultar']);
Route::get('/admin/planificacion/{pedido}/edit', [PlanificacionMaterialController::class, 'edit'])->name('admin.planificacion.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:planificacion.modificar']);
Route::put('/admin/planificacion/{pedido}', [PlanificacionMaterialController::class, 'update'])->name('admin.planificacion.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:planificacion.modificar']);

// Registro operativo de materiales utilizados; no modifica saldos ni precios.
Route::get('/admin/pedidos/{pedido}/fabricacion/edit', [FabricacionController::class, 'edit'])->name('admin.fabricacion.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.consultar', 'permiso:planificacion.consultar']);
Route::put('/admin/pedidos/{pedido}/fabricacion', [FabricacionController::class, 'update'])->name('admin.fabricacion.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.consultar', 'permiso:planificacion.consultar']);

// Revisión final y entrega: las capacidades y estados se revalidan en servidor.
Route::post('/admin/pedidos/{pedido}/materiales/validar', [FabricacionController::class, 'validar'])->name('admin.fabricacion.validar')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.consultar', 'permiso:planificacion.consultar']);
Route::post('/admin/pedidos/{pedido}/terminar', [FabricacionController::class, 'terminar'])->name('admin.fabricacion.terminar')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.consultar', 'permiso:planificacion.consultar']);
Route::post('/admin/pedidos/{pedido}/entregar', [PedidoController::class, 'entregar'])->name('admin.pedidos.entregar')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.consultar']);

// Casos históricos: consulta y revisión; sin captura manual de antecedentes externos.
Route::get('/admin/casos', [App\Http\Controllers\CasoHistoricoController::class, 'index'])->name('admin.casos.index')->middleware(['auth','permiso:panel.consultar','permiso:casos.consultar']);
Route::get('/admin/casos/{caso}', [App\Http\Controllers\CasoHistoricoController::class, 'show'])->name('admin.casos.show')->middleware(['auth','permiso:panel.consultar','permiso:casos.consultar']);
Route::post('/admin/casos/{caso}/revisar', [App\Http\Controllers\CasoHistoricoController::class, 'revisar'])->name('admin.casos.revisar')->middleware(['auth','permiso:panel.consultar','permiso:casos.consultar']);


// Consulta de similitud de un detalle guardado: no aplica ni modifica datos.
Route::post('/admin/cotizaciones/similares', [App\Http\Controllers\SimilitudController::class, 'buscar'])->name('admin.similitud.buscar')->middleware(['auth','permiso:panel.consultar','permiso:cotizaciones.consultar','permiso:casos.consultar','permiso:costos.consultar']);
Route::post('/admin/cotizaciones/{cotizacion}/similares', [App\Http\Controllers\SimilitudController::class, 'buscar'])->name('admin.similitud.buscarEditar')->middleware(['auth','permiso:panel.consultar','permiso:cotizaciones.consultar','permiso:casos.consultar','permiso:costos.consultar']);

Route::get('/admin/cotizaciones/{cotizacion}/detalles/{detalle}/similares', [App\Http\Controllers\SimilitudController::class, 'show'])->name('admin.similitud.show')->middleware(['auth','permiso:panel.consultar','permiso:cotizaciones.consultar','permiso:casos.consultar','permiso:costos.consultar']);


// Pagos registrados: consulta y edición exclusivas del propietario; sin borrado.
Route::get('/admin/pagos', [App\Http\Controllers\PagoController::class, 'index'])->name('admin.pagos.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pagos.consultar']);
Route::get('/admin/pagos/pedido/{pedido}', [App\Http\Controllers\PagoController::class, 'pedido'])->name('admin.pagos.pedido')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pagos.consultar']);
Route::get('/admin/pagos/create', [App\Http\Controllers\PagoController::class, 'create'])->name('admin.pagos.create')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pagos.gestionar']);
Route::post('/admin/pagos/create', [App\Http\Controllers\PagoController::class, 'store'])->name('admin.pagos.store')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pagos.gestionar']);
Route::get('/admin/pagos/{pago}', [App\Http\Controllers\PagoController::class, 'show'])->name('admin.pagos.show')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pagos.consultar']);
Route::get('/admin/pagos/{pago}/edit', [App\Http\Controllers\PagoController::class, 'edit'])->name('admin.pagos.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pagos.gestionar']);
Route::put('/admin/pagos/{pago}', [App\Http\Controllers\PagoController::class, 'update'])->name('admin.pagos.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pagos.gestionar']);


// Reportes administrativos de solo lectura: propietario y permisos del módulo consultado.
Route::get('/admin/reportes', [App\Http\Controllers\ReporteController::class, 'index'])->name('admin.reportes.index')->middleware(['auth','permiso:panel.consultar','permiso:reportes.administrativos']);
Route::get('/admin/reportes/{tipo}', [App\Http\Controllers\ReporteController::class, 'show'])->name('admin.reportes.show')->middleware(['auth','permiso:panel.consultar','permiso:reportes.administrativos']);
Route::get('/admin/reportes/{tipo}/pdf', [App\Http\Controllers\ReporteController::class, 'pdf'])->name('admin.reportes.pdf')->middleware(['auth','permiso:panel.consultar','permiso:reportes.administrativos']);


// Tipos de muebles: consulta y administración del catálogo de cotizaciones.
Route::get('/admin/tipos-mueble', [TipoMuebleController::class, 'index'])->name('admin.tipos_mueble.index')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.consultar']);
Route::get('/admin/tipos-mueble/create', [TipoMuebleController::class, 'create'])->name('admin.tipos_mueble.create')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.consultar']);
Route::post('/admin/tipos-mueble/create', [TipoMuebleController::class, 'store'])->name('admin.tipos_mueble.store')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.consultar']);
Route::get('/admin/tipos-mueble/{tipoMueble}', [TipoMuebleController::class, 'show'])->name('admin.tipos_mueble.show')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.consultar']);
Route::get('/admin/tipos-mueble/{tipoMueble}/edit', [TipoMuebleController::class, 'edit'])->name('admin.tipos_mueble.edit')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.consultar']);
Route::match(['put', 'patch'], '/admin/tipos-mueble/{tipoMueble}', [TipoMuebleController::class, 'update'])->name('admin.tipos_mueble.update')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.consultar']);

// Revisiones del acuerdo durante fabricación: rutas explícitas, autorización en servidor.

// Edición comercial del mismo pedido mediante las tablas de cotización existentes.

// Confirmación del importe actual de la cotización del pedido.
Route::post('/admin/pedidos/{pedido}/cotizacion/aceptar', [PedidoController::class, 'aceptarCotizacion'])->name('admin.pedidos.cotizacion.aceptar')->middleware(['auth', 'permiso:panel.consultar', 'permiso:pedidos.consultar']);

// Retirada de preliminares y rechazo comercial sin pedido asociado.
Route::delete('/admin/cotizaciones/{cotizacion}', [App\Http\Controllers\CotizacionController::class, 'destroy'])->name('admin.cotizaciones.destroy')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.eliminar']);
Route::post('/admin/cotizaciones/{cotizacion}/rechazar', [App\Http\Controllers\CotizacionController::class, 'rechazar'])->name('admin.cotizaciones.rechazar')->middleware(['auth', 'permiso:panel.consultar', 'permiso:cotizaciones.rechazar']);
