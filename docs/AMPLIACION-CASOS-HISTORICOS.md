# Ampliación de casos históricos

Estado de consumos actualizado el 17/11/2026: se mantienen 87 casos; cantidades y costos corregidos mediante referencias documentadas. Véase [Consumos de materiales](CONSUMOS-MATERIALES.md). Los importes/consumos de las secciones anteriores conservan el historial de cargas.

Actualizado el 17/11/2026. Estado vigente: 87 casos en 47 pedidos entregados, 59 cotizaciones y 82 pagos; 36 clientes, 27 materiales y nueve tipos de muebles. Fechas desde 16/03/2020 hasta 05/10/2026. Clientes solo de La Paz y El Alto, con correo opcional vacío. Las secciones anteriores documentan cambios previos; la recarga vigente con IDs del 1 al 87 se detalla al final.

## Carga anterior de 139 casos

Se añadieron 52 casos a los 87 existentes, mediante 21 pedidos adicionales. Total: 139 casos vinculados a detalles de 60 pedidos. Permanecen otros ocho pedidos abiertos.

| Casos por pedido | Pedidos | Casos |
|---|---:|---:|
| 1 | 16 | 16 |
| 2 | 18 | 36 |
| 3 | 17 | 51 |
| 4 | 9 | 36 |
| Total | 60 | 139 |

Tres tipos nuevos: Velador, Cómoda y Vitrina, con 12 casos de cada uno. El catálogo tiene nueve tipos. Se añadió Vidrio para vitrina, por pieza, con referencia de Bs 90; es un costo ilustrativo de la carga, no una cotización verificada de proveedor. Se conservaron los 26 materiales anteriores y sus datos.

Totales locales: 80 cotizaciones, 68 pedidos, 139 casos, 124 pagos, 36 clientes, 27 materiales, nueve tipos y tres usuarios. Los pedidos se distribuyen en cuatro aceptados, cuatro en fabricación, tres terminados y 57 entregados. Sin tablas o migraciones nuevas: 31 tablas y 27 migraciones.

## Fechas y costos

Los 21 pedidos adicionales se distribuyen entre marzo de 2020 y septiembre de 2026: cotización tres días antes de aceptar, aprobación un día antes, inicio de fabricación dos días después, cierre a los 18 días y entrega a los 21. Anticipo del 40 % al aceptar y cancelación del saldo al entregar, con efectivo y QR; antes de 2023 se usa efectivo.

Ninguna fecha comercial supera 05/10/2026. Máximas comprobadas: cotizaciones y aceptación de pedidos, 03/10/2026; casos, pagos y entrega prevista, 05/10/2026. Casos por año: 2020: 14; 2021: 21; 2022: 21; 2023: 22; 2024: 20; 2025: 16; 2026: 25.

Cada detalle conserva materiales y cantidades utilizadas, costos unitarios capturados, mano de obra, otros costos y precio acordado del grupo. No se considera cero un costo desconocido. Los nuevos pedidos están completamente pagados; los saldos de los anteriores no cambiaron. Totales: Bs 237.545,52 acordado; Bs 229.902,97 cobrado; Bs 7.642,55 pendiente.

Se conservó cada registro anterior completo, comprobando las relaciones mediante el mapa inverso de IDs. Los IDs se reorganizan cronológicamente dentro de la carga y los listados mantienen ID descendente. Cuentas, contraseñas, permisos, clientes, catálogo anterior, existencias y movimientos permanecen idénticos. No se reinició la base.

Las fechas de auditoría corresponden al mantenimiento actual; los antecedentes conservan procedencia simulada y revisión pendiente. La creación por servicios no acredita revisión humana o costos comerciales comprobados.

## Código y recomendaciones

CasosHistoricosSeeder mantiene la carga base y añade ampliar() con el lote casos_historicos_139_v1. DatabaseSeeder lo ejecuta después de HistorialCarpinteriaSeeder. Las plantillas adicionales están en database/seeders/data/casos_historicos_ampliacion.json y los tres tipos en TiposMuebleSeeder. La repetición de DatabaseSeeder conserva datos y no duplica registros; migrate --seed incluye la ampliación en una instalación nueva.

