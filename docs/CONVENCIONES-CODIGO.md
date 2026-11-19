# Convenciones de código

> Estado posterior vigente (9/11/2026): Casos históricos usa una sola búsqueda, sin selector o modo separado. Perfil único config/perfiles/similitud-casos-historicos-v1.json. Los registros conservan su procedencia y revisiones. Véase [flujo actualizado](FLUJO-COTIZACION-CON-CASOS.md); esta decisión sustituye las instrucciones anteriores de seleccionar demostración.

Vigentes tras la limpieza del 9 de noviembre de 2026. Se conserva el estilo familiar del sistema escolar, sin trasladar sus entidades ni quitar controles del servidor.

## Ejemplos reales del sistema escolar

Se volvieron a leer, sin modificar:

| Archivo en C:/wamp64/www/sisgestionescolarlaravel | Estructura encontrada |
|---|---|
| app/Http/Controllers/GestionController.php | index/create/store/show/edit/update/destroy; validate en store/update; new Gestion, asignaciones y save; compact y mensaje/icono |
| app/Models/Grado.php | table, fillable y relaciones nivel belongsTo, paralelos/matriculaciones/asignaciones hasMany |
| database/migrations/2025_07_10_211238_create_materias_table.php | Una creación de tabla, id automático, timestamps, up/down |
| routes/web.php | Rutas individuales /admin, POST /create, PUT update, DELETE y nombres admin.modulo.accion |
| resources/views/admin/gestiones/create.blade.php | extends adminlte::page, secciones, row/col, card, form-group, CSRF, old, error y botones con iconos |

Fragmento real de GestionController:

```php
$request->validate([
    'nombre' => 'required|max:255|unique:gestions',
]);
$gestion = new Gestion();
$gestion->nombre = $request->nombre;
$gestion->save();
```

Se conserva el flujo explícito. No se trasladan nombres de tablas escolares, reglas concretas ni la ausencia de controles de dominio.

## Organización vigente

- Controladores singulares en app/Http/Controllers, métodos index/create/store/show/edit/update/destroy cuando correspondan y variables descriptivas.
- CRUD sencillos: Request, autorización, request->validate, asignación explícita de campos y save. Clientes/categorías conservan transacción y auditoría; los servicios relacionados permanecen.
- Operaciones complejas: CotizacionService, PedidoService, PlanificacionMaterialService y ExistenciaMaterialService, con Form Requests donde están implementados. Mantener decimales exactos, transacciones, bloqueos, control de versión e idempotencia.
- Modelos Eloquent con tabla, propiedades/casts y relaciones claras. La herencia RegistroInmutable conserva eventos/movimientos.
- Migraciones de negocio por tabla; Laravel y Spatie conservan sus agrupaciones. No borrar ni consolidar una migración aplicada sin un procedimiento autorizado.
- Rutas individuales /admin y nombres admin.*, sin Route::resource. Preservar nombres, verbos, parámetros enlazados y middleware.
- Vistas por módulo, principalmente index/create/edit/show y parciales cuando conviene. Campos explícitos, old, errores, CSRF y @method. Evitar abstracciones que oculten los campos o repitan lógica de negocio en Blade.

## Ejemplos de la carpintería

ClienteController valida los datos dentro de store/update. El siguiente extracto omite campos del método real y no sustituye su transacción ni auditoría:

~~~php
$this->authorize('create', Cliente::class);
$datos = $request->validate([
    'nombre' => ['required', 'string', 'max:255'],
    // Se conservan las otras reglas reales del formulario.
]);
$cliente = new Cliente();
$cliente->nombre = $datos['nombre'];
// Se asignan los demás campos validados antes de guardar.
$cliente->save();
~~~

Relación actual de Material:

~~~php
public function categoriaMaterial()
{
    return $this->belongsTo(Categoria::class, 'categoria_id');
}

public function existencia()
{
    return $this->hasOne(ExistenciaMaterial::class, 'material_id');
}
~~~

Ruta explícita del proyecto:

~~~php
Route::post('/admin/clientes/create', [App\Http\Controllers\ClienteController::class, 'store'])
    ->name('admin.clientes.store')
    ->middleware(['auth', 'permiso:panel.consultar', 'permiso:clientes.consultar']);
~~~

