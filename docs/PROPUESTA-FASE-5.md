# Diseño propuesto de fase 5 — Casos históricos y CBR/k-NN

> Cierre posterior, 10/11/2026: 5A–5C implementadas; [entrega 5C](FASE-5C.md) documenta comparación económica y límites actuales. El resto conserva la propuesta histórica.

> Estado posterior: diseño 5B aprobado e implementado el 9 de noviembre de 2026. Véase [entrega 5B](FASE-5B.md). El texto siguiente conserva el análisis previo a aprobación; sus menciones de preparación no describen el estado actual. 5A y 5B entregadas; 5C pendiente.

**Estado actualizado: 5A aprobada e implementada el 9/11/2026**, con costos estimados identificados, dos tablas y 35 ejemplos cargados. El texto siguiente conserva la propuesta previa; consultar [entrega 5A](FASE-5A.md) para resultados. 5B/5C y sus parámetros siguen pendientes de revisión y aprobación.

Fecha: 9 de noviembre de 2026. Preparación autorizada al pasar de 4D a fase 5. El usuario confirmó generar ahora 35 casos ficticios orientados a Bolivia y limitar los antecedentes a pedidos del sistema y demostración. Solicitó una recomendación sobre los costos; se propone conservar estimaciones identificadas sin campos nuevos de costo real. Esta última elección y el diseño técnico se presentan para revisión antes de nuevas migraciones o cambios grandes.

## 1. Estado comprobado y decisiones

Lectura MySQL del 9/11: carpinteria_el_tajibo, 27 tablas y 23 migraciones. Hay 4 pedidos Aceptado y 7 En fabricación, los 11 identificados en auditorías como ejemplos; cero terminados/entregados y cero con validaciones finales. Por tanto, no existe hoy una base de trabajos elegibles que justifique recomendaciones reales. No se ha modificado MySQL en esta preparación.

config/similitud.php está deshabilitado con distancia, normalización, pesos, k y umbral sin definir; máximo de resultados 3. config/tajibo.php excluye simulados por defecto. La opción lateral Casos históricos no tiene ruta activa. No se activaron parámetros arbitrarios durante este análisis.

**Recomendación económica para decidir:** conservar precio acordado, cantidades utilizadas registradas y costos estimados de la cotización como conceptos distintos. No pedir otra ficha larga ni llamar costo real a un estimado. La recomendación histórica mostrará el precio de referencia del caso; la actualización económica calculará materiales con costos actuales conocidos y mano de obra/otros costos de referencia, siempre editable antes de aprobar. Registrar gastos reales quedaría para un alcance posterior si el usuario lo solicita.

Los 35 escenarios están preparados en [datos para el futuro seeder](../database/seeders/data/casos_historicos.json) y [tabla de revisión](CATALOGO-CASOS-HISTORICOS.md): 6 escritorios, 6 mesas, 6 sillas, 6 armarios, 6 estantes y 5 puertas. Son contextos domésticos de La Paz y El Alto, medidas cm, moneda BOB/Bs y nombres de materiales compatibles con los ejemplos del catálogo. Los precios/cantidades son ilustrativos, sin precios actuales de proveedores ni revisión del propietario/maestros. Todos tienen origen simulado, revisión pendiente y responsables/fechas de revisión NULL. No hay clientes, entrevistas, ventas o aprobaciones humanas inventadas.

El JSON contiene 35 escenarios y 172 líneas de materiales. Se verificaron sumas con aritmética entera decimal, formatos, procedencia e integridad de unidades: tableros/piezas/unidades enteros; litros/metros admiten fracciones. No se cambia esa regla del catálogo para facilitar el generador. No hay despiece automático derivado de dimensiones. Este archivo todavía no ejecuta un seeder, no está conectado a DatabaseSeeder y no se ha cargado a MySQL.

## 2. Entregas propuestas

### 5A — Base de casos y 35 ejemplos

- Activar Casos históricos con index/show y revisión sencilla. Mostrar tipo, dimensiones, material, acabado, precio permitido, origen, revisión y procedencia de costos; sin datos personales de clientes.
- Conservar las estructuras escolares: controladores explícitos, modelos Eloquent, rutas /admin con admin.*, Blade por módulo y SweetAlert2; servicios para retención/revisión transaccional y Form Requests para operaciones complejas.
- Preparar CasoHistoricoFactory para pruebas y CasosHistoricosSeeder por módulo, leyendo el lote de 35 escenarios. Carga reproducible/idempotente con claves internas de lote/posición, nunca IDs visibles alternativos. No crear DatosBolivianos/MasDatosBolivianos ni alterar los dos lotes antiguos.
- Resolver referencias de materiales/unidades mediante los registros identificados del catálogo; no asumir IDs ni sobrescribir nombres, costos o existencias. Ante un nombre ambiguo, unidad incompatible o falta de material, detener la carga con diagnóstico, sin asociación silenciosa ni borrados.
- Integrar la carga local con migrate --seed conforme a la decisión vigente, protegida por entorno y configuración; pruebas solo en bases aisladas. No convertir los pedidos actuales en terminados para crear casos.
- Retener un caso por detalle de pedido al terminar con validaciones finales, dentro de la transacción. Entrega posterior no crea otro caso. Conciliar de forma controlada los terminados/entregados validados previos a la integración, preservando origen e impidiendo duplicados.
- La procedencia simulada se determina con los IDs de todos los lotes de auditoría aplicables, no solo el primero. Los nuevos casos independientes de demostración también conservan su origen y lote. Revisión humana y origen siguen separados.