Perfil activo: config/perfiles/similitud-casos-historicos-v2.json. Conserva los 25 descriptores y rangos anteriores y añade 36 referencias de los tres tipos nuevos; total 61 referencias. Se conservan distancia, normalización, pesos, k=3 y umbral=80 %. No se modificaron controladores, modelos, rutas, vistas o fórmulas de aprobación.

El perfil anterior permanece como referencia versionada; no hay un selector ni dos modos de búsqueda. El perfil nuevo se activó después de verificar los datos, con huella SHA-256 be07f21f6ef8e94570483f934f565c99e569afd76a27e18b6a3be7c10f383727. Las consultas de control de los nueve tipos devolvieron referencias elegibles y coincidencia exacta del mejor vecino. Esto verifica integridad del perfil, no precisión comercial.

## Respaldo y comprobaciones

Respaldo nuevo completo, restaurado y comparado antes de aplicar; aplicación transaccional con claves foráneas activas. Evidencia privada: storage/app/private/ampliacion-historial-20261117154139_b0daf2/. SHA-256 de respaldo-aplicar.sql: 48386d29fa862f454dded7e36e90fabd5083b6fbde1b5db531030654a81c0614.

- MySQL: 139 detalles únicos de casos, relaciones y cronologías coherentes, costos/saldos válidos, contenido anterior íntegro y repetición idempotente.
- Pruebas aisladas de casos, similitud, instalación y tipos: 54 pruebas distintas cubiertas en tandas. La primera tanda tuvo 51 aprobadas y dos fallos; tras corregir el marcador y el recuento del catálogo, la tanda final de 14 pruebas, incluida la nueva prueba de recomendaciones, pasó con 1236 aserciones. No se ejecutó la suite completa.
- Pint aprobado para los PHP modificados.
- Edge/Playwright: paginación de 139 casos, fichas y enlaces de pedidos con uno a cuatro casos, clientes, pagos, PDF y búsquedas en formulario de Velador/Cómoda/Vitrina. Escritorio y móvil sin desbordamiento ni errores JavaScript. Evidencia: docs/capturas/historial-139/resultado-browser.json.
- Base local: 230 respuestas HTTP 200 y cuatro PDF válidos; dos redirecciones obligatorias al perfil de maestros. Lecturas sin modificar datos.
- Excel actualizado desde MySQL: 68 pedidos, 988 líneas de costos, 124 pagos y 36 clientes; fórmulas, saldos y totales verificados, sin errores, cuatro hojas renderizadas y revisadas. Es una exportación editable, no una conexión en vivo.

El ensayo detectó una colisión de nombre de variable al guardar el identificador de ordenación; se corrigió y repitió la verificación de conservación. La prueba de navegador se adaptó al nuevo paginado y se corrigió su ruta de caché temporal en Windows para comprobar el perfil preparado. Ninguno de esos ensayos modificó la base local. Las tres copias temporales creadas para esta ampliación se retiraron; respaldos y evidencias conservados.

## Ajuste vigente a 132 casos — 17/11/2026

Por corrección del usuario se retiraron siete casos de los tipos anteriores, todos adicionales a los 87 de la carga base y sin referencias del perfil. Se retiraron sus cuatro pedidos completos, cuatro cotizaciones, siete detalles por módulo, 33 líneas de materiales por módulo, 16 estados y ocho pagos, sin modificar importes o datos de los registros conservados. Los casos de veladores, cómodas y vitrinas permanecen: 12 de cada tipo.

Estado final: 132 casos, 76 cotizaciones, 64 pedidos, 116 pagos y 637 líneas de materiales de casos. Se conservan nueve tipos, 27 materiales, 36 clientes y tres usuarios; 31 tablas y 27 migraciones. Distribución: 14 pedidos con un caso, 17 con dos, 16 con tres y nueve con cuatro, total 56 pedidos con casos. Otros ocho pedidos permanecen abiertos. Estados: cuatro aceptados, cuatro en fabricación, tres terminados y 53 entregados.

No se cambiaron fechas de los registros conservados ni se renumeraron IDs. Se mantiene la cronología hasta 05/10/2026 y la búsqueda con las 61 referencias del perfil v2, sin cambios de rangos, pesos, distancia, k o umbral. La auditoría conserva las filas retiradas y los metadatos anteriores; las listas operativas de seeders se concilian para no apuntar a registros retirados.