El middleware de consulta no autoriza crear: la política create se verifica dentro del controlador. Mantener los parámetros {cliente}, {material}, {usuario} y {pedido} de sus rutas.

Cada migración conserva id automático, columnas y up/down. La migración de Ajustes crea ajustes con $table->id(); el controlador busca su primera ficha y no asigna id=1 manualmente.

Las vistas usan @extends('adminlte::page'), @section('content_header'), @section('content'), x-alerts, tarjetas y formularios por módulo. No modificar vendor; los overrides están en resources/views/vendor/adminlte.

## Autorización y conservación

Las políticas, Spatie, Acceso y middleware permanecen aunque el escolar usara menos controles. Los maestros consultan costos y estimaciones, pero no ganancia, aprobación final, administración de usuarios ni pagos. Las cotizaciones aprobadas no se reescriben; el pedido conserva el precio acordado.

La auditoría toma el actor de la sesión, no de campos enviados. No registra secretos. El borrado de catálogo está condicionado por relaciones e historial; las cuentas se desactivan. Movimientos y estados son inmutables. No descontar existencias al iniciar fabricación ni al modificar planificación.

## Presentación y mensajes

Formularios con row/col y dos o tres columnas según el campo; se apilan en móvil. Texto obligatorio coherente con validación y errores accesibles. Los iconos de etiquetas tienen aria-hidden=true y no sustituyen el texto ni su asociación label/for.

