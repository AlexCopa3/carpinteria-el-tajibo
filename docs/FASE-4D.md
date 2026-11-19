# Fase 4D — Revisión final, cierre y entrega

Entrega del 9 de noviembre de 2026. El usuario aprobó [el diseño detallado](PROPUESTA-FASE-4D.md) antes de esta implementación. Se mantienen las convenciones escolares y la aplicación independiente; el sistema escolar no se modifica. Fase 5, casos y CBR/k-NN permanecen pendientes de autorización.

## 1. Uso cotidiano

El flujo completo es **Aceptado → En fabricación → Terminado → Entregado**. Las acciones se encuentran en Ver pedido, sin otro módulo de menú ni un formulario extenso.

1. Durante En fabricación se completan las cantidades utilizadas en la pantalla de 4C. Vacío significa pendiente; cero expreso, no utilizado.
2. Propietario o maestro autorizado pulsa Validar materiales y confirma mediante SweetAlert2. Cada detalle debe tener materiales y todas las cantidades utilizadas deben estar registradas, con responsable/fecha de uso. Se conserva quién y cuándo validó.
3. El propietario confirma mediante casilla el precio previamente acordado y pulsa Terminar fabricación. Si todavía no se revisaron materiales, puede validarlos y terminar en la misma operación marcando la segunda casilla, siempre que tenga el permiso correspondiente.
4. Terminado bloquea edición del pedido, planificación y uso de materiales. El propietario pulsa Registrar entrega, con observación opcional y confirmación; fecha y actor son del servidor.

Si cambia efectivamente planificación, materiales, cantidades u observación operativa de uso después de validar, se invalida la revisión y se conserva su auditoría anterior. Guardar el mismo uso o planificación no invalida la revisión. Las notas generales del pedido y su fecha prevista no la invalidan por sí solas. La planificación conserva su versionado/auditoría preexistentes de 4B incluso cuando el envío no cambia la revisión.

Se diferencian visualmente los cuatro estados mediante iconos y colores. Seguimiento muestra fecha, responsable y nota de entrega; las fechas se obtienen de estados_pedido. Los enlaces a planificación en pedidos cerrados se presentan como consulta. La pantalla conserva iconos, botones redondeados, acento menta y adaptación móvil/claro/oscuro.

## 2. Precio, permisos y conservación

Confirmar precio ratifica precio_total_acordado y los precios de sus detalles. El servidor verifica la suma y correspondencia con los detalles de la cotización aprobada, usando aritmética decimal exacta. No recibe un precio nuevo ni recalcula un cobro por diferencias de consumo. La cotización aprobada y su PDF permanecen protegidos.

| Acción | Comprobaciones |
|---|---|
| Validar materiales | Cuenta activa; pedidos.consultar/modificar, planificacion.consultar/modificar y materiales.validar; solo En fabricación. Disponible a propietario y maestro autorizados. |
| Terminar | Solo propietario activo; consultas de pedido/planificación, pedidos.modificar y cotizaciones.validar; revisión completa/vigente y confirmación expresa del precio. Si valida materiales en la misma acción necesita también la capacidad de validarlos. |
| Entregar | Solo propietario activo; pedidos.consultar/modificar; exclusivamente desde Terminado y con validaciones registradas. |

Política, Form Request y servicio comprueban el acceso. No se modificaron permisos, roles ni asignaciones existentes. Actor, fecha, estado de destino, validaciones y precios no se aceptan desde campos privilegiados del navegador.

Se bloquea cualquier salto, retroceso o cierre con cantidades pendientes. UUID/huella, versión y bloqueo transaccional impiden duplicar eventos: repetir exactamente una transición devuelve el resultado sin volver a ejecutarla; la misma clave con datos distintos se rechaza. Un fallo de auditoría revierte estado, validaciones y evento.