CasosHistoricosSeeder::ajustarCantidad se ejecuta al final de DatabaseSeeder, una sola vez por casos_historicos_132_v1. Conserva la compatibilidad con las cargas anteriores; una instalación nueva con migrate --seed termina en 132 casos y repetir el comando no duplica ni cambia registros. No ejecutar los auxiliares privados como instaladores.

Respaldo completo nuevo, restaurado y comparado antes de aplicar; ensayo aislado y transacción con claves foráneas activas. Cuentas, contraseñas, permisos, clientes, catálogo, ajustes, existencias, movimientos y todos los registros no retirados se comprobaron íntegros. Evidencia privada: storage/app/private/reduccion-historial-20261117160740_cbe50a/. SHA-256 de respaldo-aplicar.sql: a848f35e091b90672d3292364ae7a87343d2276b55f2f2f9c6af58b76f31a294.

Pruebas recién ejecutadas: InstalacionSeedTest, cuatro pruebas/1069 aserciones aprobadas en SQLite aislado, 61,25 s; Pint aprobado. Edge/Playwright en MySQL aislado: paginación de 132 casos, fichas/enlaces, clientes, pagos, PDF y propuestas de los tres tipos nuevos, escritorio/móvil sin errores ni desbordamiento; resultado en docs/capturas/historial-132/. Base local: 219 respuestas HTTP 200 y cuatro PDF válidos, más las dos redirecciones obligatorias al perfil de maestros, sin escrituras durante la comprobación. No se ejecutó la suite completa.

Excel actualizado desde MySQL: 64 pedidos, 941 líneas de costos, 116 pagos y 36 clientes; fórmulas, subtotales y saldos comprobados, sin errores. La copia temporal de este ajuste se retiró después de verificar la aplicación; respaldo conservado.

## Calendario vigente desde mediados de marzo de 2020 — 17/11/2026

El usuario confirmó el inicio a mediados de marzo de 2020. La primera cotización es del 16/03/2020 y la primera aceptación del 18/03/2020; el corte se mantiene en 05/10/2026. Se distribuyeron los 132 casos así: 2020, 16; 2021, 20; 2022, 20; 2023, 20; 2024, 20; 2025, 20; 2026, 16. Cada año tiene ocho pedidos cerrados con casos, distribuidos entre meses diferentes. Se conservan los ocho pedidos abiertos, repartidos entre julio y septiembre de 2026.

Límites comprobados: máximo cuatro casos por mes y cuatro aceptaciones de pedidos por mes; sin fechas duplicadas en las 76 cotizaciones, las 64 aceptaciones, los 116 pagos o las 64 entregas previstas. Hay 56 fechas de cierre distintas: los muebles de un mismo pedido comparten la fecha de finalización, sin duplicarla entre pedidos diferentes.

Se comprobó cotización → aprobación → aceptación → fabricación → cierre → entrega, así como las fechas de uso/validación de materiales y de retención de casos. No se encontraron contradicciones. Los anticipos no preceden a la aceptación y los pagos permanecen dentro del período. La fecha prevista es un compromiso: un pedido aún abierto puede estar pendiente de entrega; no se cambiaron sus estados para completar el calendario.

HistorialCarpinteriaSeeder::redistribuirFechas se ejecuta al final de DatabaseSeeder, una sola vez por historial_distribuido_marzo_2020_octubre_2026_v1. Reconoce la carga autorizada y conserva cantidades, materiales, costos, precios, versiones, estados, procedencia y revisión. Se conciliaron los IDs y todas sus referencias para mantener ID descendente y cronología coherente. Se mantienen los 132 casos, 64 pedidos, 76 cotizaciones, 116 pagos, nueve tipos y 27 materiales. Perfil v2, sus 61 referencias, rangos, pesos, k y umbral sin cambios. Nuevas operaciones normales conservan la fecha real automática.

