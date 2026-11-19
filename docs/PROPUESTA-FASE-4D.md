# Propuesta de fase 4D — Revisión final y entrega

**Estado actualizado: diseño aprobado por el usuario e implementado el 9/11/2026.** El texto siguiente conserva la propuesta previa. Los resultados efectivos se encuentran en [la entrega 4D](FASE-4D.md). La fase 5 sigue pendiente de autorización.

Fecha: 9 de noviembre de 2026. El usuario solicita pasar a la siguiente fase después de 4C. Este documento concreta el diseño para su revisión previa, conforme a REQUISITOS.md, apartado de fase 4. No se ha implementado código, ejecutado migraciones ni modificado MySQL durante esta preparación.

## 1. Estructura encontrada y reutilización

- PedidoService permite Aceptado → En fabricación; usa transacción, bloqueo de pedido, versión y EstadoPedido con UUID/huella para impedir operaciones repetidas.
- pedidos conserva estado, version y precio_total_acordado. detalles_pedido conserva precio_acordado por mueble/grupo, vinculado al detalle de la cotización aprobada.
- estados_pedido ya registra estado anterior/nuevo, usuario, ocurrido_at, motivo y versión; hay claves únicas por operación y por pedido/versión. No hace falta otra tabla de estados ni duplicar las fechas de término/entrega.
- materiales_pedido conserva cantidades iniciales, planificadas y utilizadas; 4C distingue NULL pendiente y cero expreso no utilizado. FabricacionService y PlanificacionMaterialService administran cambios sin descontar existencias.
- materiales.validar ya está definido para propietario/maestros. Las políticas de modificación actuales bloquean Terminado y Entregado.
- Hay 22 archivos de migración. La entrega 4C verificó 27 tablas MySQL; este diseño no constituye un nuevo diagnóstico de esa base.

## 2. Proceso sencillo propuesto

### Revisar materiales

Desde Ver pedido, durante En fabricación, mostrar las cantidades registradas y el botón **Validar materiales** a propietario/maestro con permiso. No volver a pedir características, cliente o precios.

El servidor exige que cada detalle tenga filas de materiales y que ninguna cantidad utilizada esté pendiente. Cero introducido expresamente es válido y se distingue de un campo vacío. No se copian cantidades automáticamente para completar el cierre. La confirmación SweetAlert2 expresa que se revisaron los materiales; no basta visitar la pantalla.

Guardar el responsable y fecha de validación. Si después cambia efectivamente una cantidad, material, planificación u observación operativa del uso, invalidar esa revisión y pedir una nueva. Un guardado sin cambios no debe invalidarla. Cambiar una nota general o la fecha prevista del pedido no invalida por sí solo los materiales.

### Terminar fabricación

El propietario dispone de **Terminar fabricación**, con resumen de materiales y precio acordado. Si los materiales están completos pero sin validar, puede **validar materiales y terminar** en una sola confirmación, siempre que tenga ambos permisos. El maestro puede validar materiales, pero no confirmar el precio ni terminar el pedido mediante este flujo.

Para terminar: estado En fabricación, revisión de materiales vigente y confirmación expresa del propietario del precio previamente acordado. El servidor comprueba la suma de precios de los detalles y su correspondencia con el acuerdo; no acepta precios nuevos desde el navegador.

Registrar la validación comercial y el evento En fabricación → Terminado con responsable y fecha. Proteger cantidades, planificación, acuerdo y datos operativos cerrados. No habrá reapertura o corrección posterior improvisada; necesitará procedimiento específico si se solicita.

### Registrar entrega

Solo después de Terminado, el propietario podrá **Registrar entrega**, con confirmación y observación opcional. Se registra Terminado → Entregado y su fecha/hora real del servidor. Sin un formulario adicional de cliente, cobros o documentos. No permitir saltos, retrocesos ni una segunda entrega.

La propuesta reserva la entrega al propietario como decisión para revisar, dado que los requisitos no asignan expresamente esta operación. No se impondrá pago completo: el módulo de pagos aún no existe.

## 3. Precio, costos y casos: límites explícitos

- Confirmar precio final significa ratificar el precio acordado, no cobrar automáticamente otra cantidad por utilizar más material. No cambiar la cotización aprobada, sus costos ni su PDF.
- No introducir otro porcentaje de ganancia ni volver a pedir el presupuesto. Un cambio comercial posterior requiere su procedimiento, fuera de este alcance.
- No calcular costos reales de fabricación multiplicando consumos por el precio actual del catálogo y presentarlos como gasto comprobado. Esta propuesta no incorpora captura de costos reales; conserva estimaciones aprobadas separadas de cantidades utilizadas.
- Los datos finales de materiales y la ratificación del precio quedan disponibles para la fase 5. Allí se definirá la información económica necesaria para retener casos sin confundir estimaciones con costos reales.
- La decisión vigente de REQUISITOS.md pospone la creación de casos a fase 5. Terminar en 4D no creará casos todavía. La futura incorporación deberá considerar los terminados validados antes de esa integración y ser idempotente, sin esperar a su entrega.
- Los pedidos ficticios existentes conservan su procedencia interna; ninguna validación cambia su origen ni los convierte en antecedentes reales. No se generarán los 35 casos ni CBR/k-NN.
- Sin descuentos, reservas, devoluciones ni movimientos automáticos de existencias; sin pagos ni cálculo de ganancias administrativas nuevas.