No se descuentan existencias, reservan materiales ni generan movimientos automáticos. No se capturan costos reales de fabricación, pagos o nuevas ganancias; no se presume que los costos actuales del catálogo sean gastos comprobados. No se implementó reapertura de pedidos cerrados. La entrega no exige pago completo porque ese módulo sigue pendiente.

No se crean casos todavía. Fase 5 deberá incorporar de forma idempotente los trabajos elegibles ya terminados/validados, incluidos los entregados antes de esa integración, sin confundir sus estimaciones con costos reales. Los ejemplos mantienen su procedencia simulada interna: cerrar un pedido ficticio no lo convierte en antecedente real ni acredita validación humana.

## 3. Código y estructura

| Área | Archivos |
|---|---|
| Migración aditiva | database/migrations/2026_11_09_210000_add_validaciones_to_pedidos_table.php |
| Modelo/política | app/Models/Pedido.php y app/Policies/PedidoPolicy.php |
| Validación de las tres acciones | app/Http/Requests/Pedidos/ValidarCierrePedidoRequest.php; reglas explícitas por acción |
| Cierre transaccional | app/Services/CierrePedidoService.php |
| Invalidación de revisión | Integrada en FabricacionService y PlanificacionMaterialService, dentro de sus bloqueos/transacciones |
| Controladores explícitos | FabricacionController::validar/terminar y PedidoController::entregar; métodos anteriores conservados |
| Interfaz | admin/fabricacion/_cierre.blade.php, _resumen, ficha de pedido y vistas de estados de pedidos/planificación |
| Estado compartido | resources/views/components/estado-pedido.blade.php y estilos locales resources/css/app.css |
| Confirmaciones | resources/js/pedidos.js; recursos compilados en public/build |
| Pruebas | tests/Feature/CierrePedidoTest.php, tests/browser/fase4b.cjs ampliado y tests/mysql/cierre-worker.php |

Se eligió CierrePedidoService para reunir las tres operaciones críticas y no recargar los controladores ni el servicio de uso de materiales. No se añadieron repositorios genéricos, tablas/modelos duplicados o dependencias nuevas. Se conservaron los métodos CRUD y rutas anteriores. Hay **76 entradas de rutas**, las 73 existentes más:

- POST /admin/pedidos/{pedido}/materiales/validar — admin.fabricacion.validar.
- POST /admin/pedidos/{pedido}/terminar — admin.fabricacion.terminar.
- POST /admin/pedidos/{pedido}/entregar — admin.pedidos.entregar.

## 4. Esquema y aplicación controlada

Base confirmada: **carpinteria_el_tajibo**, MySQL local. Diagnóstico previo: 27 tablas, 22 migraciones, 11 pedidos y 31 materiales de pedido; coincide con el cierre 4C.

Se aplicó únicamente la migración aditiva aprobada, sin seeders ni cambios a las 22 migraciones anteriores (SHA-256 cotejados):

| Columna nueva en pedidos | Definición |
|---|---|
| materiales_validados_por_id | FK nullable a users.id, ON DELETE RESTRICT |
| materiales_validados_at | TIMESTAMP nullable |
| precio_confirmado_por_id | FK nullable a users.id, ON DELETE RESTRICT |
| precio_confirmado_at | TIMESTAMP nullable |

Resultado: **27 tablas, 23 migraciones alineadas y 42 FK**. Las cuatro columnas están NULL en los 11 pedidos anteriores. No se rellenaron validaciones, usos, cierres o entregas ficticias en MySQL operativo. Los registros y las columnas anteriores de todas las tablas se compararon mediante conteos/hashes; usuarios, contraseñas, permisos, materiales, existencias, movimientos, cotizaciones, pedidos y precios se conservaron íntegramente. La estructura final coincide con el ensayo.

No se eliminaron tablas, columnas o registros. No se utilizaron migrate:fresh, db:wipe ni restablecimientos en MySQL. La actualización se ejecutó con mantenimiento breve y se comprobó que la aplicación quedara disponible.

## 5. Respaldo y MySQL aislado

Directorio privado: storage/app/private/fase4d-2026-11-09/.

