# Materiales utilizados y cotización de revisión

Fecha: 15/11/2026.

Al guardar materiales utilizados, un material adicional o una cantidad superior a la cotizada actualiza automáticamente una revisión de la cotización. El detalle del pedido muestra el costo recalculado y los accesos Ver revisión y Revisar costos y precios. Un consumo dentro del presupuesto no se cobra otra vez.

La revisión conserva modificaciones preliminares existentes. Si la propuesta anterior estaba aprobada, se crea una copia editable; el precio acordado sigue vigente hasta aprobar y aplicar la revisión con la aceptación del cliente. Los reenvíos no duplican propuestas ni filas.

Los costos históricos se conservan. Para materiales nuevos se toma el precio positivo del catálogo como estimación; si no se conoce, el costo queda pendiente y no permite aprobar. Se mantienen mano de obra, otros costos totales y ganancia sobre costo configurada. No se asigna un porcentaje predeterminado.

La operación usa bloqueo del pedido y una transacción común para consumo, cotización y auditoría. Respeta denegaciones individuales: quien solo registra uso recibe una advertencia para solicitar la revisión a personal autorizado. No cambia existencias, pagos ni precios aprobados.

## Archivos

- app/Services/FabricacionService.php y RevisionPedidoService.php: integración y cálculo mediante CotizacionService.
- app/Http/Controllers/FabricacionController.php y PedidoController.php: confirmación y acceso a la revisión pendiente.
- resources/views/admin/fabricacion/edit.blade.php y admin/pedidos/show.blade.php: ayuda y revisión visible.
- tests/Feature/RevisionPedidoTest.php y FabricacionTest.php: comportamiento nuevo y preservación de presupuestos originales.
- tests/browser/revision-pedido-cero.cjs: flujo desde guardar el uso, sin apertura manual.

No se crean tablas ni migraciones. Los respaldos de código, respaldo completo de MySQL, verificación de restauración y resultados se conservan en storage/app/private/sincronizar-uso-cotizacion-2026-11-15. Las escrituras de prueba se realizan exclusivamente en SQLite temporal y una copia MySQL.

## Verificaciones

- 95 pruebas funcionales aprobadas, 794 comprobaciones: RevisionPedidoTest, FabricacionTest, FaseCuatroATest, PermisosCotizacionTest y CierrePedidoTest. SQLite en memoria, sin escribir en MySQL local.
- Sintaxis PHP correcta en los cuatro servicios/controladores modificados.
- Respaldo completo restaurado en carpinteria_el_tajibo_ensayo_uso_20261115190325_55f84c: hashes, cantidades y estructuras idénticos en las 33 tablas. SHA-256 del respaldo: 878a9854bfc50f91c1e141ee8d29251b68ff6d108ecf2c064e76f4b9d8d60cb6.
- Sobre esa copia: aumentar el consumo del pedido 12 generó una revisión automáticamente, con costo actualizado Bs 1.882,00; mantuvo el precio acordado Bs 2.232,00 y no duplicó al reenviar. La cotización original y la base original no cambiaron.
- Navegador: pruebas en escritorio/móvil y tema oscuro, incorporación automática, edición de la revisión, aprobación y aplicación, pagos conservados, saldo y reenvío idempotente. Capturas y resultado detallado en el directorio privado de evidencias.

Ejemplo de lectura: con 2 unidades previstas y 5 utilizadas, la revisión incluye al menos 5. El costo aumenta según el costo unitario estimado; el precio sugerido usa la ganancia de esa cotización si está configurada. El nuevo importe no se cobra automáticamente: requiere aprobación y aceptación.