Respaldo nuevo completo restaurado y comparado, ensayo aislado, transacción y claves foráneas activas. Se comparó el contenido no temporal de todos los registros mediante el mapa inverso de IDs; cuentas/contraseñas, permisos, clientes, catálogo, existencias, movimientos, ajustes y migraciones se conservaron íntegros. Las auditorías mantienen su fecha real y explican los valores y períodos anteriores. Evidencia privada: storage/app/private/distribucion-historial-20261117162957_7395c3/. SHA-256 de respaldo-aplicar.sql: b7a71dffa67bf4a861937ffd6b47e7d4b8b07e6929849077737ad0dbbb31a33a.

Verificaciones recién ejecutadas: InstalacionSeedTest, cuatro pruebas y 1782 aserciones aprobadas, 69,57 s; sintaxis PHP y Pint aprobados. La prueba inicial detectó una coincidencia entre dos cotizaciones; se agregó la reserva de fechas y se repitieron las comprobaciones. Edge/Playwright: fechas inicial/final, paginación, fichas y enlaces de pedidos de uno a cuatro casos, clientes, pagos, PDF y propuestas de los tres tipos nuevos, sin errores JavaScript o desbordamiento; resultado en docs/capturas/historial-distribuido/. Base local: 219 respuestas HTTP 200 y cuatro PDF válidos, sin escrituras durante las lecturas; diagnóstico de fechas sin errores. Repetición local de DatabaseSeeder comprobada sin cambios. No se ejecutó la suite completa.

Excel actualizado desde la base: 64 pedidos, 941 líneas de costos, 116 pagos y 36 clientes, con el período visible 16/03/2020–05/10/2026. Fórmulas, saldos y totales comprobados; precios e importes conservados. Las dos copias temporales de este trabajo fueron retiradas; respaldos y evidencias conservados.

## Estado vigente: todos entregados y contactos locales — 17/11/2026

El usuario pidió todos los pedidos en Entregado, ubicaciones solo de La Paz y El Alto y correos opcionales vacíos. Se le informó que completar los ocho pedidos abiertos generaría nueve casos adicionales; confirmó conservar exactamente 132 ajustando los antecedentes adicionales.

Se retiraron los nueve casos adicionales sin referencia del perfil que aún quedaban de la ampliación. Sus detalles y materiales relacionados se retiraron de pedido/cotización; cinco pedidos conservaron sus otros muebles, recalculando sus totales y los dos pagos del 40/60 %, y un pedido quedó sin muebles y se retiró con su cotización, estados y dos pagos. Los precios y características de cada mueble conservado no cambiaron. Las 61 referencias del perfil, incluidos los 12 casos de cada tipo nuevo, permanecen.

Los cuatro pedidos aceptados pasaron por fabricación/cierre/entrega, los cuatro en fabricación se completaron y entregaron y los tres terminados se entregaron mediante los servicios vigentes. Se registraron las cantidades utilizadas según su planificación en esta carga y se retuvieron nueve casos nuevos, conservando origen simulado y revisión pendiente. No se generaron cobros para completar saldos.

Estado final: 63 pedidos, todos entregados; 132 casos vinculados a sus 132 detalles; 75 cotizaciones, 114 pagos, 36 clientes, nueve tipos y 27 materiales. Hay 22 pedidos con un caso, 19 con dos, 16 con tres y seis con cuatro. Materiales de pedidos/casos: 616 líneas; detalles de cotización: 144 y sus materiales: 647; 252 eventos de estado, cuatro por pedido. Sin cambios de tablas/columnas/migraciones: 31 tablas y 27 migraciones.

Se conserva el reparto anual: 16 casos en 2020, 20 por año entre 2021 y 2025 y 16 en 2026, ahora mediante nueve pedidos por año. Período desde 16/03/2020 hasta 05/10/2026, último cierre el 03/10/2026. Se comprobaron los límites mensuales y fechas únicas de cotizaciones, aceptaciones, pagos y entregas previstas, con secuencia de estados estrictamente ordenada. Casos del mismo pedido comparten su fecha de cierre.

Los 36 clientes tienen dirección con prefijo La Paz o El Alto y email NULL. Se actualizaron las instantáneas de clientes de pedidos/cotizaciones y el contexto de los casos. ClientesSeeder y ClienteFactory solo generan ubicaciones locales y correo opcional NULL; HistorialCarpinteriaSeeder no vuelve a introducir correos de clientes. Usuarios/perfiles y Configuración conservan sus correos porque sus validaciones los exigen; cuentas y contraseñas permanecen intactas.