- Respaldo completo: respaldo-completo.sql, **376089 bytes**.
- SHA-256: **4ce88c8d19029d91a784f62e5f45768c16498dc5a6ab2f7488f98900d043fe56**.
- Restauración idéntica verificada en **carpinteria_el_tajibo_ensayo_4d_20261109160957_556e44**: 27 tablas, estructura y datos anteriores cotejados.
- Migración ensayada primero en ese clon, preservando columnas/filas originales, cuatro metadatos NULL y ambas FK restrictivas; referencias inexistentes rechazadas por MySQL.
- Pruebas de validación, corrección que invalida, cero expreso, cierre/entrega idempotentes y rollback íntegro; precios, saldos, movimientos, usuarios y permisos sin cambios.
- Cuatro parejas de procesos MySQL: validación/corrección, cierre/corrección, dos cierres y dos entregas. Se aceptó una operación compatible con la versión/estado; las otras se rechazaron por versión o autorización de estado. Un único evento por transición.
- El origen se cotejó nuevamente contra el respaldo antes de aplicar. El clon conserva exclusivamente fixtures de ensayo adicionales, sin transferirlas al origen.

Evidencias privadas: diagnostico-inicial.json, respaldo-verificado.json, estructura-ensayada.json, ensayo-aprobado.json, aplicacion-verificada.json y verificacion-local.json. Los auxiliares de este despliegue no son instaladores para volver a ejecutar; el respaldo y el clon contienen datos y deben mantenerse privados.

## 6. Resultados de pruebas

| Prueba | Resultado |
|---|---|
| Suite completa PHP, SQLite aislado | **192 aprobadas, 2033 aserciones, 192.70 s** |
| CierrePedidoTest incluido en la suite | 14 pruebas: permisos, datos incompletos, precio ratificado, invalidación, cero, estados, idempotencia, manipulación, rollback y actor inactivo |
| Verificación posterior de vistas 4C/4D | **30 aprobadas, 265 aserciones** tras ajustar textos y enlace de consulta |
| Edge/Playwright, SQLite temporal | **91 comprobaciones aprobadas**, sin errores JavaScript |
| MySQL restaurado | Seis grupos de comprobaciones, incluidas cuatro carreras concurrentes con procesos reales |
| MySQL operativo tras aplicar | Seis renderizados de solo lectura con propietario/maestro, nuevas referencias sin huérfanos y cero cambios de datos |
| Recursos/rutas | Vite compilado, PHP revisado y 76 entradas verificadas |

Navegador: maestro valida o cancela con SweetAlert2 y no puede cerrar por interfaz ni endpoint; propietario confirma precio, termina, consulta y entrega; reenvíos idempotentes y edición cerrada bloqueada. También se revisaron filtros de Entregado y saldos intactos, regresión de las fases previas, menú fijo, claro/oscuro y móvil. Las capturas de cierre incluyen 1440 y 390 px y oscuro móvil, sin desborde horizontal de página.

Las operaciones de escritura de prueba se hicieron en SQLite temporal o el clon MySQL. El chequeo operativo renderiza controladores/Blade con MySQL: no es una sesión HTTP real autenticada en esa base. No se verifican correo real, rendimiento productivo, precisión CBR/k-NN ni validaciones humanas de fabricación.

## 7. Capturas y consulta

- [Resultado completo de navegador](capturas/fase4d/resultado-browser.json).
- [Revisión final en escritorio](capturas/fase4d/cierre-revision-1440.png).
- [Revisión final móvil](capturas/fase4d/cierre-revision-390.png).
- [Revisión final en oscuro](capturas/fase4d/cierre-revision-oscuro-390.png).
- [Pedido terminado](capturas/fase4d/pedido-terminado-1440.png).
- [Pedido entregado](capturas/fase4d/pedido-entregado-1440.png).

Las imágenes usan datos sintéticos de pruebas aisladas. La próxima fase requiere nueva revisión y aprobación expresa.
