# Propuesta de fase 4C — materiales utilizados en fabricación

**Estado actualizado: aprobada por el usuario e implementada el 9/11/2026.** El texto siguiente conserva el diseño previo; las expresiones pendientes/no implementadas describen su preparación. Los resultados efectivos están en [la entrega 4C](FASE-4C.md). La fase 4D no está autorizada.

**Fecha:** 9 de noviembre de 2026. **Estado:** diseño para aprobación, sin implementación ni cambios en MySQL.

La autorización para continuar se desarrolla primero como diseño revisable. AGENTS.md dispone: «Antes de migraciones nuevas o cambios grandes, presentar el diseño y esperar aprobación». Esta propuesta adapta la antigua 4C a la decisión posterior de existencias sencillas: no recupera el inventario complejo ni descuenta materiales automáticamente.

## 1. Objetivo

Registrar qué materiales y cantidades se utilizaron realmente en un pedido En fabricación, aprovechando sus filas planificadas. Evitar volver a escribir características de muebles, unidades, costos y listas completas.

4B ya permite planificar, corregir cantidades, consultar faltantes informativos e iniciar fabricación. Esta entrega añadiría el registro operativo de uso; la revisión/validación final y Terminado/Entregado continúan en 4D. CBR/k-NN, casos, pagos y cambios comerciales permanecen fuera del alcance.

## 2. Formulario propuesto

Desde Ver pedido y su planificación se mostraría Registrar materiales utilizados, solo cuando corresponda al estado y permiso. No se crea una opción adicional de menú.

El formulario se organiza por cada detalle de mueble del pedido:

| Material | Unidad | Planificado total | Utilizado total | Diferencia |
|---|---|---:|---|---|
| Material de la planificación | Automática | Solo lectura | Campo de cantidad | Calculada |

Las cantidades son los totales para todos los muebles de ese detalle: no se multiplican nuevamente por cantidad_muebles. El usuario normalmente solo escribe Utilizado. Las observaciones se mantienen como opción secundaria; no se piden costos, porcentaje de ganancia, cliente ni características del mueble.

- **Copiar cantidades planificadas:** rellena únicamente campos utilizados vacíos y requiere que el usuario revise y guarde. No registra nada al abrir la pantalla ni sobrescribe cantidades ya ingresadas; copiar no equivale a validar el trabajo.
- **Añadir material utilizado:** selector de material activo y cantidad; unidad automática. Si no estaba planificado se crea una fila con planificación 0 e inicial NULL, sin alterar retrospectivamente el presupuesto.
- Filas existentes con componente histórico diferente a General lo muestran como ayuda de lectura para distinguir materiales repetidos; no se exige llenar un componente nuevo.
- **Guardar materiales:** registra los valores y deja el pedido En fabricación. No finaliza ni entrega el pedido, ni confirma costos finales.
- **Cancelar:** regresa al listado de Pedidos, sin guardar. Después de guardar se consulta Ver pedido, con su botón de regreso al listado.

Se conserva el diseño AdminLTE, los iconos, el acento menta, botones redondeados, temas claro/oscuro y adaptación móvil. No se crea un asistente de pasos.

## 3. Vacío, cero y correcciones

- Vacío/NULL significa Sin registrar; no se transforma en cero ni se considera dato real confirmado.
- 0 significa No utilizado, introducido expresamente por la persona. No representa una devolución ni un movimiento de existencia.
- Valor positivo significa cantidad utilizada en la unidad del material. Respetar precisión y unidades que no permiten fracciones.
- Puede guardarse parcialmente; el registro definitivo completo se exigirá al diseñar el cierre de 4D, sin inventar datos faltantes ahora.
- Observación por fila opcional. Al corregir cantidades que ya estaban registradas, exigir un único motivo general de corrección y conservar antes/después en auditoría. No exigir un motivo por cada fila en el primer registro.
- Volver un valor registrado a vacío es una corrección con motivo y queda pendiente nuevamente; su valor anterior sigue en auditoría. Cero y pendiente continúan siendo diferentes.
- No se eliminan físicamente filas históricas. Un material agregado al formulario y todavía no guardado sí puede quitarse antes del envío.

## 4. Diseño de datos mínimo

Se propone reutilizar materiales_pedido y MaterialPedido. Esa tabla ya conserva material/unidad, detalle_pedido_id, cantidad_inicial y cantidad_planificada; también protege los enlaces a cotización y catálogo.

Una única migración nueva añadiría:

| Columna propuesta | Tipo | Regla |
|---|---|---|
| cantidad_utilizada | DECIMAL(18,4), NULL | NULL pendiente; cero o positivo si se registró. |
| observaciones_uso | TEXT, NULL | Separada de las observaciones de planificación, para no sobrescribirlas. |
| uso_registrado_por_id | FK a users, NULL, restrictOnDelete | Actor del último registro efectivo; nunca recibido del formulario. |
| uso_registrado_at | TIMESTAMP, NULL | Fecha del último registro efectivo, generada en servidor. |

Al limpiar expresamente una cantidad a NULL, su metadato operativo actual queda pendiente; el responsable/fecha y cantidades anteriores permanecen en auditoría. No se atribuye un uso a la persona que creó originalmente la planificación.

No se crean tablas ni modelos nuevos para lo que ya representa materiales_pedido. Las filas existentes conservarían sus valores y recibirían campos de uso NULL: ningún pedido de ejemplo o anterior se marca como fabricado por una migración.

Se mantiene pedido.version como control de concurrencia y auditorias como historial de valores/actor/motivo. No se añaden contadores manuales, códigos alternativos, campos de costo final ni duplicados del precio acordado.

Si se aprueba y aplica, el código pasaría de 21 a 22 archivos de migración y conservaría las 27 tablas, sujeto a cotejar el esquema real antes del despliegue. No se modifican las migraciones anteriores ni el historial aplicado por iniciativa propia.

## 5. Registro de materiales adicionales

Una fila adicional de uso conserva material_id, unidad_id, nombres capturados, detalle_pedido_id, cantidad_inicial=NULL y cantidad_planificada=0. Solo se registra cuando se informa una cantidad utilizada, no por abrir un selector.

Cuando ese material ya figura para el detalle, se edita una fila existente en lugar de agregar inadvertidamente un duplicado. Si existen varias filas históricas del mismo material por componentes, se respetan sus IDs/componentes; no se fusionan ni se suman dos veces los datos al guardar.

La comparación necesaria/por comprar sigue usando cantidad_planificada; registrar uso no reescribe esa necesidad. Las modificaciones explícitas de planificación continúan en su pantalla actual y no alteran cantidades utilizadas.

## 6. Permisos y límites de estado

- Consulta bajo los permisos de Pedidos y Planificación existentes.
- Registro/corrección para propietario o maestro autorizado: pedidos.consultar, pedidos.modificar, planificacion.consultar y planificacion.modificar, cuenta activa y pedido En fabricación.
- Utilizar una capacidad específica en PedidoPolicy para este flujo, sin concederla solo por ocultar/mostrar un botón y sin cambiar asignaciones guardadas de Spatie.
- No crear permisos nuevos para duplicar esos accesos en esta propuesta. La validación definitiva de materiales mediante materiales.validar se mantiene para el diseño de cierre 4D; guardar aquí no se presenta como aprobación final.
- Pedidos Aceptado: materiales todavía pendientes; se requiere iniciar fabricación para registrar uso. Terminado/Entregado no se habilitan ni se aceptan mediante solicitudes manipuladas.
- Maestros no modifican ganancia, aprobaciones, precios, cliente acordado, responsable administrativo ni entrega prevista desde este formulario.

## 7. Reglas del servidor

Form Request con campos permitidos explícitos; rechazar IDs duplicados/ajenos, detalle de otro pedido, unidad alterada, negativos, exceso de precisión y campos administrativos. IDs de actor, fechas, diferencias y datos históricos se determinan en servidor.

Servicio transaccional con bloqueo del pedido, filas/materiales necesarios y verificación de versión. Reenviar una versión anterior no inserta otra fila ni una segunda auditoría parcial. La interfaz evita doble envío, pero la protección principal es del servidor. Un envío sin cambios no genera una modificación ficticia.

Material inactivo ya presente se conserva para registrar su uso histórico; no se permite añadir un material inactivo nuevo. Los cambios de unidad con historial continúan bloqueados por las reglas actuales de catálogo.

Modificar cantidades utilizadas no cambia existencias_materiales, movimientos_materiales, la cotización aprobada, precios acordados ni su PDF. No se presenta una referencia de costo desconocida como costo real cero. Los costos finales no se capturan ni validan en 4C; su definición permanece pendiente de 4D.

## 8. Archivos previstos, sin crear aún