CasosHistoricosSeeder::entregarHistorial se ejecuta al final de DatabaseSeeder una sola vez por historial_entregado_local_sin_correo_v1. HistorialCarpinteriaSeeder::redistribuirFechas conserva la compatibilidad con el calendario anterior y distribuye la carga final completamente entregada. Repetir DatabaseSeeder no modifica datos. No se crearon módulos, controladores, modelos o rutas ni se cambiaron fórmulas de negocio.

Importes finales de la carga: Bs 212.154,19 acordado y Bs 204.511,64 recibido; saldo pendiente Bs 7.642,55, igual al anterior. Entregado no implica pagado. Los ajustes de totales/pagos de cinco pedidos corresponden a la corrección de estos datos de carga autorizada, no a nuevos cobros o devoluciones reales. La auditoría conserva los valores anteriores y los registros retirados.

Respaldo nuevo completo, restaurado y comparado antes de aplicar; ensayo aislado, claves foráneas activas y transacción. Evidencia privada: storage/app/private/entregados-historial-20261117170258_d10dfb/. SHA-256 de respaldo-aplicar.sql: 169a20454e94c0d1857b126175cf6b253d2eb194869995bb15a0ca3dbe55694a. Cuentas, permisos, catálogo, existencias, movimientos, ajustes y registros no sustituidos del perfil/casos fueron verificados. La copia temporal se retiró; respaldos conservados.

Pruebas recién ejecutadas:
- InstalacionSeedTest: cuatro pruebas/2066 aserciones aprobadas, 79,69 s.
- FaseDosTest, CierrePedidoTest y SeedersModulosTest: 41 pruebas/652 aserciones aprobadas, 68,67 s. Total: 45 pruebas/2718 aserciones, sin ejecutar la suite completa.
- Sintaxis PHP y Pint aprobados.
- MySQL: totales por detalle/cotización/pedido, ausencia de sobrepagos, saldo global conservado, estados, fechas, distribución anual/mensual, ubicaciones/contactos, relaciones, conservación e idempotencia comprobados.
- Edge/Playwright: paginación, fichas, clientes sin correo, pagos, PDF, propuestas de los tres tipos nuevos y móvil sin errores JavaScript/desbordamiento. Resultado en docs/capturas/historial-entregado/.
- Base local: 218 respuestas HTTP 200, cuatro PDF válidos y dos redirecciones obligatorias al perfil de maestros, sin escrituras durante las lecturas.
- Nueve consultas locales de recomendaciones correctas y perfil v2 intacto. Las coincidencias de control no acreditan validación comercial o humana.
- Excel actualizado desde MySQL: 63 pedidos entregados, 880 líneas de costos, 114 pagos y 36 clientes locales sin correo opcional; fórmulas, saldos y totales verificados y hojas renderizadas.

## Cantidad vigente: 87 casos

Se verificaron 132 filas en casos_historicos antes del ajuste. El ID mayor era 183, que no representa la cantidad de filas. Por solicitud del usuario se redujo efectivamente la carga a 87 casos; la pantalla muestra ahora el total global y, al filtrar, la cantidad de resultados. Los IDs conservan sus huecos normales y el listado continúa en orden descendente.

Se retiraron 45 casos sin referencia del perfil, junto con sus 16 pedidos completos y pagados y las cotizaciones, detalles, materiales, estados y pagos dependientes. La selección está limitada al historial reconocido, de origen simulado y revisión pendiente. No se descartan casos revisados ni registros manuales. Se conservan los 61 descriptores del perfil v2 y 12 casos por cada tipo nuevo: Velador, Cómoda y Vitrina. No se cambiaron el algoritmo, pesos, k=3 o umbral del 80 %.

| Casos por pedido | Pedidos | Casos |
|---|---:|---:|
| 1 | 21 | 21 |
| 2 | 14 | 28 |
| 3 | 10 | 30 |
| 4 | 2 | 8 |
| Total | 47 | 87 |

