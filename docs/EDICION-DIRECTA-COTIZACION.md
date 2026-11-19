# Edición directa de la cotización del pedido

Fecha: 16 de noviembre de 2026. Aplicación realizada exclusivamente en carpinteria_el_tajibo.

## Resultado

Se retiró el mecanismo interno de cotización original, revisiones y cambios comerciales. La edición y el consumo modifican la misma cotización y sus líneas actuales. No se copian cabeceras ni detalles para conservar versiones. Abrir el formulario no escribe en la base.

Guardar recalcula materiales, mano de obra, otros costos y ganancia configurada, y deja pendiente la aprobación. El importe aceptado del pedido se conserva hasta aprobar los precios y confirmar la aceptación del cliente mediante Actualizar pedido. Los pagos no se duplican ni se modifican. Se mantienen permisos efectivos, bloqueos, transacciones, versiones técnicas, validaciones, seguimiento de estados y auditoría general.

## Código retirado y adaptación

Retirados RevisionPedidoService, CambioPedidoService, sus dos controladores, CambioPedido/MaterialCambioPedido, GuardarCambioPedidoRequest, las vistas de revisión/cambios y su PDF. Retiradas las rutas admin.pedidos.revision.* y admin.pedidos.cambios.*, JavaScript/CSS exclusivos, pruebas del mecanismo anterior y seis documentos que describían su funcionamiento sustituido.

CotizacionController mantiene edit/update y Form Request. CotizacionService guarda directamente e incorpora sincronizarUso; FabricacionService lo utiliza. PedidoController/PedidoService incorporan aceptarCotizacion; ruta POST admin.pedidos.cotizacion.aceptar. Se adapta la autorización, el cierre, la retención de casos y las vistas correspondientes. Se mantiene la organización escolar: controladores por entidad, modelos Eloquent, rutas explícitas /admin, Blade por módulo y servicios para operaciones complejas.