Botones con igual tipografía, iconos, separación uniforme y esquinas redondeadas. Registrar azul, actualizar turquesa, eliminar coral, Ver celeste y Volver/Cancelar plomo. Acento menta (#84dfc3), menú azul oscuro y paleta de acciones conservada. Los logos personalizados no se recolorean.

SweetAlert2 local se centraliza en admin/partials/alertas y el layout. Las sesiones mensaje/icono y swal se codifican con @json; la confirmación de eliminación utiliza el formulario real y validación del servidor.

El selector x-theme-toggle usa tajibo.tema en localStorage. Los estilos oscuros se delimitan por html[data-theme="dark"]. Comprobar tablas, campos y mensajes en ambos temas; no crear columnas de usuario para esta preferencia.

## Datos y pruebas

Los IDs visibles son los valores id normales, sin PED-/COT- ni ceros agregados; UUID internos siguen protegiendo operaciones repetidas. Los seeders se organizan por módulo y los coordina DatosSistemaSeeder. En instalación local DatabaseSeeder los incluye conforme a [la guía de instalación](INSTALACION-CON-SEED.md), sin claves de acceso predeterminadas.

No agregar pruebas que solo repitan estilos; conservar pruebas de permisos, cálculos, integridad, estados e idempotencia y usar datos aislados. Antes de refactorizaciones importantes presentar el alcance. La limpieza de archivos no autoriza cambiar consultas, precios, permisos ni requisitos.

Referencias: [arquitectura](ARQUITECTURA.md), [datos](DISENO-DATOS.md), [permisos](PERMISOS.md), [interfaz](DISENO-INTERFAZ.md) y [verificación](VERIFICACION.md).

En 4D, FabricacionController::validar/terminar y PedidoController::entregar conservan métodos explícitos. ValidarCierrePedidoRequest tiene reglas por acción y CierrePedidoService coordina versión, bloqueo, revisión y transiciones; no se copia esta lógica a los controladores. El componente estado-pedido comparte icono/color de los cuatro estados sin duplicar reglas de negocio. Véase [entrega 4D](FASE-4D.md).

En 5A, CasoHistoricoController mantiene index/show/revisar explícitos y Blade en admin/casos. No hay create/edit de históricos externos porque el alcance los obtiene de pedidos o demostración. RetencionCasoService/RevisionCasoService y RevisarCasoRequest conservan transacción, bloqueos, versión, auditoría y autorización. Las dos tablas tienen migraciones individuales e ID autoincremental; lote/posición del seeder son solo claves internas. [Entrega 5A](FASE-5A.md).

## Ejemplo incorporado en 5B

SimilitudController conserva show(Request $request, Cotizacion $cotizacion, DetalleCotizacion $detalle, RecuperacionCasosService $service), autorización y $request->validate para un selector sencillo. RecuperacionCasosService reúne elegibilidad/perfil y ComparadorCasosService el cálculo exacto, sin capa genérica. GET explícita /admin/cotizaciones/{cotizacion}/detalles/{detalle}/similares, admin.similitud.show. Vista admin/similitud/show.blade.php con @extends/@section y consulta del detalle guardado, sin CRUD artificial o duplicación de formularios. [Entrega](FASE-5B.md).

## Búsqueda durante el registro

SimilitudController.buscar conserva Request->validate, autorización y rutas POST explícitas. RecuperacionCasosService comparte el cálculo entre formulario y ficha guardada; PropuestaCasoService concentra adaptación numérica y procedencia. CotizacionService mantiene transacción/auditoría y cálculos finales. Las tarjetas se reutilizan mediante admin/similitud/_resultados.blade.php, sin duplicar el motor. [Flujo autorizado](FLUJO-COTIZACION-CON-CASOS.md).

## Pagos 6A

PagoController conserva index/create/store/show/edit/update, rutas explícitas /admin con POST create y PUT update, y Blade por operación. GuardarPagoRequest comparte reglas con PagoService; guardado con new Pago/asignaciones/save en el servicio transaccional, fuera del controlador por sus bloqueos, idempotencia y auditoría. Dinero decimal sin float y lectura actual bloqueada para impedir sobrepago concurrente. [Entrega](FASE-6A.md).

## Permisos individuales

PermisoUsuarioController mantiene edit/update y validación explícita dentro del controlador; las rutas GET/PUT siguen /admin y admin.usuarios.permisos.*. UserPolicy conserva la autorización y PermisosUsuarioService coordina transacción/auditoría sin modificar los roles heredados. La vista permanece en admin/permisos; se reutilizan las tablas de Spatie.

## Autorización delegable — 13/11/2026

Implementación aplicada y verificada el 13 de noviembre de 2026, tras aprobación del usuario, respaldo completo restaurado y ensayo aislado. Permisos generales editables para todos los módulos; excepciones individuales Según su rol / Permitir / Denegar con prioridad sobre Spatie. El propietario conserva las cuatro capacidades de recuperación documentadas. Base carpinteria_el_tajibo: 31 tablas, 27 migraciones, 55 FK y 48 permisos; propietario 48 y maestro sus mismos 15. Sin delegaciones automáticas al maestro, cambios de contraseñas ni pérdidas de registros. Informe: docs/INFORME-PERMISOS-GENERALES-INDIVIDUALES.md. Esta decisión sustituye las restricciones históricas por rol; conservar las validaciones del negocio y no avanzar a fases nuevas.

Suite final: 293 pruebas y 2920 aserciones aprobadas; 25 grupos de comprobación en Edge con datos temporales, sin errores JavaScript. Nueve comprobaciones MySQL en clon con escrituras revertidas, más renderizado local CLI de solo lectura. Las pruebas no acreditan precisión académica del recomendador ni validación humana.

Se conserva el CRUD y Blade actuales, modelos Eloquent y servicios transaccionales. La resolución central en User es necesaria para que una denegación prevalezca también en @can y el menú de AdminLTE; ocultar botones o comprobar solo el rol sería insuficiente. Nuevas reglas en políticas y servicios, no lógica de autorización repetida en cada controlador.



## Edición directa de la cotización vinculada

CotizacionController mantiene edit/update; GET solo muestra el formulario. CotizacionService modifica las mismas filas dentro de una transacción. PedidoController::aceptarCotizacion y PedidoService::aceptarCotizacion confirman el nuevo acuerdo mediante POST admin.pedidos.cotizacion.aceptar. Sin controladores o servicios de revisiones/cambios, sin ciclos ni copias de líneas. Conservar Form Request, políticas, Spatie, auditoría general y bloqueo/versión. Ver EDICION-DIRECTA-COTIZACION.md.


## Estados y eliminación segura de cotizaciones

CotizacionController incorpora destroy y rechazar con Request y validación explícita; CotizacionService mantiene la transición auditada y EliminacionService la validación compartida de dependencias. Rutas DELETE admin.cotizaciones.destroy y POST admin.cotizaciones.rechazar, políticas y permisos Spatie específicos. El componente eliminar utiliza el mismo motivo de bloqueo del servidor; no duplicar reglas en Blade. El estado se muestra mediante Cotizacion.estado_nombre siguiendo la convención de Pedido. No se crean tablas ni mecanismos de versiones históricas.
