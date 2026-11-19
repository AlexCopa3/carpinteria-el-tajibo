# Historial de la carpintería

Estado de consumos actualizado el 17/11/2026: se mantienen 87 casos; cantidades y costos corregidos mediante referencias documentadas. Véase [Consumos de materiales](CONSUMOS-MATERIALES.md). Los importes/consumos de las secciones anteriores conservan el historial de cargas.

Actualización: 17/11/2026. Estado vigente: 87 casos con IDs del 1 al 87 en 47 pedidos entregados, 59 cotizaciones y 82 pagos; 36 clientes, 27 materiales y nueve tipos de muebles. Fechas desde 16/03/2020 hasta 05/10/2026. Clientes solo de La Paz y El Alto, con correo opcional vacío. Reparto anual: 10 casos en 2020, 13 por año de 2021 a 2025 y 12 en 2026; máximo cuatro casos por mes. Véase [ajuste aplicado](AMPLIACION-CASOS-HISTORICOS.md#cantidad-vigente-87-casos). Las secciones siguientes conservan el detalle de las cargas anteriores.

## Carga anterior de 87 casos

| Entidad | Cantidad |
|---|---:|
| Clientes | 36 |
| Cotizaciones | 59 |
| Pedidos | 47 |
| Casos históricos | 87 |
| Pagos | 82 |
| Materiales | 26 |
| Usuarios | 3 |

Los pedidos se distribuyen en 4 aceptados, 4 en fabricación, 3 terminados recientes y 36 entregados. Los casos conservan su organización en pedidos con una a cuatro líneas: 12 pedidos con uno, 12 con dos, 9 con tres y 6 con cuatro. No se cambió el contenido de sus características, cantidades, materiales, costos o precios.

Casos por año: 2020: 8; 2021: 14; 2022: 13; 2023: 13; 2024: 14; 2025: 9; 2026: 16.

HistorialCarpinteriaSeeder organiza las fechas de cotizaciones, pedidos, estados, uso de materiales, validación, casos y pagos. Se completan anticipos y saldos mediante PagoService y las entregas mediante CierrePedidoService. El lote de auditoría impide duplicar o sobrescribir estos registros al repetir DatabaseSeeder. El seeder general está protegido para destinos locales/pruebas y no carga el historial en producción.

Los contactos conservan nombres y direcciones de contexto boliviano, teléfonos de ocho dígitos con prefijo +591 y correos en el dominio reservado correo.test. Los datos de acceso de los usuarios no se modificaron. Las fechas normales de nuevas cotizaciones siguen siendo automáticas; la asignación histórica corresponde exclusivamente a la carga autorizada.

La presentación utiliza nombres normales, sin avisos de carga inicial en las fichas. Internamente se conserva la procedencia simulada y la revisión humana pendiente; estos registros no acreditan documentos auténticos, fabricación real ni evaluación por el personal. Se mantienen las claves históricas de lotes y auditoría y no se reescriben eventos anteriores.

## Aplicación y respaldo

Se verificó exclusivamente carpinteria_el_tajibo: 31 tablas y 27 migraciones. No se crearon ni eliminaron tablas/columnas y no se reiniciaron usuarios o permisos. Cuentas, contraseñas, roles, permisos, existencias, movimientos, materiales, ajustes y migraciones se compararon íntegramente y permanecen conservados. Los importes de cotizaciones/pedidos y las instantáneas de materiales/costos/precios de los casos no cambiaron.

Evidencia privada: storage/app/private/historial-20261117120323_092a86/. Respaldo completo de ensayo y respaldo nuevo previo a la aplicación, ambos restaurados y comparados con el origen. SHA-256 del respaldo previo: f0674413466a7ff654c19529c541719bddb50766e108ef3dae05b4ec5d7b24c6.

La aplicación local se realizó después del ensayo y navegador, con mantenimiento temporal y transacción. Se verificaron relaciones, cronologías, fechas de uso y casos, ausencia de sobrepagos, saldo cero de trabajos cerrados, conservación de contenidos y repetición idempotente. Las tres copias temporales creadas durante este trabajo se retiraron; los respaldos permanecen. Aplicación fuera de mantenimiento.

## Excel

Registros-El-Tajibo.xlsx se generó leyendo la base local actualizada, con cuatro hojas: Pedidos, Costos, Pagos y Clientes. Contiene los mismos 47 pedidos, 620 líneas de costos, 82 pagos y 36 clientes. Fechas numéricas con formato día/mes/año, listas de estados/medios y fórmulas de subtotales, pagos acumulados y saldo.

Totales coincidentes con MySQL: precio acordado Bs 147.779,18; pagos Bs 140.136,63; saldo Bs 7.642,55. El costo de materiales usa cantidades utilizadas cuando existen y planificadas cuando están pendientes; mano de obra y otros costos mantienen su referencia cotizada. Un costo desconocido queda Pendiente. El archivo es una exportación editable, sin conexión automática con la base. No contiene usuarios, contraseñas o tokens.

Se verificaron los nombres completos de muebles, cada saldo/pago/subtotal, cambios temporales de importes con restitución, ausencia de errores de fórmulas y las cuatro vistas renderizadas. También se comprobaron las cachés de totales en el XLSX exportado y sus encabezados.

## Pruebas

- InstalacionSeedTest: 4 pruebas y 655 aserciones aprobadas, 41,98 s, SQLite aislado; instalación completa, cronología/saldos, repetición sin cambios, producción/desactivación y alta segura del propietario.
- Pint aprobado para los seis PHP de la carga.
- MySQL: respaldo restaurado, integridad referencial, conservación de tablas protegidas y contenidos, cronología e idempotencia comprobadas.
- Edge/Playwright en MySQL aislado: listados, pedidos con uno a cuatro casos, fichas/enlaces, contactos y pagos, PDF y móvil sin desbordamiento; sin errores JavaScript. [Resultado](capturas/historial-2020-2026/resultado-browser.json).
- Base local: 157 respuestas HTTP 200, cuatro PDF válidos y dos redirecciones obligatorias de maestros para cambiar contraseña, con datos intactos tras las lecturas.
- Recomendador: tres resultados para la consulta de control, sin cambios de perfil, pesos, k ni umbral.

La primera revisión de instalación detectó una comparación de fecha/hora entre SQLite y una fecha sin hora; se corrigió normalizando la fecha. El primer auxiliar de ensayo se ajustó para tablas pivote sin columna id. Se repitieron las comprobaciones satisfactoriamente antes de aplicar MySQL local. No se ejecutó la suite completa.

## IDs del historial por cronología — 17/11/2026

Se corrigió la numeración de la carga autorizada: los IDs bajos representan los trabajos antiguos y los altos los recientes. Los listados siguen ordenados por ID descendente. Pedido 1: 29/01/2020; pedido 48: 15/11/2026. No se cambiaron fechas para hacer parecer antiguos los pedidos abiertos.

HistorialCarpinteriaSeeder ordena una sola vez cotizaciones, pedidos, casos y pagos por su fecha, y concilia sus detalles, materiales, estados y vínculos. Conserva el conjunto de IDs existente de cada tabla, incluidos los huecos de registros retirados, sin reutilizarlos. Las altas normales continúan utilizando el autoincremento. No hay migraciones ni cambios de esquema.

La operación se ejecutó transaccionalmente con claves foráneas activas. Se comparó cada registro antes/después invirtiendo el mapa de IDs: fechas, cantidades, materiales, precios, costos, estados, UUID y versiones permanecen idénticos. Se conservaron íntegros clientes, usuarios/contraseñas, permisos, catálogo, existencias, movimientos, ajustes y migraciones. Las auditorías mantienen sus instantáneas históricas; se conciliaron los enlaces a registros y los metadatos operativos de seeders. El evento historial_ordenado conserva los mapas y los metadatos previos para explicar la correspondencia. La repetición de DatabaseSeeder no modifica datos.

Se mantuvieron 59 cotizaciones, 47 pedidos, 87 casos y 82 pagos, y la distribución de casos/estados anterior. Los 25 descriptores del perfil de similitud mantienen lote/posición, características y parámetros. Se verificaron 25 consultas al recomendador después de aplicar, sin escrituras ni perfil inválido.

Respaldo nuevo completo, restauración comparada, ensayo aislado y navegador antes de la aplicación local. Evidencia privada: storage/app/private/orden-historial-20261117143314_1ab362/. SHA-256 de respaldo-aplicar.sql: 056fd1a1d5faf6dc72448d51fbb7f47b183664c4ad6c5d9fb8d9746a9c190666.

Verificaciones recién ejecutadas: InstalacionSeedTest, OrdenListadosTest y SimilitudTest, 42 pruebas/943 aserciones aprobadas; Pint aprobado. Edge/Playwright sobre MySQL aislado: cronología por ID, navegación de pedidos con 1–4 casos, pagos, clientes, PDF y móvil, sin errores JavaScript ni desbordamiento. Capturas y resultado en docs/capturas/historial-ids/. En la base local: 157 respuestas HTTP 200, cuatro PDF válidos y dos redirecciones de maestros para cambiar contraseña, sin modificar los datos. No se ejecutó la suite completa.

El Excel se actualizó desde MySQL con la misma numeración: 47 pedidos, 620 líneas de costos, 82 pagos y 36 clientes. Fórmulas, saldos, subtotales y totales comprobados, cuatro hojas renderizadas y revisadas. Totales conservados: Bs 147.779,18 acordado; Bs 140.136,63 cobrado; Bs 7.642,55 pendiente. No es una conexión en vivo.

## Corte del historial al 5 de octubre de 2026 — 17/11/2026

Por solicitud del usuario, el historial comercial cargado llega como máximo al 05/10/2026. Se conservaron intactos los años 2020–2025 y se comprimió cronológicamente el tramo de 2026; las entregas previstas posteriores también quedaron dentro del corte, sin quedar antes de la aceptación. El último pedido por ID, 48, está fechado 03/10/2026; los últimos cierres/casos llegan al 05/10/2026. Se mantuvieron IDs, relaciones, estados, cantidades, características, importes y distribución de los 87 casos.

HistorialCarpinteriaSeeder aplica una sola vez historial_hasta_octubre_2026_v1. Ajusta fechas comerciales y timestamps de la carga reconocida de cotizaciones, pedidos, sus detalles/materiales/estados, casos y pagos. Las altas posteriores mantienen su fecha real automática; no se bloquea el calendario del sistema. Usuarios, contraseñas, permisos, clientes, catálogo, existencias, movimientos, ajustes y migraciones se compararon íntegramente y no cambiaron. Las fechas técnicas de cuentas y auditorías conservan el instante real: esta solicitud no falsifica las fechas de mantenimiento o de acceso. La auditoría del ajuste conserva las fechas anteriores y el período previo.

Respaldo completo nuevo restaurado y comparado, ensayo MySQL aislado y navegador antes de aplicar; sin cambios de esquema. Evidencia privada: storage/app/private/periodo-historial-20261117144531_92c613/. SHA-256 de respaldo-aplicar.sql: 7870bc431cf2da676897b178e383a9e4fefd70592a8c3aa9be962412053e93ce.

Verificación recién ejecutada: InstalacionSeedTest y OrdenListadosTest, 8 pruebas y 793 aserciones aprobadas, 62,16 s; Pint aprobado. Se comprobaron todas las fechas comerciales/timestamps del alcance contra 05/10/2026 23:59:59, cronología y claves foráneas, conservación de contenido no temporal, saldos e idempotencia de DatabaseSeeder. Edge/Playwright: fechas, listados, relaciones de pedidos con 1–4 casos, clientes, pagos, PDF y móvil, sin errores JavaScript ni desbordamiento; capturas/resultado en docs/capturas/historial-octubre/. Base local: 157 respuestas HTTP 200, cuatro PDF válidos y dos redirecciones obligatorias al perfil de maestros, sin escrituras durante la comprobación. No se ejecutó la suite completa.

Excel actualizado desde MySQL con el mismo corte: 47 pedidos, 620 líneas de costos, 82 pagos y 36 clientes. Fórmulas y totales verificados, sin errores, y cuatro hojas renderizadas. Los importes totales permanecen Bs 147.779,18 acordado, Bs 140.136,63 cobrado y Bs 7.642,55 pendiente. La copia temporal de ensayo fue retirada tras verificar la base operativa; respaldos conservados.

## IDs después de la recarga autorizada

El 17/11/2026 se reconstruyó la base mediante las 27 migraciones y se recargaron los seeders. Los casos tienen IDs del 1 al 87 y el siguiente es 88; pedidos del 1 al 47, cotizaciones del 1 al 59 y pagos del 1 al 82. Se conservó el acceso del propietario. Las secciones anteriores que mencionan huecos de IDs documentan el estado previo. Véase la sección final de INFORME-RECARGA-DATOS.md.
