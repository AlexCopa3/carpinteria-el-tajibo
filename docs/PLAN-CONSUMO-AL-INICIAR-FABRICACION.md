# Resta de materiales al iniciar fabricación

Fecha: 13/11/2026. El usuario acepta incorporar la resta automática y precisa que debe ocurrir al comenzar la fabricación. Se sustituye para ese evento la prohibición anterior de restar existencias. Estado: diseño preparado; aplicación y ampliaciones de esquema pendientes de revisión. No hay cambios activos en los servicios ni MySQL.

## Funcionamiento propuesto

1. Cotizar, aprobar, aceptar el pedido o editar su planificación no resta materiales.
2. Al pulsar Iniciar fabricación, el servidor suma las cantidades planificadas de todos los muebles, agrupadas por material y unidad. Son totales ya calculados: no vuelve a multiplicarlos por la cantidad de muebles.
3. Resta esas cantidades de las existencias, registra responsable/fecha/pedido y cambia el estado a En fabricación dentro de la misma transacción. Si falta material, informa qué falta y mantiene todo sin cambios; se conserva la prohibición de saldos negativos.
4. Reenviar la solicitud o pulsar dos veces no vuelve a restar. Se conservan la clave de operación y la versión del pedido.
5. Registrar el uso durante fabricación no vuelve a restar todo. Al validar el uso real se compara con lo ya retirado: si se utilizaron más unidades, sale solo la diferencia; si se utilizaron menos, se devuelve solo la diferencia. Las correcciones justificadas vuelven a validarse y dejan movimientos compensatorios; no se borran ni editan salidas previas.
6. Terminar o entregar no duplica el consumo. Si se valida el uso al terminar, aplica la misma conciliación una sola vez. No altera cotización, costos de referencia, precios acordados, pagos ni casos previos.

Ejemplo: hay 10 tableros y se planifican 3. Al iniciar quedan 7. Si después se confirma que se usaron 4, sale uno adicional y quedan 6; si se confirman 2, se devuelve uno y quedan 8. Cambiar la planificación por sí solo no aplica otra salida: el uso confirmado resuelve la diferencia.

## Pedidos existentes

Diagnóstico MySQL de solo lectura: carpinteria_el_tajibo, 31 tablas/27 migraciones, dos usuarios, 11 pedidos (4 aceptados y 7 en fabricación), 27 existencias, 29 movimientos, 31 líneas de materiales y ninguna cantidad utilizada registrada. Las dos columnas propuestas no existen. Evidencia privada: storage/app/private/preparacion-consumo-fabricacion-2026-11-13/diagnostico.json.

Se aplica a los inicios que ocurran después de activar la función, incluidos los cuatro pedidos aceptados si se inician entonces. Los siete ya en fabricación y los pedidos terminados/entregados quedan sin descuentos retroactivos. Sus movimientos y registros conservan su significado anterior. No se marca un consumo anterior que no ocurrió en el sistema.

## Ampliación mínima de datos propuesta

No se crean tablas nuevas ni se modifican las 27 migraciones aplicadas.

| Tabla | Campo nuevo | Finalidad |
|---|---|---|
| pedidos | existencias_descontadas_at, TIMESTAMP nullable | Identifica el inicio que sí retiró materiales mediante este flujo, incluso si la planificación contiene cantidades cero. En registros anteriores queda NULL. |
| movimientos_materiales | pedido_id, BIGINT UNSIGNED nullable, FK restrictiva a pedidos.id | Vincula cada salida/devolución con su pedido. Los 29 movimientos anteriores conservan NULL. Se añade índice pedido_id/material_id/id. |

Dos migraciones aditivas, una por tabla: 2026_11_13_160000_add_existencias_descontadas_at_to_pedidos_table y 2026_11_13_160001_add_pedido_id_to_movimientos_materiales_table. Se conservan como borradores privados en el directorio de preparación, fuera de database/migrations; no se ejecutan en una migración normal del proyecto.

Se proponen los tipos salida_fabricacion y devolucion_fabricacion en la columna tipo existente, compatible por longitud. cantidad permanece positiva; los saldos anterior/resultante muestran la resta o devolución. No se suman movimientos manuales antiguos para inferir consumos de pedidos. El saldo retirado de cada pedido se deriva de sus salidas menos devoluciones, sin duplicar una tabla de consumos.

Estado esperado tras aplicar: 31 tablas, 29 migraciones y 56 FK, conservando íntegramente usuarios, contraseñas, roles, permisos y registros anteriores. No se añaden ni modifican asignaciones de permisos por esta función.

