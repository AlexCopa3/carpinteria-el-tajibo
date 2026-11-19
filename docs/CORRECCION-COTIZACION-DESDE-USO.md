# Cantidades y precio estimado desde materiales utilizados

16/11/2026.

## Problema corregido

La sincronización solo reaccionaba cuando el consumo superaba el presupuesto, retenía cantidades mayores al corregirlas hacia abajo y Ver cotización abría el acuerdo anterior. Por ello las cantidades y precios consultados no coincidían con lo guardado en fabricación.

## Resultado

Guardar un total utilizado explícito lo lleva exactamente a la revisión, tanto si aumenta como si disminuye. También incorpora materiales añadidos y recalcula sus costos mediante CotizacionService. La cantidad por mueble se obtiene dividiendo el total utilizado entre los muebles; ese total no se multiplica dos veces. Se conserva mano de obra, otros costos y costos manuales del borrador. Cero reduce el consumo presupuestado de esa fila a cero; vacío significa pendiente.

Ver cotización desde el pedido abre la revisión actual, con sus cantidades y precio final estimado actualizado. El precio sugerido sigue usando la ganancia configurada; sin porcentaje permanece pendiente de definición manual. No se inventa un porcentaje para precios fijados manualmente. El importe aprobado y aceptado requiere el mismo procedimiento de aprobación/aceptación que Editar pedido. El precio original, los pagos y las existencias se conservan.

## Archivos

- app/Services/RevisionPedidoService.php: detección de cantidades diferentes y sincronización exacta.
- app/Services/CotizacionService.php: conservar referencias históricas inactivas en revisiones, solo cuando mantienen los mismos IDs y están mapeadas por el servidor. Nuevas selecciones inactivas siguen rechazadas.
- resources/views/admin/pedidos/show.blade.php: cotización enlazada y precio estimado visible.
- resources/views/admin/cotizaciones/show.blade.php: precio final estimado de la revisión antes de aprobar, con permisos de márgenes.
- tests/Feature/FabricacionTest.php: preservación del material histórico inactivo y rechazo de altas inactivas.
- tests/Feature/RevisionPedidoTest.php: aumentos, reducciones, cero, pendiente, recálculo, enlace y conservación del acuerdo.
- tests/browser/revision-pedido-cero.cjs: flujo real desde consumo hasta cotización, aprobación y aplicación.
- AGENTS.md y REQUISITOS.md: decisión actualizada.

No se cambian modelos, controladores, rutas, esquema ni migraciones. Las escrituras de prueba se limitan a SQLite temporal y una copia MySQL. Respaldo, originales del código, resultados y capturas: storage/app/private/corregir-cotizacion-desde-uso-2026-11-16.

## Verificaciones de MySQL y navegador

Respaldo completo restaurado en carpinteria_el_tajibo_ensayo_reflejo_20261116111316_d8e889. Se verificaron cantidades, hashes y estructuras de las 33 tablas. SHA-256: b966887f0254dba3e48474ea2e2b48575301c204a7c902a1c79658a83c286098. En la copia, aumentar consumo y luego reducir a 1 actualizó la misma revisión; costo tras reducir Bs 1772.00. El acuerdo anterior y los datos/esquema de la base original se conservaron.

Navegador Microsoft Edge/Playwright: APROBADO, 26 comprobaciones y 22 capturas; base SQLite temporal. Incluye el acceso Ver cotización hacia la revisión vigente, corrección de consumo de 2,5 a 2, adición de otro material, sugerencias 306/66 tras aumentar muebles, aprobación/aplicación, pago/saldo conservados y reenvío sin duplicados. Se revisaron escritorio, móvil y tema oscuro.

Suite funcional final: 97 pruebas aprobadas, 820 comprobaciones, 114,59 segundos. Incluye RevisionPedidoTest, FabricacionTest, FaseCuatroATest, PermisosCotizacionTest y CierrePedidoTest. Se ejecutó exclusivamente con SQLite en memoria. Sintaxis PHP verificada en los dos servicios modificados.
