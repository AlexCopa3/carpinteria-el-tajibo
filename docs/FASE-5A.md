# Fase 5A — Casos históricos y materiales

Entrega del 9 de noviembre de 2026, tras la aprobación del esquema de dos tablas y la confirmación de conservar materiales, cantidades, costos y precio por caso. Se mantienen las convenciones escolares sin modificar el sistema escolar original. **5B (recuperación k-NN) y 5C (aplicación a cotización) no están implementadas ni activadas.**

## 1. Funcionalidad entregada

Casos históricos está disponible en el menú para usuarios con casos.consultar. Su listado permite buscar por ID/tipo/material y filtrar por tipo de mueble, origen y revisión. La ficha Ver presenta:

- Características del mueble: tipo, material principal, dimensiones cm, acabado, puertas, cajones y divisiones.
- Cantidad de muebles del grupo y fecha de referencia.
- Cada material, su unidad y cantidad total utilizada o ilustrativa, costo unitario estimado y subtotal cuando se conocen.
- Materiales, mano de obra, otros costos y total estimados, separados del precio histórico/acordado del grupo.
- Origen, fuente de características/costos, revisión y responsables/fechas.

No se duplican datos personales del cliente. No hay captura manual o importación Excel de antecedentes externos. Las cantidades/importes pertenecen al grupo completo y no se multiplican otra vez. Los botones conservan iconos/colores, SweetAlert2 y disposición móvil; tablas con desplazamiento interno, claro/oscuro y regreso al listado.

## 2. Casos al terminar un pedido

CierrePedidoService invoca RetencionCasoService dentro de la misma transacción al pasar En fabricación → Terminado, después de guardar el evento y las validaciones. Se crea **un caso por detalle de mueble**, con detalle_pedido_id único. El caso conserva el tamaño del lote; materiales conservan sus totales originales. Reenvíos, conciliación y entrega posterior no duplican casos ni sus materiales.

Características proceden de la cotización acordada; cantidades proceden del uso validado. No se presentan las medidas acordadas como nuevas mediciones físicas al terminar. Los costos unitarios históricos se obtienen exclusivamente de la línea cotizada vinculada al material/unidad originales; no se reemplazan por precios actuales del catálogo.

El costo de materiales de referencia se calcula con cantidades utilizadas × costos estimados capturados, redondeando cada subtotal a dos decimales. Mano de obra y otros costos conservan sus importes aplicados de cotización. Se conserva por separado costo_presupuestado_total para distinguir el presupuesto original del costo de referencia recalculado con cantidades registradas. **Ambos son estimaciones, no gastos reales comprobados.** El precio del caso copia el precio acordado del detalle y no se recalcula.

Un material adicional sin estimación histórica queda con costo/subtotal NULL, aunque hoy tenga un costo en el catálogo. Si una cantidad positiva carece de costo, el total permanece pendiente. Las filas de cantidad cero se conservan como no utilizadas; un costo desconocido no se presenta como costo unitario cero. Si no hay materiales positivos, el principal no figura utilizado o faltan datos, el caso se retiene con motivo de inelegibilidad y no se valida automáticamente como referencia completa. No se altera el cierre ni se rellenan datos para resolverlo.

Los casos operativos completos de pedidos reales heredan responsables/fechas de las validaciones de 4D; esa revisión corresponde a materiales y precio, no certifica gastos reales. Se identifican los pedidos simulados mediante IDs de pedido/cotización de todos los lotes anteriores registrados en auditoría; sus casos conservan origen simulado y revisión pendiente sin inventar una revisión humana. Revisar un caso simulado nunca cambia su origen.

Los datos de la instantánea y líneas históricas no se reescriben mediante el módulo. No hay edición comercial de un pedido cerrado ni un formulario que modifique retrospectivamente un caso. Una corrección de esos datos requiere un procedimiento específico; un caso incompleto puede conservarse/descartarse como referencia.

## 3. Revisión y permisos

| Capacidad | Propietario | Maestro |
|---|---|---|
| Consultar casos | casos.consultar | casos.consultar |
| Consultar desglose de costos | Además costos.consultar | Además costos.consultar |
| Validar materiales del caso | casos.validar_materiales | casos.validar_materiales |
| Validar precio/referencia económica | Exclusivo, con casos.validar_precios y costos.consultar | Bloqueado incluso con permiso incompatible |
| Descartar referencia con motivo | Mismos controles de revisión económica | Bloqueado |

Todas las operaciones requieren cuenta activa y consulta del caso. Controlador/Form Request/servicio verifican autorización y campos permitidos. Actor, fecha, origen, precio y cantidades no se aceptan como datos de revisión del navegador. Una versión obsoleta se rechaza; repetir una validación ya hecha con la versión vigente no genera una modificación ficticia. Fallos de auditoría revierten cambios.