### 5B — Recuperación y comprobación del método

- Buscar únicamente entre casos elegibles del mismo tipo exacto de mueble. No utilizar el precio, costo, cliente o ubicación como características de similitud.
- Propuesta técnica: distancia mixta inspirada en Gower, para dimensiones/componentes/cantidad numéricos y material/acabado categóricos. Recuperar los k vecinos más cercanos, mostrando como máximo 3. Es recuperación k-NN; no se afirma que exista un clasificador entrenado o un predictor con precisión comprobada.
- Para un número: diferencia absoluta dividida por el rango de referencia del perfil, acotada a [0,1]. Si el rango es cero, igualdad aporta 0 y diferencia aporta 1. Material/acabado: igualdad 0, diferencia 1. Distancia global: promedio ponderado de diferencias. Similitud = 100 × (1 − distancia); es un puntaje del método, no una probabilidad de acierto.
- Rangos/perfil se versionan y registran junto a cada consulta; no incorporar la consulta o el dato evaluado a sus propios parámetros de evaluación. No cambiar silenciosamente la escala entre consultas históricas. Excluir datos incompletos, unidades incompatibles y casos descartados; nunca reemplazar faltantes por ceros.
- Hipótesis piloto para revisar, **sin activar**: dimensiones 45 % (15 cada una), material principal 25 %, acabado 10 %, puertas/cajones/divisiones 15 % (5 cada una), cantidad 5 %; k=3 y umbral inicial experimental 80 %. No son parámetros definitivos ni validados localmente. Documentar sensibilidad y revisión antes de su uso normal.
- Si solo hay uno o dos vecinos elegibles, mostrar los disponibles con cobertura limitada; no inventar tres ni exigir una cantidad arbitraria de antecedentes. Si ninguno supera el umbral, mostrar No hay antecedentes suficientemente similares y conservar el flujo manual rápido.
- Comparar inicialmente los escenarios sintéticos por tipo y revisar ejemplos de coincidencia/rechazo. Un experimento con datos generados solo comprueba coherencia interna; no demuestra exactitud comercial. No ajustar o reportar precisión usando la misma consulta como su propio vecino ni clones como muestras independientes.
- Perfil configurado por propietario; rangos, pesos, k, umbral y versión visibles en documentación técnica, sin campos técnicos en la cotización cotidiana. Si falta una configuración válida, el motor permanece desactivado y explica el motivo.

### 5C — Aplicación sencilla en cotizaciones

Al completar las características actuales de un mueble, permitir Buscar casos similares. Mostrar hasta tres tarjetas con porcentaje, diferencias, procedencia y dos referencias económicas: histórica y actualizada. El usuario elige una tarjeta y Aplicar referencia llena materiales/cantidades, mano de obra y otros costos, editables. No se pide otra captura completa.

No mezclar automáticamente materiales de distintos vecinos ni promediar muebles incompatibles. Aplicar no guarda/aprueba la cotización ni reemplaza datos ya llenados sin confirmación. El servidor vuelve a validar los datos y el catálogo al guardar/aprobar, para evitar referencias obsoletas/manipuladas.

Normalizar referencias por cantidad de muebles manteniendo los totales originales del caso y su tamaño de lote. Para sugerir al nuevo grupo, usar las cantidades por unidad de referencia y multiplicar una sola vez; redondear solo en el punto documentado y respetar unidades enteras. Mostrar una advertencia si cambia el tamaño del lote: los ahorros conjuntos anteriores no son un descuento automáticamente válido para otro trabajo. No escalar materiales geométricamente por cambio de dimensiones. Otros costos fijos se proponen como total del detalle y no se multiplican otra vez por muebles.

Costo desconocido sigue pendiente y bloquea un total supuestamente completo: nunca se convierte en cero. Materiales inactivos/no disponibles se señalan para sustitución explícita. El maestro consulta costos y aplica estimaciones; no consulta ganancia/recargo administrativo ni aprueba precios. El propietario conserva su decisión final.

Los casos reales revisados se usan en el modo normal. La demostración se habilita explícitamente en entorno local con sus resultados rotulados; los casos simulados pendientes de revisión solo pueden mostrarse como ejemplos experimentales, jamás como recomendaciones validadas. No mezclar silenciosamente ambos orígenes en un resultado o evaluación ni activar simulados en producción por instalar semillas.

## 3. Esquema mínimo candidato

Se proponen dos tablas de negocio, cada una con su migración individual. Las 23 migraciones aplicadas no se editan.

