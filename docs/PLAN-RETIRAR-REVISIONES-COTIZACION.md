# Retirada del mecanismo de revisiones de cotización

16/11/2026.

## Decisión funcional confirmada

El usuario solicita eliminar también el mecanismo interno de cotización original, versiones históricas y cambios comerciales, y guardar directamente los cambios sobre la misma cotización. No se trata solamente de ocultar las pantallas. La limpieza de base de datos y refactorización concreta descritas a continuación se presentan antes de ejecutarlas, conforme a AGENTS.md.

## Diagnóstico de solo lectura

Base carpinteria_el_tajibo: 30 cotizaciones, 32 detalles de cotización, 91 materiales de cotización, 12 pedidos, 13 detalles de pedido, 35 materiales de pedido y 3 pagos. Historial comercial: 4 cambios y 14 referencias de materiales; 1 detalle histórico, 4 cabeceras vinculadas al flujo de revisiones y 1 detalle con referencia original.

El pedido 12 conserva importe base Bs 1.072,80, pero su importe aceptado actual es Bs 2.347,20, sin pagos. El precio y saldo dependen hoy del historial: retirar tablas sin consolidarlo cambiaría el importe incorrectamente. La lectura se hizo en una transacción READ ONLY revertida. No se modificó código funcional ni MySQL.

## Funcionamiento propuesto

1. Una cabecera y sus líneas actuales por cotización. Edición directa de cantidades, materiales, costos y muebles, sin copiar líneas para versiones históricas.
2. Conservar permisos efectivos, aprobación del precio y confirmación de aceptación antes de cambiar el importe del pedido. Mantener versiones técnicas de concurrencia y claves que evitan envíos duplicados; no son historial de cotización.
3. El pedido guarda directamente el importe aceptado actual y los precios de sus detalles. Los pagos y saldos se calculan desde estos valores, sin consultar cambios_pedido. Una marca simple de la versión aceptada de la cotización permite impedir el cierre de cambios todavía no confirmados, sin almacenar versiones anteriores.
4. Registrar materiales utilizados sigue actualizando los materiales/cantidades actuales de la misma cotización. Desconocido permanece pendiente; no inventar costos cero, porcentajes ni aprobaciones.
5. Conservar seguimiento de estados, responsables y auditoría general habitual del sistema; retirar la historia comercial específica de cotización original/revisiones, sus pantallas y comprobantes. No descontar existencias ni crear módulos.

## Limpieza prevista

- Consolidar precios aceptados actuales de pedidos y detalles, referencias de materiales/costos y la cotización que pertenece a cada pedido. El pedido 12 debe conservar Bs 2.347,20 y saldo Bs 2.347,20. Conservar un borrador pendiente como pendiente, sin aprobarlo ni aceptar automáticamente otra cifra.
- Retirar cambios_pedido y materiales_cambio_pedido, después de preservar sus datos en el respaldo y materializar los valores vigentes necesarios.
- Retirar cotizaciones.pedido_revision_id y revision_datos; detalles_pedido.detalle_cotizacion_original_id; detalles_cotizacion.revision_numero y vigente.
- Consolidar cabeceras antiguas de revisión y sus relaciones sobre el ID de la cotización del pedido, sin renumerar pedidos, usuarios o registros independientes. Retirar líneas históricas únicamente tras comprobar todas sus dependencias.
- Preparar una marca simple de aceptación actual en pedidos, distinta del versionado histórico que se retira.
- Retirar o adaptar las cinco migraciones del mecanismo y conciliar el historial para que una instalación nueva y la base existente tengan el mismo esquema final. No borrar archivos aplicados de forma aislada.

## Código afectado

Retirar RevisionPedidoService, RevisionPedidoController, CambioPedidoService, CambioPedidoController, GuardarCambioPedidoRequest, CambioPedido y MaterialCambioPedido, reemplazando únicamente la actualización corriente que siga siendo necesaria dentro del flujo de Cotizaciones/Pedidos. Retirar admin/pedidos/revision/_confirmar.blade.php, admin/pedidos/cambios/create.blade.php, admin/pedidos/cambios/_resumen.blade.php y pdf/cambio-pedido.blade.php.

Adaptar Cotizacion, DetalleCotizacion, MaterialCotizacion, Pedido, DetallePedido, CotizacionService, FabricacionService, PedidoService, CierrePedidoService, RetencionCasoService, PagoService/reportes cuando corresponda; controladores, políticas, rutas y Blade que actualmente consultan esos servicios o campos. Retirar selectores JavaScript/CSS exclusivos del mecanismo abandonado y actualizar pruebas/documentación para no dejar código sin uso. Mantener la estructura basada en el sistema escolar.

## Aplicación controlada

Por instrucción expresa posterior del usuario, no generar respaldos adicionales en esta operación sobre datos iniciales. Probar conversión y funcionamiento en una copia aislada, incluyendo pagos/saldos, registros pendientes, costos, casos, permisos y concurrencia. Comparar usuarios, contraseñas, roles, permisos, clientes, existencias, movimientos, pagos y casos; no modificarlos por la limpieza. Mantener la base original intacta hasta aprobar el ensayo. Aplicar únicamente en carpinteria_el_tajibo y verificar conservación de cada importe aceptado y saldo. Sin migrate:fresh, db:wipe ni cambios en el sistema escolar.