## 4. Esquema mínimo propuesto

Una migración aditiva sobre pedidos, sin editar las 22 anteriores:

| Columna | Tipo y regla |
|---|---|
| materiales_validados_por_id | FK nullable a users.id, ON DELETE RESTRICT |
| materiales_validados_at | TIMESTAMP nullable |
| precio_confirmado_por_id | FK nullable a users.id, ON DELETE RESTRICT |
| precio_confirmado_at | TIMESTAMP nullable |

Todos los valores anteriores empiezan en NULL; no se inventa una validación para pedidos existentes. El actor/fecha no vienen del formulario. La revisión de materiales se borra operativamente al modificarse los datos revisados, preservando en auditorías quién validó antes y qué cambió.

No se crean tablas, modelos ni fechas de estado duplicadas. El término y la entrega se consultan en estados_pedido; la versión y los bloqueos del pedido coordinan cambios con los servicios de 4B/4C. Si se aprueba técnicamente este diseño, serían 23 migraciones y las mismas 27 tablas, sujeto a verificar el MySQL real.

## 5. Autorización propuesta

| Operación | Propietario | Maestro |
|---|---|---|
| Consultar revisión y estados | Con consultas de pedidos/planificación | Igual |
| Validar materiales | Con consultas, modificación de pedido/planificación y materiales.validar | Igual |
| Confirmar precio y terminar | Solo propietario, consultas y pedidos.modificar + cotizaciones.validar; materiales.validar si valida en esa misma operación | Bloqueado |
| Registrar entrega | Solo propietario con pedidos.consultar y pedidos.modificar | Bloqueado |

Todas requieren cuenta activa y el estado permitido. Se reutilizan permisos existentes sin cambiar asignaciones silenciosamente. La política, Form Request y servicio revalidan acceso, versión, datos completos y estado. No aceptar estados arbitrarios, responsables, fechas, precio, permisos ni validaciones desde campos ocultos.

## 6. Archivos previstos y convenciones

- Mantener FabricacionController edit/update y añadir métodos explícitos de validar materiales y terminar; PedidoController con método explícito de entrega. Servicios mantienen las operaciones críticas y los controladores su estilo familiar.
- Extender FabricacionService/PedidoService o utilizar un servicio específico de cierre solo si el tamaño lo justifica; no introducir repositorios, eventos genéricos o capas innecesarias.
- Form Requests para las acciones de validación/cierre/entrega. PedidoPolicy con capacidades separadas; MaterialPedido y las tablas actuales se conservan.
- Ampliar Ver pedido y el resumen de materiales para mostrar validación y acciones según estado/permiso. Botones coherentes, iconos, SweetAlert2 local, temas claro/oscuro y móvil. Sin una opción nueva de menú.
- Rutas POST explícitas propuestas bajo /admin/pedidos/{pedido}: /materiales/validar, /terminar y /entregar, con nombres admin.fabricacion.validar, admin.fabricacion.terminar y admin.pedidos.entregar. Conservar las 73 entradas actuales.
- Extender la consulta de estados y filtros para los nuevos estados reales. No alterar la aprobación de cotizaciones, cálculos ni PDF comercial.
- Auditoría de validación, invalidación y transición con valores anteriores/nuevos. Cada transición conserva UUID/huella y una sola versión/evento. Reenvío idéntico no duplica; misma clave con datos distintos se rechaza.

## 7. Comprobaciones y despliegue tras revisión

1. Implementar el alcance revisado y probar en SQLite aislado, preservando las pruebas previas.
2. Comprobar conexión, esquema, migraciones y conteos actuales; preparar un nuevo respaldo completo, restaurarlo y verificarlo en un clon aislado.
3. Ensayar allí la migración, preservación de datos, cuatro columnas NULL y FK, y las operaciones del cierre.
4. Probar dos cierres/entregas simultáneos y cambio de materiales frente a validación/cierre; no permitir un cierre con datos distintos a los revisados ni duplicar eventos.
5. Aplicar únicamente la migración aprobada en MySQL local si el respaldo/ensayo y la autorización lo permiten. Ante diferencias, detenerse e informar. Sin migrate:fresh, db:wipe ni eliminación de tablas/columnas.
6. Navegador con ambos roles y estados, SweetAlert2, errores, reenvíos, móvil y oscuro; verificar inmutabilidad, precios/PDF/existencias intactos y rechazo de saltos/retrocesos.
7. Informe de entrega, pruebas y capturas; no iniciar fase 5 sin aprobación.

## 8. Decisiones propuestas para revisar

1. Validación de materiales por propietario/maestro y cierre por propietario, combinables en una acción del propietario.
2. Precio acordado ratificado sin recálculo ni formulario nuevo de costos reales.
3. Entrega exclusiva del propietario, con fecha automática y nota opcional, sin vincularla a pagos todavía.
4. Cuatro metadatos nullable en pedidos y reutilización de estados_pedido; sin tablas nuevas ni casos en esta fase.

La petición de pasar a la siguiente fase autoriza esta preparación. REQUISITOS.md exige revisar el diseño detallado antes de nuevas migraciones o cambios grandes; por ello estos puntos se presentan antes de implementarlos.