El caso queda Revisado cuando tiene ambas revisiones; con una sola permanece Pendiente. Descartado conserva cabecera/materiales y motivo en auditoría; no se elimina ni vuelve a validarse mediante la misma pantalla. Los datos con motivo de inelegibilidad no se aprueban administrativamente como completos.

CasosPermisosSeeder agregó únicamente **dos permisos nuevos** y **tres asignaciones**: materiales a propietario/maestro y precio a propietario. Conservó permisos/asignaciones anteriores y registra la incorporación una sola vez; repetirlo no restaura capacidades retiradas después por el propietario. Los nuevos permisos aparecen en las casillas de Usuarios → Ver permisos.

## 4. Los 35 escenarios de demostración

Carga aplicada en la base actual según autorización: **35 casos y 172 líneas de materiales**, todos simulados y pendientes, sin responsables/fechas de revisión. Distribución: 6 escritorios, 6 mesas, 6 sillas, 6 armarios, 6 estantes y 5 puertas; contextos domésticos de La Paz/El Alto, cm y Bs. Precios/cantidades ilustrativos, sin validación local ni precios de proveedores. [Tabla de escenarios](CATALOGO-CASOS-HISTORICOS.md).

CasosHistoricosSeeder lee database/seeders/data/casos_historicos.json. Resuelve materiales mediante los IDs/nombres del lote del catálogo identificado o una coincidencia inequívoca; exige unidad/categoría compatibles, sin renombrar, reactivar, actualizar precios o existencias. Tablero/pieza/unidad conservan cantidades enteras; litros/metros admiten fracciones. No hay despiece geométrico automático.

Antes de escribir valida los 35 registros, unidades y totales con decimales exactos. Lote/posición son claves internas de repetición, no códigos visibles. Una carga parcial/ambigua o archivo diferente al ya registrado se detiene, no se sobrescribe. Todo el lote y su auditoría son transaccionales. Repetir conserva casos y revisiones existentes.

DatabaseSeeder integra la carga después de los ejemplos de catálogo/pedidos, respetando APP_ENV local y TAJIBO_DATOS_SISTEMA. En una instalación local **php artisan migrate --seed** incluye ahora estos 35 casos; producción no carga ejemplos. CasoHistoricoFactory permite pruebas aisladas con una línea coherente de material. No se generaron usuarios, clientes, pedidos, cotizaciones o existencias nuevos al cargar estos casos en la instalación actual.

## 5. Esquema y archivos

Se aplicaron dos migraciones individuales; las 23 anteriores conservan sus SHA-256:

| Migración | Tabla |
|---|---|
| 2026_11_09_220000_create_casos_historicos_table.php | casos_historicos |
| 2026_11_09_220001_create_materiales_caso_table.php | materiales_caso |

casos_historicos conserva características, cantidad del grupo, fuentes/origen, revisión, costos estimados, presupuesto original, precio histórico, vínculo único al detalle de pedido y claves internas de demostración. materiales_caso conserva material/unidad y nombres capturados, posición única por caso, vínculo opcional a la fila de pedido, cantidad total, costo unitario/subtotal estimados nullable y su fuente. FK restrictivas protegen relaciones; ID autoincremental normal. No se duplica cliente, pedido, estados o inventario ni se crea otra tabla de auditoría.

Archivos nuevos principales: CasoHistorico/MaterialCaso, CasoHistoricoPolicy, CasoHistoricoController, RevisarCasoRequest, RetencionCasoService, RevisionCasoService, comando ConciliarCasos, CasosHistoricosSeeder, CasosPermisosSeeder, CasoHistoricoFactory, vistas admin/casos/index/show y las dos migraciones.

Integraciones limitadas: CierrePedidoService, DetallePedido y PedidoController/show (enlaces a los casos), DatabaseSeeder, AppServiceProvider, Acceso, configuración de permisos/navegación/rótulos y rutas. Tres rutas explícitas nuevas: GET /admin/casos, GET /admin/casos/{caso} y POST /admin/casos/{caso}/revisar; admin.casos.index/show/revisar. **79 entradas de rutas** en total. No se instalaron dependencias nuevas; se reutilizan recursos locales AdminLTE/SweetAlert2 existentes, sin modificar vendor.

## 6. Respaldo, ensayo y MySQL local

Origen comprobado: **carpinteria_el_tajibo**. Antes: 27 tablas/23 migraciones/11 pedidos y cero terminados validados. Después: **29 tablas, 25 migraciones alineadas y 51 FK**. Los 35 casos cargados son independientes de los pedidos existentes, que conservaron datos y estados.