Todos los 47 pedidos están entregados. Hay 59 cotizaciones, 82 pagos, 36 clientes, 27 materiales y nueve tipos. Se conservan íntegros usuarios, contraseñas, permisos, clientes, existencias, movimientos, catálogo, ajustes y migraciones. Los contenidos no temporales de las diez tablas comerciales se compararon mediante el mapa inverso de IDs: únicamente se retiraron los registros seleccionados. Sin cambios de esquema: 31 tablas y 27 migraciones.

Calendario redistribuido sin concentración: 10 casos en 2020, 13 por año en 2021–2025 y 12 en 2026. Cotización inicial 16/03/2020, primer pedido 18/03/2020, último cierre 03/10/2026 y entrega final 05/10/2026. Máximo cuatro casos por mes. Se comprobaron fechas únicas de cotizaciones, aceptaciones, pagos y entregas previstas; los casos del mismo pedido comparten el cierre correspondiente. No se limita la fecha de nuevas altas normales.

CasosHistoricosSeeder::limitarA87 se ejecuta al final de DatabaseSeeder una sola vez por historial_87_casos_v1. Los lotes anteriores se mantienen para reconocer instalaciones existentes. Una instalación completa termina con 87 casos y repetir la carga no modifica datos; no se impone un límite a los casos que se generen posteriormente mediante el trabajo normal.

### Respaldo y comprobación

Se confirmó carpinteria_el_tajibo y el diagnóstico de 132 casos/63 pedidos/75 cotizaciones/114 pagos. Se realizó un respaldo completo y se restauró en una copia aislada. El ajuste y el navegador se probaron allí antes de aplicar. Inmediatamente antes de aplicar se comprobó que la base no había cambiado y se creó/restauró un nuevo respaldo. La aplicación usó mantenimiento temporal y transacción, con claves foráneas habilitadas.

Evidencia privada: storage/app/private/casos-87-historial-20261117173239_37356c/. SHA-256 de respaldo-aplicar.sql: 8d62a6a9b55c23101a55d0a6595839cb96184310a852f121e304632724398710. Incluye diagnóstico, ensayo, resultado local, auditoría de conservación y comprobaciones HTTP/similitud. La copia temporal se retiró; los respaldos permanecen. Aplicación fuera de mantenimiento.

- InstalacionSeedTest: 4 pruebas, 1589 aserciones aprobadas; instalación completa, fechas, relaciones y repetición sin cambios.
- CasosSeederTest, OrdenListadosTest y CasosHistoricosTest: 23 pruebas, 764 aserciones aprobadas; carga base, listado/filtros, autorización y ciclo de casos. Total: 27 pruebas y 2353 aserciones. No se ejecutó toda la suite.
- Pint aprobado para los siete PHP afectados.
- MySQL: conservación por contenido, integridad referencial, totales, calendario, saldo e idempotencia aprobados.
- Edge/Playwright en copia aislada: total 87, ocho páginas y tres filas en la última, pedidos con uno a cuatro casos, fichas, PDF, búsquedas de los tres tipos nuevos y móvil sin desbordamiento ni errores JavaScript. [Resultado](capturas/historial-87/resultado-browser.json).
- Base local: 157 respuestas HTTP 200, cuatro PDF válidos y redirección obligatoria de las dos cuentas con contraseña temporal; lecturas sin cambios en la base.
- Recomendador: consultas de control de los nueve tipos correctas. El 100 % en descriptores idénticos comprueba continuidad funcional, no una evaluación independiente de calidad.

Excel sincronizado: 47 pedidos, 579 líneas de costos, 82 pagos y 36 clientes. Totales: Bs 141.767,24 acordados, Bs 134.124,69 pagados y Bs 7.642,55 pendientes. Se mantuvo el saldo anterior al retirar exclusivamente pedidos completamente pagados. Fórmulas recalculadas y comparadas con la fuente, prueba temporal de cambios con restitución, archivo exportado sin errores de fórmula y cuatro vistas revisadas.

## Recarga vigente: 87 casos e IDs consecutivos — 17/11/2026