| Tabla | Datos/relaciones propuestos |
|---|---|
| casos_historicos | ID autoincremental; detalle_pedido_id nullable único para procedencia operativa; tipo/material principal y sus nombres capturados; dimensiones cm, componentes, acabado, cantidad del grupo; fecha de referencia; precio histórico total y desglose económico con fuente estimada/real explícita; origen real/simulado, lote/posición internos únicos de demostración; estado de revisión, responsables/fechas diferenciados de materiales y economía; versión y timestamps. |
| materiales_caso | ID; caso_historico_id; posición única por caso; material/unidad y nombres capturados; enlace opcional a material_pedido original; cantidad total utilizada/referencia; costo unitario histórico nullable y su fuente; subtotal estimado cuando pueda calcularse. Conservar totales originales, sin inventar un decimal unitario exacto para un reparto periódico. |

No duplicar cliente, pedido, estados o inventario. Historial de revisiones mediante auditorias existente, sin otra tabla genérica de historial. La estructura exacta se concretará al aprobar el diseño económico; si se conserva este alcance serían 25 migraciones y 29 tablas, sujeto a diagnóstico real y ensayo.

Las características del pedido proceden de lo acordado en la cotización; no se presentarán como nuevas mediciones físicas al terminar. La retención conserva esa fuente. Si el principal acordado no figura utilizado o no hay materiales positivos, retener el registro con observación de inelegibilidad, sin cambiar la fabricación histórica ni recomendar un mueble sin materiales. Los casos incompletos no se vuelven elegibles por rellenar cero o por aprobarlos administrativamente sin resolver el dato.

Para casos de pedidos, conservar la instantánea del trabajo cerrado; no permitir editarla como si se hubiera fabricado otra cosa. Las correcciones comerciales/físicas posteriores necesitan su procedimiento. Los casos de demostración pendientes pueden revisarse con auditoría; cambiar datos invalida su revisión. La revisión no convierte simulado en real.

## 4. Permisos y archivos previstos

Reutilizar casos.consultar y proponer capacidades diferenciadas para revisión de materiales y revisión económica. Propietario/maestro revisan materiales; solo propietario valida precio y datos económicos definitivos. La concesión de permisos nuevos se documentará y verificará sin restablecer personalizaciones anteriores. El endpoint de recuperación exige también las consultas pertinentes de cotizaciones/costos; el de aplicación comprueba crear/modificar cotización.

Archivos candidatos: CasoHistorico/MaterialCaso, CasoHistoricoController, CasoHistoricoPolicy, RetencionCasoService, RecuperacionCasosService y requests específicos; vistas admin/casos; ampliación limitada de CotizacionController, del formulario y su JavaScript; llamada de retención en CierrePedidoService. Usar servicios concretos, sin repositorios genéricos ni otra tecnología. Los parámetros siguen en la configuración propia, fuera de vendor.

## 5. Pruebas y MySQL

Antes de aplicar migraciones: conexión/esquema/conteos correctos, respaldo completo nuevo restaurado y comparado, ensayo de migraciones/carga/retención en clon, origen nuevamente cotejado. No usar migrate:fresh, db:wipe, borrar columnas/tablas ni reiniciar datos operativos. Ante diferencias, detener e informar.

Probar permisos, inmutabilidad, costos desconocidos, cálculos exactos, fracciones, lotes y otros costos fijos; retención una sola vez por detalle, conciliación previa y cierres concurrentes; repetición del seeder sin duplicar/cambiar los casos revisados. Simulados nunca pasan por reales ni se validan automáticamente como evidencia humana.

Probar identidad/distancia/rangos constantes/datos fuera de rango, filtros por tipo/origen/revisión, k y umbral, desempates deterministas, datos incompletos y ausencia de referencias. Comprobar aplicación sin sobrescritura inesperada, precio/ganancia protegidos, historial/cotizaciones aprobadas intactos y cero movimientos de existencias.

Suite de regresión completa y navegador para ambos roles, móvil/claro/oscuro, modo demostración y fallback manual; informe con límites y capturas. Las pruebas de 4D son antecedentes y no acreditan un motor nuevo todavía.

## 6. Bibliografía técnica y decisiones pendientes

La separación recuperación/reutilización/revisión/retención corresponde al marco CBR de [Aamodt y Plaza, 1994](https://www.iiia.csic.es/~enric/papers/Aamodt_1994_Case.pdf). La elección propuesta de una comparación mixta se apoya en el trabajo original de [Gower, 1971](https://doi.org/10.2307/2528823). Estas fuentes fundamentan el enfoque general; no validan los pesos, umbral, cantidades o precios específicos de esta carpintería.

Pendiente de aprobación antes de implementar: conservar estimaciones identificadas en los casos; esquema de dos tablas; separación 5A/5B/5C; método/perfil piloto y revisión de sus parámetros antes de activarlo. No se incorpora importación Excel o registro manual de históricos externos, según decisión del usuario.
