# Fase 6B — Reportes

Entrega del 10 de noviembre de 2026. Alcance confirmado: consulta en pantalla y **PDF imprimible**, exclusivamente para el propietario, incluyendo **todos los registros del sistema**.

## Funcionalidad

Desde Reportes en el menú administrativo se consultan cuatro apartados. Los datos cargados inicialmente también se incluyen, sin recalificarlos como operaciones verificadas ni modificar su procedencia.

| Apartado | Filtros | Contenido y alcance |
|---|---|---|
| Cotizaciones | Fecha de cotización y estado | Cantidad, precios aprobados, pendientes; costos estimados solo con permiso. Un precio pendiente se muestra Sin aprobar y un costo desconocido Pendiente. |
| Pedidos | Fecha de aceptación y estado | Cliente conservado al aceptar, entrega prevista y precio acordado. Sin confundir precios con cobros. |
| Pagos recibidos | Fecha del pago y efectivo/QR | Pagos registrados en el período, aunque el pedido sea anterior. Se usan los importes vigentes después de correcciones auditadas. |
| Saldos actuales | Con saldo, pagados o sin pagos | Precio acordado menos todos los pagos registrados, incluidos pedidos antiguos y entregados. No aplica filtro temporal ni representa un cierre histórico. |

El período inicial es desde el primer día del mes hasta hoy, con ambos extremos incluidos. Se puede cambiar para ver antecedentes. Los totales se calculan con Brick Math/BigDecimal sin sumas float. Las filas se ordenan por fecha e ID descendentes; saldos por ID descendente.

Pantalla: 20 filas por página, totales del conjunto filtrado completo y tabla desplazable en móvil. PDF: mismo filtro, todas sus filas sin limitar a la página actual, A4 horizontal, encabezados repetidos, pie y fecha de generación local, Bs. Hasta 500 filas por documento; un filtro mayor se rechaza indicando que debe acotarse, sin omitir filas. Los datos se vuelven a consultar al exportar; cambios realizados entre consulta e impresión pueden reflejarse en el PDF.

## Estructura y permisos

- app/Services/ReporteService.php: autorización, validación, consultas de solo lectura y totales.
- app/Http/Controllers/ReporteController.php: index, show, pdf; paginación y Dompdf ya instalado.
- resources/views/admin/reportes/index.blade.php y show.blade.php: navegación, filtros y resultados.
- resources/views/pdf/reporte.blade.php: impresión sin recursos remotos ni ejecución PHP/JS.
- routes/web.php: tres rutas GET explícitas /admin/reportes, /admin/reportes/{tipo} y /admin/reportes/{tipo}/pdf, con nombres admin.reportes.*.
- config/navegacion.php: habilita la opción Reportes existente.
- tests/Feature/ReportesTest.php y tests/browser/fase4b.cjs: pruebas aisladas.

Cuenta activa, rol propietario y reportes.administrativos, más panel.consultar en rutas. Cada tipo exige el permiso de consulta del módulo: cotizaciones.consultar o pedidos.consultar; pagos/saldos también pagos.consultar. costos.consultar controla la columna de costos en pantalla/PDF. Se conservan las asignaciones existentes, sin permisos nuevos o bypass del propietario. Maestro recibe denegación incluso con permiso administrativo asignado por error; inactivos son expulsados por el middleware existente.

## MySQL y datos

Verificación sobre carpinteria_el_tajibo local: **30 tablas, 26 migraciones, 53 claves foráneas**. Se compararon estructura, cantidades y SHA-256 de todas las filas antes/después de generar los cuatro reportes y PDF con los roles existentes: **sin diferencias ni escrituras**. No se ejecutaron migraciones, seeders, restauraciones o comandos destructivos. No se necesitó respaldo de actualización porque no existe modificación de esquema o datos.

En el corte verificado: 26 cotizaciones (15 con precio aprobado), 11 pedidos y cero pagos. Precios aprobados Bs 18103,95; precios acordados Bs 14538,20; pagos registrados Bs 0,00 y saldo actual Bs 14538,20. Los reportes no inventan cobros para los pedidos cargados anteriormente.

Evidencia privada de lectura y artefactos de impresión: storage/app/private/fase6b-2026-11-10/verificacion-mysql.json y mysql-antes.json. Este directorio también conserva copias previas de rutas, navegación, prueba de navegador, AGENTS y REQUISITOS, además de los logs. No ejecutar scripts privados como instaladores.

## Verificación

Suite completa: **269 pruebas/2581 aserciones, 363,60 s**, aprobadas. Pruebas específicas finales: **10 pruebas/118 aserciones, 13,13 s**, aprobadas. SQLite en memoria con destino comprobado; incluye importes, fechas límite, filtros, cliente congelado, pagos de pedidos antiguos, permisos revocados y maestro con permiso incompatible, paginación, límite de 500 y ausencia de escrituras en todas las tablas.

Navegador Edge/Playwright: **133 comprobaciones aprobadas, 111 capturas acumuladas**, sin errores JS. Incluye los módulos anteriores y los cuatro reportes, filtros, PDF, efectivo/QR, saldo actual, período vacío, rechazo de fechas para saldos y maestro sin menú/acceso. Escritorio 1440, tableta 1024 y móvil 390 px, claro/oscuro.

MySQL: controladores, Blade, servicio y generación de PDF en solo lectura con propietario y maestro. La navegación autenticada y operaciones de escritura se prueban en SQLite temporal, sin agregar pagos al MySQL local. No se afirma haber realizado una sesión HTTP autenticada de producción.

Se extrajeron textos y renderizaron los PDF con pypdf/Poppler. Revisión de todas las páginas de un documento de 65 filas (cuatro páginas) y de un reporte vacío (una página): filas completas, encabezados repetidos, pie en cada página, acentos y totales legibles. Las 65 filas son solo datos de impresión en memoria, sin inserciones en la base.

- [Resultado de navegador](capturas/fase6b/resultado-browser.json).
- [Menú de reportes](capturas/fase6b/reportes-index-1440.png).
- [Pedidos](capturas/fase6b/reporte-pedidos-1440.png).
- [Pagos](capturas/fase6b/reporte-pagos-1440.png).
- [Saldos en móvil](capturas/fase6b/reporte-saldos-390.png).
- [Tema oscuro](capturas/fase6b/reporte-saldos-oscuro-390.png).

## Límites

Reportes internos de lo registrado; sin caja, gastos, ganancias contables, facturas fiscales, Excel, cobro QR automático o reconstrucción histórica de correcciones. No modifica precios, estados, pagos, existencias o casos. No cambia el algoritmo de similitud ni constituye evaluación de precisión con personal real. No se inició otra fase y el sistema escolar permanece intacto.