Archivos principales: app/Models/{Cotizacion,DetalleCotizacion,MaterialCotizacion,Pedido,DetallePedido}.php; app/Policies/{CotizacionPolicy,PedidoPolicy}.php; app/Services/{CotizacionService,PedidoService,FabricacionService,CierrePedidoService,RetencionCasoService,RecuperacionCasosService}.php; app/Http/Controllers/{CotizacionController,PedidoController,FabricacionController}.php; routes/web.php; resources/views/admin/cotizaciones/*; resources/views/admin/pedidos/{show,edit,_aceptar-cotizacion}.blade.php; resources/js/{cotizaciones,pedidos}.js; resources/css/app.css. AGENTS.md, REQUISITOS.md y las convenciones/diseño de datos reflejan la decisión vigente.

## Limpieza de MySQL

Diagnóstico confirmado antes de escribir: 33 tablas, 32 migraciones, 64 claves foráneas; 30 cotizaciones, 32 detalles y 91 materiales de cotización; 12 pedidos, 13 detalles, 35 materiales utilizados y 3 pagos. No hubo diferencias respecto al diagnóstico aprobado.

Se consolidaron los importes aceptados en pedidos.precio_total_acordado y detalles_pedido.precio_acordado. El pedido 12 conserva Bs 2.347,20, sin pagos y con el mismo saldo. No se regresó al importe base antiguo de Bs 1.072,80.

Retiradas las tablas cambios_pedido y materiales_cambio_pedido. Retiradas cotizaciones.pedido_revision_id/revision_datos, detalles_pedido.detalle_cotizacion_original_id y detalles_cotizacion.revision_numero/vigente. Restaurado el índice único de cotización/posición. Se retiraron las tres cabeceras antiguas 36/37/38 y cuatro detalles históricos, conservando la cabecera 27, su detalle actual 40 y los materiales actuales 104–107. No se renumeraron registros ni se borraron pedidos.

Se añadió pedidos.cotizacion_aceptada_version para comprobar la aceptación de la aprobación actual; no guarda versiones anteriores. La migración original de pedidos ya lo crea para instalaciones nuevas. Retiradas las cinco migraciones del mecanismo anterior (15 y 16 de noviembre) y sus cinco entradas del registro, después de comprobar la estructura final. No se ejecutó migrate:fresh, db:wipe ni un seeder sobre los datos actuales.

Estado final: **31 tablas, 27 migraciones, 55 claves foráneas; cero referencias huérfanas**. Permanecen 27 cotizaciones, 28 detalles, 79 materiales de cotización, 12 pedidos, 13 detalles de pedido, 35 materiales de pedido y los 3 pagos. Todas las migraciones actuales aparecen Ran.

Se compararon cantidades y SHA-256 de filas: intactos usuarios y contraseñas, personal, roles, permisos y excepciones, clientes, categorías, materiales, unidades, existencias, movimientos, ajustes, casos y pagos. materiales_pedido conserva íntegramente referencias actuales, cantidades usadas, responsables y fechas. Precios, pagos y saldos de los doce pedidos coinciden antes/después. Solo se retiraron auditorías exclusivas de los modelos comerciales eliminados; se conserva la auditoría general.

## Respaldos y ensayo

Por instrucción expresa del usuario, no se generaron respaldos adicionales. No se eliminaron los respaldos previos. Se creó una copia MySQL aislada mediante transferencia en memoria, sin escribir otro archivo SQL. La limpieza se ensayó en esa copia antes de aplicarse a la base local. Las escrituras de prueba quedaron aisladas; la comparación del origen confirmó que permaneció intacto durante el ensayo.

## Pruebas ejecutadas

- Suite general: **316 pruebas, 3.186 aserciones, aprobadas**, SQLite en memoria; 408,83 segundos. Se añadieron después cinco comprobaciones específicas adicionales.
- Suite focalizada final CotizacionPedidoTest/FabricacionTest/ConsolidacionMigracionesTest: **27 pruebas, 242 aserciones, aprobadas**. Incluye las nuevas comprobaciones; no sumar estas cifras como pruebas distintas de la suite general.
- Edición directa: IDs estables, aprobación pendiente, acuerdo conservado hasta confirmar, aceptación obligatoria, restricciones del maestro, versiones vencidas, diseño protegido, rebajas inferiores a pagos rechazadas, aumento de cantidad y mueble nuevo sin duplicación, fallo de auditoría con reversión completa, consumo sincronizado e idempotencia.
- Ensayo MySQL: mismo ID de cotización/detalle, conservación de acuerdo y consumo y dos procesos concurrentes confirmando el mismo envío: ambos obtuvieron el mismo pedido y se incrementó una sola vez su versión. Sin escrituras en el origen durante el ensayo.
- Edge/Playwright: **28 grupos de comprobación aprobados**, sobre SQLite temporal: creación/edición, fechas, clientes, retornos, materiales, precio aprobado/aceptado, pago/saldo, reenvío y misma cotización. Anchos 390, 1024, 1280 y 1440, según pantalla; temas claro/oscuro; sin desbordes horizontales ni errores JavaScript. No es una prueba de autenticación de usuarios reales ni evaluación de precisión del recomendador.
- MySQL local final: **16 solicitudes internas de lectura HTTP 200**, ocho rutas como propietario y maestro (listados, ver/editar cotización, PDF, pedido, materiales utilizados y planificación). Datos antes/después idénticos; sesiones de prueba en memoria. No se ejecutaron escrituras funcionales contra los registros locales.
- npm run build aprobado; formato Pint aplicado a los archivos modificados que lo requerían. Rutas verificadas y vistas compiladas limpiadas. La aplicación volvió a estar disponible mediante artisan up.

## Evidencias

- Capturas y resultado de navegador: [carpeta de capturas](capturas/cotizacion-directa-2026-11-16/resultado-browser.json).
- [Formulario actual](capturas/cotizacion-directa-2026-11-16/cotizacion-formulario-1440.png).
- [Pedido con precio confirmado](capturas/cotizacion-directa-2026-11-16/pedido-precio-confirmado.png).
- Evidencia técnica local, sin credenciales: storage/app/private/resultado-edicion-directa-{ensayo,local}.json y storage/app/private/edicion-directa-2026-11-16/resultado-{mysql,lectura-local}.json.

No se modificó el sistema escolar, no se añadieron módulos ni se implementó descuento automático de existencias. No habrá consulta de versiones comerciales anteriores: se conserva únicamente el estado actual y la auditoría general autorizada.

La base temporal creada para este ensayo se retiró después de verificar la instalación local; se conservan sus resultados JSON. No se dejó otra base de datos para el funcionamiento de la aplicación.


## Quitar materiales y recalcular costos — 16/11/2026

El usuario aprueba Quitar material tanto en Registrar materiales utilizados como en Editar muebles y precios. En filas nuevas sin guardar se retira la fila; en materiales vinculados a un pedido se conserva la referencia y se registra cantidad utilizada/presupuestada cero, con subtotal cero. No se elimina el material del catálogo ni se pierde su trazabilidad. Deshacer retiro permite restaurar los valores antes de guardar. Corregir un consumo positivo exige motivo y permiso efectivo de registrar uso, también si se solicita desde la cotización; el servidor comprueba pertenencia al pedido/mueble y rechaza referencias ajenas. Las solicitudes sin permiso no pueden corregir consumos desde otro formulario.

Se recalculan costos y precio sugerido con el porcentaje configurado, sin inventar uno. Se mantienen costos unitarios capturados y desconocidos NULL; no se convierten en costo real cero. Guardar deja pendiente la aprobación, invalida las validaciones correspondientes y mantiene acuerdo, pagos y saldo hasta aprobar y confirmar aceptación. Operaciones transaccionales con auditoría y control de versiones. Sin nuevas tablas/migraciones, descuentos de existencias ni actualizaciones masivas de registros existentes.

Verificación del retiro: 87 pruebas y 755 aserciones aprobadas en SQLite aislado. Edge/Playwright verifica Quitar y Deshacer en ambos formularios, motivo, recálculo, aprobación/aceptación y conservación de pagos, en móvil/escritorio y tema oscuro, sin errores JavaScript. Evidencia: docs/capturas/retiro-materiales-2026-11-16/resultado-browser.json. No se modificó el esquema ni se escribieron datos funcionales en MySQL local.