- Respaldo completo: storage/app/private/fase5a-2026-11-09/respaldo-completo.sql, **377059 bytes**.
- SHA-256: **d118f83903ed8afbfdb465f1abf934e4b10645e10357ba0690724543d5380db6**.
- Restauración idéntica verificada en **carpinteria_el_tajibo_ensayo_5a_20261109172713_d0d61f**, con todas las tablas/registros originales cotejados.
- Ensayo primero en ese clon: dos migraciones, carga completa, incremento de permisos y repetición sin cambios; datos anteriores preservados.
- Dos procesos concurrentes cerrando el mismo pedido sintético: un éxito y un rechazo de versión, un caso por detalle y líneas sin duplicados. Conciliación repetida conserva la instantánea. FK/unicidad rechazan referencias o retenciones duplicadas.
- Antes de aplicar se cotejó nuevamente el origen con el respaldo. Durante mantenimiento breve se ejecutaron solo las dos migraciones y los dos seeders específicos, sin volver a generar los módulos anteriores.
- En la base local se compararon hashes de todas las filas anteriores y sus columnas; únicamente se admitieron las dos migraciones nuevas, dos permisos, tres asignaciones, tres auditorías y las dos tablas nuevas con 35/172 filas. La carga coincide con el ensayo excluyendo únicamente created_at/updated_at. Repetición verificada idéntica.

Usuarios/contraseñas, roles, permisos previos, clientes, catálogo, precios de cotización, pedidos, existencias, movimientos y auditorías anteriores se conservaron. No se eliminaron tablas/columnas/registros ni se usaron migrate:fresh o db:wipe en MySQL. No se descuentan existencias ni se generan movimientos por casos.

Evidencias privadas: diagnostico-inicial.json, respaldo-verificado.json, estructura-ensayada.json, carga-ensayada.json, ensayo-aprobado.json, aplicacion-verificada.json y verificacion-local.json en el directorio de esta entrega. Los auxiliares de despliegue no son instaladores para volver a ejecutar; respaldo y clon contienen datos privados.

## 7. Conciliación de trabajos anteriores

php artisan tajibo:conciliar-casos consulta pedidos terminados/entregados con validaciones finales, sin escribir. Con --aplicar retiene únicamente los casos faltantes, manteniendo los pedidos y la unicidad por detalle; no fabrica validaciones ni termina trabajos pendientes.

Comprobación adicional de solo lectura: **51 claves foráneas comprobadas y cero referencias huérfanas**, sin cambios de datos. Evidencia privada: verificacion-integridad.json.

Consulta ejecutada en MySQL local: **0 pedidos candidatos**. No había casos operativos previos que conciliar. Las escrituras de conciliación/retención se comprobaron en bases aisladas, no creando un trabajo real ficticio en la base operativa.

## 8. Pruebas y capturas

| Comprobación | Resultado |
|---|---|
| Suite completa PHP, SQLite aislado | **211 aprobadas, 2187 aserciones, 248.90 s** |
| CasosHistoricosTest/CasosSeederTest en la suite | 13 pruebas de retención/revisión y 6 de carga/permisos/rollback/unidades |
| Vistas finales después del ajuste móvil | **13 aprobadas, 109 aserciones** |
| Edge/Playwright, SQLite temporal | **97 comprobaciones aprobadas**, sin errores JavaScript |
| MySQL restaurado | Cinco grupos de comprobaciones, incluidas concurrencia real e integridad |
| MySQL operativo después de aplicar | Diez renderizados con propietario/maestro, datos 35/172 pendientes, referencias de caso/uso/validación sin huérfanos comprobados y sin escrituras |

La regresión incluye autenticación/permisos, auditoría, catálogo/existencias, cotizaciones/PDF, pedidos, planificación y 4C/4D. Navegador comprueba los 35 casos/172 materiales, consulta de cantidades/costos/precio, revisión diferenciada con SweetAlert2, conservación de origen y claro/oscuro/escritorio/móvil. Se comprobó además que ambos botones de revisión caben dentro de la pantalla móvil.

El chequeo operativo renderiza controladores/Blade con MySQL; no equivale a una sesión HTTP autenticada real sobre esa base. Las capturas usan fixtures sintéticas aisladas; no acreditan revisión humana del negocio. No se verifica precisión CBR/k-NN, pagos, costos reales ni rendimiento productivo.

- [Resultado de navegador](capturas/fase5a/resultado-browser.json).
- [Listado de casos](capturas/fase5a/casos-listado-1440.png).
- [Detalle con materiales y costos](capturas/fase5a/caso-detalle-1440.png).
- [Detalle móvil](capturas/fase5a/caso-detalle-390.png).
- [Detalle oscuro móvil](capturas/fase5a/caso-detalle-oscuro-390.png).

Los pesos, rangos, k y umbral siguen sin activarse en config/similitud.php. Antes de 5B corresponde revisar el método y sus parámetros, sin presentar el puntaje de similitud como probabilidad de acierto.