El usuario autorizó borrar y reconstruir la base y confirmó conservar su acceso actual de propietario. Se ejecutó migrate:fresh exclusivamente contra carpinteria_el_tajibo, seguido de PermisosSeeder, restauración privada de esa cuenta y DatabaseSeeder. Se ejecutaron las 27 migraciones; permanecen 31 tablas. Los datos anteriores se sustituyeron por la carga de los módulos vigentes. No se modificó el proyecto escolar.

Resultado: 87 casos con IDs del 1 al 87; próximo autoincremento 88. Hay 47 pedidos entregados, 59 cotizaciones, 82 pagos, 36 clientes, 27 materiales y nueve tipos. Las diez tablas comerciales relacionadas tienen IDs consecutivos desde 1. Usuario/contraseña/nombre/estado del propietario se conservaron por comparación del hash y sus atributos, sin mostrar ni almacenar contraseñas en texto. Los roles y permisos se recargaron y los dos maestros usan su contraseña temporal habitual. Las sesiones se reiniciaron.

HistorialCarpinteriaSeeder::finalizarIds se ejecuta al terminar DatabaseSeeder. Organiza las relaciones con claves foráneas activas y prepara los contadores una sola vez por historial_ids_correlativos_v1. MySQL ajusta AUTO_INCREMENT fuera de la transacción de relaciones; SQLite ajusta su secuencia dentro del entorno aislado de pruebas. Repetir los seeders no vuelve a renumerar ni modifica las nuevas altas. Se probó crear el caso 88 y repetir la carga conservándolo.

Se mantiene el calendario 16/03/2020–05/10/2026, con último cierre 03/10/2026: 10 casos en 2020, 13 por año en 2021–2025 y 12 en 2026. Clientes solo de La Paz y El Alto y correo opcional NULL. Permanecen los 61 descriptores del perfil v2 y los 12 casos de cada tipo nuevo, sin cambios en pesos, k o umbral. Los orígenes y estados internos de revisión no se transforman en validación humana.

### Comprobaciones de esta recarga

- Respaldo completo restaurado y comparado antes de cada reinicio. Ensayo completo en MySQL aislado y navegador aprobados antes de aplicar al origen.
- Carpeta privada: storage/app/private/recarga-87-20261117180045_1bc2fc/. SHA-256 del respaldo previo a aplicar: 7757ceb3c127bc08cfa1f35070acc9002f96771151944c578c49593a852f3d2b.
- Definiciones del esquema equivalentes. Solo se normaliza el orden físico de users.activo y el orden de dos índices de materiales al recrearlos desde las migraciones; no cambian columnas, restricciones o claves.
- MySQL: CHECK TABLE, todas las FK sin huérfanos, IDs consecutivos, contadores, cantidades, precios/saldos y repetición sin cambios aprobados.
- InstalacionSeedTest: 4 pruebas y 1612 aserciones. OrdenListadosTest: 4 pruebas y 46 aserciones. Total 8 pruebas y 1658 aserciones aprobadas; no se ejecutó toda la suite. Pint aprobado.
- Edge/Playwright: IDs 1–87, ocho páginas, tres filas en la última, fichas de pedidos con uno a cuatro casos, PDF y recomendaciones de Velador/Cómoda/Vitrina, móvil y ausencia de errores JavaScript aprobados. Evidencia: docs/capturas/recarga-87/resultado-browser.json.

Los documentos de cargas anteriores se conservan como historial. Esta sección sustituye su estado final.

Verificación posterior sobre la base local recargada: 157 respuestas HTTP 200, cuatro PDF válidos, cambio obligatorio de contraseña de las dos cuentas de maestros y consultas de control de los nueve tipos correctas, sin escrituras. Las dos copias temporales del ensayo se retiraron, conservando los respaldos; la base operativa permaneció intacta. Aplicación disponible, fuera de mantenimiento.

Excel Registros-El-Tajibo.xlsx sincronizado con IDs definitivos: 47 pedidos, 579 líneas de costos, 82 pagos y 36 clientes. Pedidos 1–47 y pagos 1–82. Fórmulas, importes, saldos y archivo XLSX guardado sin errores; vistas afectadas revisadas. Totales: Bs 141.767,24 acordados, Bs 134.124,69 pagados y Bs 7.642,55 pendientes.