| Área | Propuesta |
|---|---|
| Migración | Un archivo add_uso_to_materiales_pedido_table, sin editar las 21 existentes. |
| Modelo | MaterialPedido: casts y relación del responsable de uso. Sin modelo duplicado. |
| Controlador | FabricacionController con edit/update explícitos, conforme al estilo escolar. |
| Validación | Requests/Pedidos/GuardarMaterialesUtilizadosRequest para arrays y control de campos. |
| Servicio | FabricacionService para consistencia, bloqueo, versión y auditoría. |
| Política | Capacidad específica en PedidoPolicy, reutilizando Spatie/Acceso existentes. |
| Rutas | GET /admin/pedidos/{pedido}/fabricacion/edit y PUT /admin/pedidos/{pedido}/fabricacion; nombres admin.fabricacion.edit/update. No se cambian rutas existentes. |
| Vistas | admin/fabricacion/edit.blade.php; consulta incorporada a Ver pedido y accesos autorizados desde planificación. Parcial solo si resulta necesario. |
| Recursos | Extensión limitada de pedidos.js para añadir filas y copiar solo vacíos; estilos compartidos existentes. |
| Pruebas | Feature y escenario de navegador en bases aisladas; controles de MySQL en copia aislada antes de aplicar la migración. |

No se crean repositorios genéricos, eventos/capas innecesarias, otro menú ni una pantalla independiente de inventario. La cantidad y necesidad de parciales se ajustará al código real durante la implementación aprobada.

## 9. Secuencia de implementación y despliegue tras aprobación

1. Confirmar alcance y columnas de este documento.
2. Implementar migración, servicio, validación, permisos y formulario en código; pruebas de SQLite aislado sin tocar MySQL operativo.
3. Cotejar conexión, tablas/columnas, migraciones y cantidades actuales de carpinteria_el_tajibo. Ante diferencias con el diseño, informar antes de seguir.
4. Preparar un respaldo completo nuevo y verificar su restauración en una base aislada. Comparar estructura y registros.
5. Ensayar la migración y operaciones en esa copia, verificando conservación de datos anteriores y separación entre planificación/uso. Sin migrate:fresh, db:wipe ni eliminación de tablas/columnas.
6. Aplicar solo la migración aprobada a MySQL cuando el ensayo y la autorización lo permitan; comprobar campos NULL para registros anteriores y conservación de usuarios, roles, permisos, cotizaciones, pedidos y saldos.
7. Pruebas completas, navegador y capturas; informe de resultados antes de solicitar la fase siguiente.

Esta preparación no genera ni restaura respaldos de MySQL ahora, no aplica migraciones ni implementa el formulario.

## 10. Comprobaciones requeridas, aún no ejecutadas para 4C

- Permisos por rol, estado y acceso directo; rechazo de campos privilegiados y datos de otro pedido.
- NULL, cero, cantidades positivas, unidades enteras/fraccionables, límites y redondeo exacto.
- Totales por detalle sin segunda multiplicación, varios muebles y materiales/componentes históricos.
- Primer registro, guardado parcial, corrección con motivo, reenvío, conflicto de versión y rollback ante fallo.
- Material adicional no planificado, preservación de planificación inicial/actual, bloqueo de duplicados nuevos y conservación de inactivos históricos.
- Ningún cambio en saldos, movimientos de existencias, precios aprobados/acordados ni PDF.
- Registros anteriores con NULL, sin copiar automáticamente planificación como uso real.
- Comportamiento del botón Copiar solo sobre vacíos, errores accesibles, teclado y claro/oscuro en escritorio/móvil.
- Regresión de las fases anteriores y de migrate --seed; los lotes de ejemplo conservan su origen interno y no se convierten en casos reales.

Las 162 pruebas PHP y 75 comprobaciones de navegador de la limpieza anterior no acreditan 4C. No se ejecutan pruebas nuevas de implementación en una entrega exclusivamente documental.

## 11. Decisiones para aprobar

1. Alcance: registro sencillo de uso durante En fabricación, sin cerrar/entregar ni descontar existencias.
2. Reutilizar materiales_pedido, con cuatro columnas NULL y una migración aditiva; sin tabla ni modelo adicional.
3. Entrada habitual limitada a cantidad utilizada; copiado explícito de planificación, observación opcional y motivo general solo al corregir cantidades ya registradas.
4. Propietario y maestros autorizados registran uso; aprobación final de materiales/precio y preparación de casos definitivos se diseñan en 4D/5.

El contenido es una propuesta revisable, no una modificación silenciosa de REQUISITOS.md ni una autorización para avanzar de fase.