## Archivos y estructuras que se conservan

- PedidoService::cambiarEstado ya bloquea el pedido, valida versión/clave y crea el evento de inicio. Integra la salida antes de confirmar el estado, sin trasladar la lógica al controlador.
- Nuevo ConsumoMaterialService sencillo: agrupar cantidades, bloquear materiales/saldos por ID ordenado, comprobar unidades y disponibilidad, registrar salidas/devoluciones con BigDecimal y la misma transacción del pedido. Las operaciones deben exigir los permisos de iniciar o validar que correspondan y no exponer un endpoint administrativo sin autorización.
- CierrePedidoService::validarMateriales y el cierre con validación concilian el uso confirmado solo para pedidos con retiro inicial registrado. Revalidar sin cambios no crea movimientos.
- FabricacionService conserva captura, motivo de corrección, versión e invalidación de revisión; guardar cantidades no vuelve a descontar lo planificado.
- Pedido, MovimientoMaterial y sus relaciones se amplían de forma explícita. Cada movimiento conserva clave única, huella, responsable, material/unidad y saldos. Los movimientos permanecen inmutables.
- PedidoController/FabricacionController y los textos Blade dejan de afirmar que iniciar nunca modifica existencias; se indica cuándo se resta y cuándo se concilia. Se mantiene AdminLTE, rutas actuales, formularios y validación del servidor.
- PlanificacionMaterialService::disponibilidad y su vista distinguen lo ya retirado para el pedido del saldo libre del taller. El faltante es máximo(necesario − retirado − saldo libre, 0), para no pedir comprar de nuevo lo que ya salió para ese trabajo.
- Existencias muestra Salida por fabricación o Devolución de fabricación, signo correcto y referencia al pedido. Un enlace al pedido solo se ofrece con permiso de consulta. No expone clientes o precios a quien solo consulta materiales.
- Se reutilizan los permisos de pedidos/planificación/materiales vigentes: iniciar o validar no habilita corregir manualmente todas las existencias. Se mantiene la prioridad de las excepciones individuales.

## Aplicación controlada después de revisar

1. Revalidar el diagnóstico y estructuras; investigar cualquier diferencia respecto a este corte antes de aplicar.
2. Respaldar por completo la base actual y verificar una restauración aislada mediante comparación de datos/estructura. Aislar también la caché de Spatie desde el arranque para que el ensayo no comparta IDs con el origen.
3. Implementar y probar en bases aisladas: cantidades fraccionarias/enteras, mismo material en varios muebles, insuficiencia sin cambios parciales, doble envío, dos pedidos concurrentes sobre un saldo, corrección con salida/devolución, revalidación y cierre sin duplicados, cambio de unidad, rollback si falla auditoría, permisos y protección de pedidos anteriores.
4. Probar las dos migraciones y el flujo completo en el clon MySQL restaurado. Conservar todos los datos anteriores; las dos columnas nuevas deben quedar NULL en las filas anteriores y no generar consumos.
5. Aplicar únicamente las dos ampliaciones aprobadas y activar el código verificado, bajo una ventana controlada para impedir inicios durante el despliegue. No ejecutar seeders generales, migrate:fresh, db:wipe ni borrar tablas/columnas existentes.
6. Verificar MySQL, suite funcional y navegador con escrituras aisladas. Documentar respaldo, resultados y estado final. Sin pruebas de consumo sobre pedidos reales existentes.

La preparación de estructura en SQLite, si se documenta, acredita únicamente compatibilidad del borrador y restricciones; no demuestra aún el descuento funcional ni un ensayo MySQL. El sistema sigue funcionando sin restas automáticas hasta aprobar y completar esta aplicación.

## Comprobación previa de estructura

Ocho comprobaciones aprobadas en SQLite :memory:, ejecutando las 27 migraciones activas y después únicamente los dos borradores privados. Se crearon filas de prueba anteriores al cambio y se confirmó: marcador de pedido NULL, referencia de movimiento NULL, todas las columnas/datos anteriores idénticos, existencia sin restar, índice compuesto, FK al pedido, rechazo de pedido inexistente y bloqueo del borrado de un pedido con movimientos. No se registraron los borradores en el historial activo ni se conectó a MySQL en esa prueba. No es todavía prueba funcional del consumo ni ensayo MySQL. Evidencia privada: preparacion-consumo-fabricacion-2026-11-13/resultado-estructura.json.
