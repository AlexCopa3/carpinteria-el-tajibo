# Verificación vigente

> Estado vigente: [Reportes 6B](FASE-6B.md), 10/11/2026. Suite completa 269/2581 y navegador 133 comprobaciones aprobadas. Los apartados de entregas anteriores conservan sus resultados originales.

## Cierre vigente 6A — Pagos, 10 de noviembre de 2026

[Entrega, respaldo y aplicación](FASE-6A.md).

- **259 pruebas PHP/2463 aserciones, 331,28 s**, aprobadas con SQLite aislado; incluye 13 pruebas de pagos y regresión anterior. Expectativas de 26 migraciones y reversión de dependencias actualizadas.
- **117 comprobaciones Edge/Playwright**, aprobadas sin errores JavaScript, SQLite temporal y 95 capturas acumuladas. Crear/editar/Ver, efectivo/QR, duplicados, versiones, sobrepago, saldo cero, entrega con saldo, privacidad y 1440/1024/390/oscuro.
- Respaldo nuevo de 430.886 bytes restaurado idénticamente; ensayo MySQL concurrente de cinco grupos aprobado. Se detectaron/corrigieron lecturas antiguas bajo InnoDB antes de aplicar. Solo un cobro ante sobrepago simultáneo, UUID simultáneo único y versión protegida.
- MySQL aplicado: **30 tablas/26 migraciones/53 FK**, solo tabla pagos y su registro de migración; cero pagos ficticios, datos previos íntegros, sin cambios de permisos. Estructura igual a ensayo y 25 migraciones anteriores intactas.
- Verificación MySQL posterior de propietario/maestro, controlador/Blade/servicio: solo lectura, sin sesión HTTP autenticada. El servidor 127.0.0.1:8000 no estaba comprobable; no se inició. Mantenimiento retirado. HTTP completo comprobado en la base SQLite del navegador.
- 88 rutas (82 previas más seis de pagos). Sin dependencias/JS/CSS nuevos; no fue necesario compilar recursos.
- Evidencias privadas: storage/app/private/fase6a-2026-11-10; [resultado de navegador](capturas/fase6a/resultado-browser.json).

Los siguientes apartados conservan los cortes históricos anteriores.

## Cierre funcional 5C — 10 de noviembre de 2026

[Entrega y reglas económicas](FASE-5C.md).

- **246 pruebas PHP/2367 aserciones, 356,30 s**, aprobadas en SQLite aislado. Regresión de todas las fases anteriores; histórico ponderado, comparación para igual cantidad, pendientes independientes y costos fijos.
- **108 comprobaciones Edge/Playwright**, aprobadas con SQLite temporal, sin errores JavaScript; 86 capturas acumuladas. Incluye dos escenarios económicos, aviso de lote, aplicación/edición, guardado sin aprobación, escritorio/móvil y oscuro.
- Primera comprobación económica: cuatro pruebas/26 aserciones, aprobadas.
- MySQL de solo lectura: 27 detalles, ficha/propuestas por propietario/maestro; datos y estructura íntegros idénticos, cero escrituras operativas. Sin autenticación HTTP MySQL.
- Las 25 migraciones y el perfil de similitud conservan sus hashes; 29 tablas/51 FK y rutas existentes intactas. Sin dependencias, compilación adicional o cambios JS/CSS: la entrega modifica servicio y Blade.
- Evidencias: storage/app/private/cierre-5c-2026-11-10 y [resultado de navegador](capturas/fase5c/resultado-browser.json).

Los apartados siguientes conservan los resultados históricos de las entregas anteriores.

## Casos históricos unificados — cierre vigente

9 de noviembre de 2026. [Flujo y archivos actualizados](FLUJO-COTIZACION-CON-CASOS.md).

- Suite completa: **243 pruebas/2347 aserciones, 304,17 s**, aprobadas en SQLite aislado. Incluye la regresión de compatibilidad de la auditoría/lote anterior, sin duplicación ni reescritura de registros.
- Primera comprobación dirigida: 41 pruebas/215 aserciones; la suite completa incorpora después la prueba adicional de compatibilidad.
- Edge/Playwright: **107 comprobaciones** aprobadas y 86 capturas acumuladas, sin errores JavaScript; búsqueda única, principal/alternativas, confirmación, edición y guardado sin aprobar, 1440/1024/390 y oscuro.
- Vite build aprobado; sin dependencias nuevas. Perfil único con mismos pesos, descriptores/rangos/lote y hash actualizado por el cambio de nombre/versión.
- MySQL: 29 tablas/25 migraciones/51 FK, datos y estructura idénticos antes/después, cero escrituras operativas. 27 detalles consultados y ficha/propuestas por propietario y maestro; sin autenticación HTTP MySQL. Las escrituras del navegador y PHP fueron aisladas.
- 82 rutas conservadas; no quedan modos en controlador, servicios, configuración o JavaScript. Retirados perfil y documento de nombres anteriores; enlaces documentales comprobados sin roturas.
- Evidencias: storage/app/private/unificacion-casos-2026-11-09 y [resultado de navegador](capturas/casos-historicos-unificados/resultado-browser.json).

Los apartados siguientes documentan cortes históricos y quedan sustituidos para el estado actual por este cierre.

## Actualización: búsqueda y selección desde el formulario

9 de noviembre de 2026. [Informe del flujo](FLUJO-COTIZACION-CON-CASOS.md).

- Suite completa: **241 pruebas/2333 aserciones, 292,87 s**, aprobadas en SQLite aislado.
- Prueba adicional posterior de unidades y costos fijos: **1 prueba/6 aserciones**, aprobada. La suite completa anterior no incluía esta última prueba.
- Comprobación específica final: **31 pruebas/150 aserciones, 60,50 s**, aprobadas; incluye la prueba adicional de unidades y todos los escenarios de similitud.
- Navegador Edge/Playwright: **107 comprobaciones**, aprobadas; 86 capturas del recorrido acumulado, seis nuevas del flujo de formulario. Sin errores JavaScript.
- Vite build aprobado; JS actualizado en public/build y manifest vigente.
- MySQL datos/estructura idénticos al diagnóstico: cero escrituras operativas. 25 migraciones intactas/29 tablas/51 FK; 82 rutas, dos POST nuevos y GET previa conservada.
- Evidencia: storage/app/private/similitud-formulario-2026-11-09 y [resultado de navegador](capturas/similitud-formulario/resultado-browser.json).

Los resultados siguientes son el corte histórico de la entrega 5B original.

Cierre de fase 5B, 9 de noviembre de 2026. [Informe y límites](FASE-5B.md).

## Resultados

- **232 pruebas PHP/2275 aserciones, 294,63 s**, aprobadas, SQLite aislado; regresión previa y 21 pruebas nuevas de similitud/comparación.
- Tras último ajuste responsive Blade: **21 pruebas/86 aserciones, 46,83 s**, aprobadas.
- **102 comprobaciones Edge/Playwright**, SQLite temporal, aprobadas sin errores JavaScript: fases previas/SweetAlert2, normal vacío, demostración explícita, ranking/diferencias, permisos de maestro, parámetros rechazados, fuera de cobertura, 1440/1024/390 px y oscuro. Tablas nuevas dentro del contenedor.
- MySQL operativo: 29 tablas/25 migraciones/51 FK, datos/estructura idénticos al diagnóstico inicial de 5B, cero escrituras/migraciones/seeders, hashes de las 25 migraciones conservados.
- Servicio experimental sobre 27 detalles existentes: 7 fuera de cobertura, 12 con referencias, 8 sin antecedentes. No mide precisión comercial.
- Cuatro renderizados de controlador/Blade en MySQL, propietario/maestro × normal/demostración; sin sesión HTTP autenticada sobre MySQL. El navegador usa SQLite aislado.
- 80 entradas de rutas, conservando 79 anteriores; una GET nueva. Sin nuevas dependencias o cambios de recursos compilados.

[Resultado navegador](capturas/fase5b/resultado-browser.json), seis capturas específicas y logs/JSON en storage/app/private/fase5b-2026-11-09/implementacion. Recorrido acumulado: 80 capturas. Ensayos/restauraciones y concurrencia MySQL previos se documentan en sus entregas, sin presentarlos como ejecuciones nuevas de 5B.

## Ejecución y límites

- php artisan test: suite aislada.
- php artisan test --filter='SimilitudTest|ComparadorSimilitudTest': comprobación específica.
- npm run test:browser: tests/browser/fase4b.cjs ampliado hasta 5B, Edge y SQLite temporal. preparar-casos.php conserva comprobación estricta de ruta/entorno.
- TAJIBO_PHP selecciona PHP; TAJIBO_CAPTURAS selecciona salida, en esta entrega docs/capturas/fase5b. Predeterminada: docs/capturas/actual.
- npm run build no fue necesario: recursos existentes, sin cambios JS/CSS o dependencias.
- Escenarios test:browser:usuarios/auth/ajustes conservados; no ejecutados por separado en este cierre.

Escrituras de prueba aisladas. La consulta no modifica MySQL ni registra búsquedas en auditorías. Fixtures/datos simulados no acreditan precisión real, revisión humana, gastos reales o rendimiento productivo. Perfil normal pendiente; demostración experimental local/testing. Aplicación 5C pendiente de autorización.

## Entrega vigente — Reportes 6B, 10/11/2026

Suite completa final: **269 pruebas/2581 aserciones, 363,60 s**, aprobada. Específicas ReportesTest: **10/118, 13,13 s**, aprobadas.

[Resultados y límites actuales](FASE-6B.md). ReportesTest verifica filtros, fechas inclusivas, sumas exactas, ausencia de escrituras, permisos en HTML/PDF/servicio y límite de exportación sin truncar. Edge/Playwright acumulado: 133 comprobaciones, 111 capturas y cero errores JS sobre SQLite temporal. MySQL solo lectura: cuatro reportes/PDF con roles existentes, 30 tablas/26 migraciones/53 FK sin diferencias de filas o estructura. PDF multipágina renderizado e inspeccionado con datos en memoria. Las cifras anteriores corresponden a sus entregas originales.

## Delegación individual — 11 de noviembre de 2026

PermisosUsuarioTest: 8 pruebas/101 aserciones aprobadas. Recorrido general: 286 pruebas, 284 aprobadas y 2 expectativas antiguas de FaseDosTest desactualizadas (permitir otro propietario y mostrar el aviso de contraseña inicial). Se actualizaron conforme a decisiones ya aprobadas y se repitió FaseDosTest: 18 pruebas/171 aserciones aprobadas. No quedaron fallos pendientes de esa comprobación; no se repitió la corrida completa después de actualizar únicamente esas expectativas.

Navegador: 57 comprobaciones y 40 capturas aprobadas entre delegación individual, Usuarios/perfil y roles. Incluye concesión, cliente registrado por maestro, acceso a alta de material, revocación, consultas preservadas, rechazo de pagos/usuarios/autodelegación y adaptación 1440/1024/390 px y oscuro móvil. Evidencia en docs/capturas/permisos-individuales-2026-11-11 y docs/capturas/permisos-individuales-regresion-2026-11-11.

Todas las pruebas utilizan SQLite aislada. No se ejecutaron migraciones, seeders ni escrituras sobre MySQL de la aplicación. No se modificó la referencia escolar. Los cuatro permisos ya existían; se reutilizan las relaciones de Spatie.

## Permisos completos — 13/11/2026

Implementación aplicada y verificada el 13 de noviembre de 2026, tras aprobación del usuario, respaldo completo restaurado y ensayo aislado. Permisos generales editables para todos los módulos; excepciones individuales Según su rol / Permitir / Denegar con prioridad sobre Spatie. El propietario conserva las cuatro capacidades de recuperación documentadas. Base carpinteria_el_tajibo: 31 tablas, 27 migraciones, 55 FK y 48 permisos; propietario 48 y maestro sus mismos 15. Sin delegaciones automáticas al maestro, cambios de contraseñas ni pérdidas de registros. Informe: docs/INFORME-PERMISOS-GENERALES-INDIVIDUALES.md. Esta decisión sustituye las restricciones históricas por rol; conservar las validaciones del negocio y no avanzar a fases nuevas.

Suite final: 293 pruebas y 2920 aserciones aprobadas; 25 grupos de comprobación en Edge con datos temporales, sin errores JavaScript. Nueve comprobaciones MySQL en clon con escrituras revertidas, más renderizado local CLI de solo lectura. Las pruebas no acreditan precisión académica del recomendador ni validación humana.

## Bloqueo de Usuarios — 14/11/2026

Cambio limitado a User, PermisosRolService/PermisosUsuarioService y las dos vistas de permisos. Sin migraciones ni seeders. Específicas: 53 pruebas/655 aserciones aprobadas (roles, permisos individuales, autenticación y usuarios/perfil). Incluyen rechazo por HTTP, autorizaciones antiguas de rol/directas/individuales, conservación del propietario y bloqueo del maestro aun con todos los permisos administrativos asignados. Navegador: 26 grupos y 18 capturas; Usuarios bloqueado, otros módulos editables, guardado/restablecimiento correctos.

MySQL verificado solo lectura: 31 tablas/27 migraciones/dos usuarios. Los cinco permisos reservados y sus casillas se verifican en los dos roles y las dos vistas mediante renderizado CLI autenticado, sin login HTTP real. Huellas de todos los registros idénticas antes/después de la comprobación. Evidencia privada: storage/app/private/usuarios-permisos-bloqueados-2026-11-14/verificacion-mysql.json y estado-mysql.json. Respaldo de archivos anteriores en codigo-previo del mismo directorio. Suite completa final: **295 pruebas y 2990 aserciones aprobadas, 448,62 s**. Resultado conservado en pruebas-completas.txt del mismo directorio privado.


## Cotizaciones: revisión visual — 14/11/2026

FaseCuatroATest, PermisosCotizacionTest y SimilitudTest: **76 pruebas y 541 aserciones aprobadas**, 136,67 s. Edge/Playwright: **28 comprobaciones y 21 capturas aprobadas**, sin errores JavaScript; pruebas en 1440/1024/390 px y tema oscuro móvil. Alta con secciones opcionales, añadir/quitar mueble, edición, aprobación, protección posterior, PDF, búsqueda/limpieza y consulta del maestro verificados. Inspección visual de capturas de listado y ficha en escritorio/móvil.

Comando: php artisan test --compact --filter='FaseCuatroATest|PermisosCotizacionTest|SimilitudTest'. Navegador: node tests/browser/cotizaciones.cjs. SQLite temporal aislada, sin consultas ni escrituras en MySQL local. La preparación del alta se realiza por HTTP en esa base; la actualización y aprobación se ejercitan mediante botones del navegador. No se repitió la suite completa, pues el cambio se limita a presentación de cuatro vistas. No fue necesario recompilar recursos: se reutilizan componentes y estilos existentes.

Respaldo de las cuatro vistas y logs en storage/app/private/revision-cotizaciones-2026-11-14. Capturas y resultado-browser.json en docs/capturas/cotizaciones-revision-2026-11-14.


## PDF de cotización — 14/11/2026

FaseCuatroATest y PermisosCotizacionTest: **42 pruebas/367 aserciones aprobadas**, 48,61 s. Se conserva autorización, bloqueo de cotizaciones no aprobadas, instantáneas históricas y ausencia de costos internos en el PDF. Tres variantes renderizadas con Dompdf y Poppler: dos muebles con logo y condiciones (una página), diez muebles y cliente (tres páginas), datos mínimos sin logo/contacto/cliente (una página). Páginas inspeccionadas visualmente; pdfplumber verificó límites del texto, A4, encabezados repetidos, numeración, los diez muebles y total único. No se hicieron consultas SQL al generar vistas previas: conexión SQLite en memoria con bloqueo de ejecución de consultas.

Respaldo anterior, generador, PDF/PNG de verificación, resultados JSON y log en storage/app/private/revision-pdf-cotizacion-2026-11-14. Solo se modificó la plantilla PDF y esta documentación; no se tocaron MySQL, vendor ni servicios.


Corrección posterior del estilo PDF, 14/11/2026: nuevamente aprobadas las dos pruebas específicas de PDF de FaseCuatroATest (30 aserciones), 4,53 s. Las tres variantes se generaron y renderizaron otra vez: 1/3/1 páginas, iconos visibles y encabezados claros. Verificación de límites de texto, integridad de diez muebles, total único y datos administrativos excluidos aprobada (verificacion-pdf-estilo-sistema.json). No se repitieron las otras 40 pruebas porque la corrección solo afecta CSS y marcado de presentación. No se consulta ni modifica MySQL; el servicio PDF sigue intacto.


Crear cotización, recuadros (14/11/2026): npm run build aprobado. FaseCuatroATest/PermisosCotizacionTest: **42 pruebas/367 aserciones**, 47,19 s. Navegador: **33 comprobaciones/24 capturas**, sin errores JS y sin desborde en 1440/1024/390 px, incluido tema oscuro. Se verifican fila compartida de acabado/componentes, apertura de costos, selección de material principal y cálculo de 2 × Bs 40 + Bs 20 + Bs 5 = Bs 105; además permanecen las pruebas de edición, aprobación, PDF y consulta del maestro. SQLite temporal aislada; sin escrituras MySQL. Respaldo y logs en storage/app/private/revision-formulario-cotizacion-2026-11-14. Capturas en docs/capturas/cotizaciones-revision-2026-11-14.


## Catálogo de tipos de muebles — 14/11/2026

Regresión TiposMuebleTest, FaseCuatroATest, PermisosCotizacionTest, CategoriasTest y SimilitudTest: **89 pruebas/680 aserciones aprobadas**, 140,73 s. Incluye nueve pruebas nuevas: registro/validaciones, auditoría, claves conservadas, seeder idempotente sobre nombre/estado editados, consulta del maestro, administración delegada y denegación individual, búsqueda/paginación, ausencia de DELETE, protección de cotizaciones aprobadas y acceso de invitados/inactivos. La primera ejecución individual detectó una expectativa incorrecta del test sobre cuentas inactivas (el middleware existente redirige a login); se ajustó la prueba, no el comportamiento.

Edge/Playwright: **20 comprobaciones/18 capturas aprobadas**, cero errores JS. Pruebas de listado/crear/ver/editar en 1440/1024/390 px y oscuro móvil, búsqueda, registro/desactivación/reactivación, consulta del maestro y rechazo de administración por URL. Se guardó una cotización manual con el tipo nuevo Cómoda y costo Bs 105. Datos exclusivamente en SQLite temporal. Diez archivos PHP/Blade con sintaxis válida; seis rutas explícitas comprobadas. No fue necesario compilar recursos porque se reutilizan los existentes.

MySQL real comprobado solo lectura: base carpinteria_el_tajibo, **31 tablas/27 migraciones/seis tipos**. Vistas renderizadas por CLI con roles existentes: propietario consulta/administra y maestro solo consulta. No fue un login HTTP en MySQL; las pruebas de interacción se ejecutaron en SQLite. Huellas de todas las tablas iguales antes/después; SQL no lector bloqueado por el verificador. No se ejecutaron migraciones, seeders, respaldos/restauraciones ni escrituras en MySQL. La primera consulta de inspección abarcó nombres de otras bases accesibles y falló al buscar cache dentro de carpinteria_el_tajibo; se corrigió acotando getTables al esquema autorizado, sin tocar esas tablas ni datos.

Evidencia y respaldo de código: storage/app/private/tipos-muebles-2026-11-14 (pruebas-regresion.txt, pruebas-navegador.txt, verificacion-mysql.json, estado-mysql.json y codigo-previo). Capturas en docs/capturas/tipos-mueble-2026-11-14. No se ha repetido la suite completa; se ejecutaron los módulos afectados y relacionados.


## Simplificación de cotizaciones — 15/11/2026

FaseCuatroATest, PermisosCotizacionTest y SimilitudTest: **78 pruebas/570 aserciones aprobadas**, 146,01 s. Dos pruebas nuevas verifican ausencia de campos, conservación de ajustes, motivo, actor/fecha y precio propuesto cuando no se revisan valores; recálculo al cambiar mano de obra o cantidad; historial anterior en auditoría. Se conservan las validaciones de formatos anteriores y sus pruebas de compatibilidad. No se eliminan columnas, migraciones ni datos de MySQL.

Navegador: recorrido nuevo cotizacion-simplificada.cjs **9 comprobaciones/6 capturas** y regresión cotizaciones.cjs **33 comprobaciones/24 capturas**, ambos aprobados sin errores JS. Incluye guardar una edición antigua sin perder el ajuste, modificar mano de obra con cálculo coherente en cliente/servidor, buscar/aplicar un caso y registrar una cotización sin los campos eliminados; escritorio 1440/1024/390 px y temas del recorrido general. Preparación y escrituras solo en SQLite temporal. Primera ejecución del nuevo recorrido requería confirmar el diálogo de sustitución de caso; se corrigió el test y se repitió con aprobación. npm run build y sintaxis PHP/JS aprobados. El recorrido histórico fase4b.cjs se ajustó en las referencias al apartado retirado, pero no se ejecutó completo en esta entrega.

Evidencia y respaldos en storage/app/private/simplificacion-cotizacion-2026-11-15; capturas de compatibilidad/casos en docs/capturas/cotizacion-simplificada-2026-11-15 y regresión en docs/capturas/cotizaciones-revision-2026-11-14. No se ha repetido la suite completa; no se modifica MySQL ni la referencia escolar.


## Restauración del apartado opcional — 15/11/2026

Restauradas las versiones previas de _detalle.blade.php, cotizaciones.js y CotizacionService.php, junto con las expectativas originales correspondientes. La prueba exclusiva de la simplificación se archiva en storage/app/private/restauracion-opcionales-cotizacion-2026-11-15. Se invalidó la caché Blade porque las vistas compiladas conservaban temporalmente la versión simplificada tras copiar el respaldo.

FaseCuatroATest/PermisosCotizacionTest/SimilitudTest: **76 pruebas/541 aserciones aprobadas**, 126,99 s. Navegador: **34 comprobaciones/25 capturas aprobadas**, sin errores JS. Se verifica ajuste de mano de obra, precio propuesto independiente del costo, persistencia del propuesto al editar y aprobación de un precio final diferente. npm run build aprobado. Todas las escrituras de pruebas son SQLite temporal; sin operaciones MySQL ni cambios de esquema. Logs y copia del estado anterior a la restauración en el mismo directorio privado; capturas en docs/capturas/cotizaciones-revision-2026-11-14. La solicitud posterior de restaurar sustituye las conclusiones funcionales de la simplificación, conservadas como antecedente.


Ver cotización, distribución de recuadros (15/11/2026): npm run build, sintaxis Blade y recorrido cotizaciones.cjs aprobados. **36 comprobaciones/25 capturas** en Edge con SQLite temporal; comprueban que Datos/Resumen comparten fila y los cuatro costos comparten columnas a 1440 px, ausencia de desborde a 1440/1024/390 px, oscuro móvil y flujos existentes de registro/edición/aprobación/PDF/consulta del maestro. Se actualizó el capturador para cerrar el aviso temporal de registro antes de tomar imágenes. No se repitió PHPUnit porque el cambio solo afecta la distribución de una vista y CSS; no se modifican servicios ni validaciones. Respaldo y log en storage/app/private/consulta-cotizacion-columnas-2026-11-15.


Formato compacto de entradas, 15/11/2026: DecimalEntradaTest, FaseCuatroATest, PermisosCotizacionTest y SimilitudTest: **79 pruebas/562 aserciones aprobadas**, 126,13 s. Se comprueba equivalencia numérica exacta incluso con valores grandes y fracciones pequeñas, distinción vacío/cero y conservación de texto inválido para su corrección. Navegador cotizaciones.cjs: **37 comprobaciones/25 capturas**, sin errores JS; valores 25, 120, 2 y 40 al editar, con guardado/aprobación/PDF y permisos conservados. npm run build y sintaxis PHP/JS aprobados. La expectativa visual de valores cargados por catálogo/casos se actualiza en fase4b.cjs, sin ejecutar ese recorrido completo. Todas las escrituras de prueba usan SQLite aislada; sin cambios MySQL. Respaldo y logs en storage/app/private/numeros-formulario-cotizacion-2026-11-15.


## Cotizaciones: solo Observaciones — 15/11/2026

FaseCuatroATest, PermisosCotizacionTest y DecimalEntradaTest: **46 pruebas/403 aserciones aprobadas**, 63,11 s. Nueva prueba verifica ausencia de Condiciones en crear/editar, actualización de Observaciones, conservación del texto anterior cuando se omite y condiciones nulas en nuevas altas desde datos sin ese campo. Navegador cotizaciones.cjs: **37 comprobaciones/25 capturas aprobadas**, incluida ausencia del control Condiciones en crear/editar; guardado, aprobación, PDF y permisos se mantienen. Sintaxis PHP/Blade válida. Sin cambios de JS/CSS ni necesidad de compilación. No se modifican MySQL, migraciones ni registros locales. Respaldo y logs en storage/app/private/cotizaciones-solo-observaciones-2026-11-15; capturas en docs/capturas/cotizaciones-revision-2026-11-14.


## Crear pedido — 15/11/2026

Cambios limitados a resources/views/admin/pedidos/create.blade.php y estilos propios en resources/css/app.css; recursos compilados actualizados. Se añade tests/browser/crear-pedido.cjs para verificar la vista y ambos flujos de registro.

- PedidosPlanificacionTest: **13 pruebas y 104 aserciones aprobadas**, 25,35 s.
- npm run build y comprobación de sintaxis Blade aprobados.
- Edge/Playwright: **8 comprobaciones y 6 capturas**, resultado APROBADO; anchos 1440, 1280, 1024 y 390 px, tema oscuro móvil, sin desbordamiento de página ni tabla de resumen. Se verifican columnas en escritorio, alternancia de cliente existente/nuevo, Cancelar, registro de ambos tipos de cliente y conservación del importe aprobado. Sin errores JavaScript.
- Todas las escrituras de prueba usan SQLite temporal aislada. Sin modificaciones de MySQL, esquema ni datos locales.

Copias previas y logs: storage/app/private/diseno-crear-pedido-2026-11-15. Capturas e informe del navegador: docs/capturas/crear-pedido-2026-11-15. La primera ejecución detectó una aserción demasiado estricta sobre un párrafo con nombre y teléfono; se corrigió el selector del test. La revisión visual detectó un ancho mínimo global de tabla y se sustituyó únicamente para el resumen compacto. El recorrido final completo pasó.


## Mensaje de fecha de entrega — 15/11/2026

El formulario mostraba validation.after_or_equal al recibir una fecha anterior a hoy. Se añade la traducción de after_or_equal/date_format y un mensaje específico en español para la entrega prevista. El parcial pedidos/_datos incluye error junto al campo, ayuda y min coherente con la regla existente: hoy al crear y fecha de aceptación al editar. No se cambia la validación de servidor ni se sustituyen fechas automáticamente. Se conserva old() para corregir el dato sin perder lo rellenado.

PedidosPlanificacionTest: **14 pruebas/112 aserciones aprobadas** (19,19 s), incluida petición HTTP con fecha pasada rechazada sin crear pedido y posterior aceptación de hoy. Edge/Playwright: **9 comprobaciones/6 capturas aprobadas**, incluido envío que omite deliberadamente la validación HTML para comprobar la respuesta del servidor, error traducido junto al campo, conservación de cliente/fecha y guardado posterior. Escrituras exclusivamente en SQLite aislada; MySQL intacto. Copia previa y logs: storage/app/private/correccion-fecha-pedido-2026-11-15. Sin cambios CSS/JS de producción ni nueva compilación necesaria.


## Revisión visual del módulo Pedidos — 15/11/2026

Archivos de presentación: resources/views/admin/pedidos/index.blade.php, show.blade.php y edit.blade.php; estilos acotados en resources/css/app.css y recursos compilados. Se amplía tests/browser/crear-pedido.cjs para recorrer listado, consulta y edición. No se modifican controladores, servicios, modelos, rutas ni esquema.

- PedidosPlanificacionTest: **14 pruebas / 112 aserciones aprobadas**, 19,94 s.
- npm run build aprobado.
- Edge/Playwright: **15 comprobaciones / 12 capturas aprobadas**, sin errores JavaScript. Creación con clientes existentes/nuevos, rechazo traducido de fechas pasadas, detalle, filtros Buscar/Limpiar, edición de observaciones, conservación de Bs 150,00 y Cancelar hacia el listado. Sin desbordamiento de página en escritorio y móvil; las tablas anchas conservan desplazamiento interno.
- Inspección visual de detalle y listado en escritorio; comprobaciones móviles a 390 px. Crear mantiene su cobertura previa de tema oscuro.

Pruebas de escritura exclusivamente en SQLite temporal aislada. Sin modificaciones de MySQL ni del sistema escolar. Respaldo y logs en storage/app/private/diseno-pedidos-2026-11-15; evidencias en docs/capturas/pedidos-revision-2026-11-15.


## Regreso al pedido de origen — 15/11/2026

Los enlaces desde Detalle del pedido identifican el contexto con desde=pedido. Volver/Cancelar utiliza la relación del registro y una ruta interna al pedido autorizado; no acepta URLs de retorno proporcionadas por el cliente. Se aplica a cotización, planificación y edición, edición de pedido, formulario de materiales utilizados, pagos (listado, consulta y formularios) y casos relacionados. Los accesos independientes conservan los listados anteriores. Se propaga el contexto en enlaces GET anidados de planificación y pagos; no se cambia el destino de guardados ni las operaciones de negocio.

PedidosPlanificacionTest: **14 pruebas/112 aserciones aprobadas**, 19,86 s. Edge/Playwright: **16 comprobaciones/12 capturas**, APROBADO. Verifica recorrido pedido → cotización → Volver, planificación → editar → Cancelar, pagos → nuevo pago → Cancelar, registrar pago directo y cancelación de edición del pedido; acceso independiente a cotización regresa al listado. También conserva las comprobaciones de creación, edición, filtros y precio. Sin errores JavaScript. Retornos de casos/materiales utilizados/detalle de pago se revisaron por relación y código; no se recorrieron con registros propios en esta prueba.

Sin cambios de rutas, controladores, permisos, cálculos, CSS o esquema. Todas las escrituras de pruebas en SQLite temporal; MySQL intacto. Respaldo y logs: storage/app/private/retorno-pedido-2026-11-15. Evidencias: docs/capturas/pedidos-retorno-2026-11-15.


## Diseño de registro de materiales utilizados — 15/11/2026

Archivos: resources/views/admin/fabricacion/edit.blade.php y _fila.blade.php, resources/css/app.css y recursos compilados. Se amplía tests/browser/crear-pedido.cjs para recorrer el registro durante fabricación. Sin cambios de controladores, servicios, rutas, validaciones ni base local.

FabricacionTest: **16 pruebas/135 aserciones aprobadas** (23,16 s). npm run build aprobado. Edge/Playwright: **21 comprobaciones/16 capturas aprobadas**; incluye los recorridos anteriores de pedidos y registro de materiales a 1440/1024/390 px y oscuro móvil. Verifica copiar solo vacíos, conservación de cantidad escrita, diferencias decimales, añadir/quitar fila, guardado, valor compacto al reabrir, motivo obligatorio al corregir y retorno al detalle. Sin errores JS ni desbordamiento de página. Se inspeccionaron visualmente las capturas de escritorio y oscuro móvil.

El primer recorrido detectó que copiar mostraba 2.0000; se compactó únicamente el dato de presentación usado en la copia con Decimal::entrada y el recorrido final pasó. El selector de quitar se ajustó al texto existente Quitar fila nueva. Todas las escrituras usan SQLite temporal aislada. MySQL intacto. Respaldo y logs en storage/app/private/diseno-materiales-utilizados-2026-11-15. Capturas en docs/capturas/materiales-utilizados-revision-2026-11-15.


## Retirada de copia de cantidades — 15/11/2026

Retirados botón, aviso, manejador JS y estilos exclusivos. Adaptados crear-pedido.cjs y fase4b.cjs al ingreso manual. npm run build aprobado; crear-pedido.cjs aprobado con 21 comprobaciones/16 capturas, incluyendo ausencia del botón, campos inicialmente vacíos, ingreso manual, diferencia, guardado y corrección justificada. fase4b.cjs actualizado sin repetir su recorrido completo. Sin cambios de servidor ni MySQL. Respaldo y log en storage/app/private/retirar-copia-materiales-2026-11-15.


## Cambios del pedido — 15/11/2026

Ver docs/EDICION-DIRECTA-COTIZACION.md. Suite completa ejecutada: **322 pruebas, 319 aprobadas inicialmente y 3 expectativas antiguas fallidas**. Se actualizaron dos recuentos de migraciones a 29 y la ausencia del botón de permisos del propietario ya retirada por el usuario. Reejecución de las suites afectadas: **30 pruebas/309 aserciones** (ConsolidacionMigracionesTest y FaseCuatroATest) y **11 pruebas/220 aserciones** (PermisosUsuarioTest), todas aprobadas. No se repitió la suite completa tras actualizar únicamente esas expectativas.

Integración focalizada previa: 79 pruebas/693 aserciones aprobadas (107,02 s). CambiosPedidoTest final: 13 pruebas/105 aserciones (19,86 s), todas aprobadas, incluida delegación/denegación individual y fallo de auditoría con rollback. Se actualizó FabricacionTest para exigir ausencia del botón de copia retirado antes de esta tarea. Navegador Edge/Playwright: **27 comprobaciones/22 capturas aprobadas**, incluidos permisos del maestro, ingreso de costos/precio, vista previa, reenvío sin duplicar y PDF, en escritorio/móvil/oscuro. Build y formato PHP aprobados. PDF renderizado e inspeccionado; extracción comprueba importes y ausencia de costos internos.

MySQL aislado: restauración exacta de 31 tablas y migraciones aditivas con datos anteriores intactos; costos sin variar cobro, precio/aceptación, pago/saldo, cierre y caso revisado. Tres carreras multiproceso aprobadas: reenvío idéntico (mismo ID), distinta revisión con misma versión (una rechazada) y cobro/rebaja incompatibles (una rechazada, saldo no negativo). Aplicación local solo después del ensayo: 33 tablas/29 migraciones/62 FK, nuevas tablas vacías y hash/recuento de todas las filas anteriores intactos. GET locales de pedido, cambio y pagos: HTTP 200 con sesiones array; snapshot MySQL idéntico antes/después. Sin escrituras funcionales de prueba locales.

Respaldo/logs/snapshots en storage/app/private/cambios-pedido-2026-11-15. Las incidencias del ensayo y las limitaciones están detalladas en el informe.


## Revisión completa de pedido — 15/11/2026

Informe: EDICION-DIRECTA-COTIZACION.md. Integración amplia: 182 pruebas; 178 aprobaron y 4 fallaron transitoriamente porque una vista se actualizó para llamar cotizacionVigente mientras el proceso ya tenía el modelo anterior cargado. Se repitieron con archivos estables las suites afectadas y las pruebas nuevas: **56 pruebas/422 aserciones aprobadas** (69,14 s); después de la protección adicional de planificación, **RevisionPedidoTest: 13 pruebas/83 aserciones** aprobadas (21,05 s). Durante preparación se corrigieron imports de prueba, comparación de fechas por valor y orden de selección en la aserción de silla, sin relajar validaciones de negocio. No se presenta el primer recorrido como completamente verde ni se suma cobertura solapada.

Navegador Edge/Playwright: **26 comprobaciones/21 capturas aprobadas**. Incluye recorridos previos de pedidos, formulario de revisión, cantidad total, mueble nuevo, sugerencias 246/66, aprobación 250/70, total 320, pago 50/saldo 270, uso anterior conservado, reenvío sin duplicar y navegación a cotización vigente. 1440/1024/390 px y oscuro móvil; sin desbordamiento ni errores JS. Build y formato PHP aprobados.

MySQL aislado: respaldo restaurado con 33 tablas idénticas; columnas aditivas sin cambiar valores anteriores. Caso de ensayo 2→3 mesas más silla, nuevo total 650 sustituyendo 300, pago 100/saldo 550; original/uso intactos, cierre y casos correctos. Tres carreras aprobadas: apertura simultánea del mismo borrador, aplicación duplicada de una revisión y cobro/rebaja incompatibles. Aplicación local: 33 tablas/31 migraciones/64 FK; hashes/cantidades de columnas anteriores idénticos, columnas nuevas NULL para registros anteriores. GET locales de pedido, edición y pagos devuelven 200 sin modificaciones de datos ni sesiones reales. Pruebas de escritura exclusivamente en bases aisladas.

Evidencia privada: storage/app/private/revision-cotizacion-pedido-2026-11-15. Capturas: docs/capturas/revision-pedido-2026-11-15.


## Revisión con planificación cero — 15/11/2026

Corrección del pedido 12: revisión usa max(planificado, utilizado) y conserva filas cero de materiales anteriores sin asumir costo conocido. Cantidades positivas siguen obligatorias en materiales nuevos/cotizaciones normales; precio unitario desconocido no se convierte en cero. 73 pruebas/615 aserciones aprobadas (98,17 s), incluidas revisión, cotización, permisos y cierre. Nuevas comprobaciones: material utilizado no planificado, cantidades superiores a la planificación, cero con costo pendiente, incremento posterior que bloquea aprobación sin costo y cero rechazado en cotización normal.

Edge/Playwright: 26 comprobaciones/21 capturas APROBADO, con material añadido durante fabricación de planificación 0/uso 5, ingreso manual del costo, ampliación/cálculo/aceptación y pago anterior. Build aprobado. MySQL: respaldo nuevo restaurado exactamente en carpinteria_el_tajibo_ensayo_cero_20261115183634_5b297c; apertura del pedido 12 con cantidades 5/2/5 y datos originales sin cambios. El primer ensayo detectó precio_referencia_capturado obligatorio para una fila sin cotización previa; se corrigió y la restauración/prueba final en otra copia pasó. No se relajó el esquema ni se usó el catálogo como costo real desconocido. Sin migraciones ni cambios en MySQL local. Logs/copia de código/respaldos en storage/app/private/corregir-revision-cero-2026-11-15.


Actualización aplicada 16/11/2026: edición directa sin mecanismo de originales/revisiones. MySQL: 31 tablas, 27 migraciones y 55 FK; precios, pagos y saldos preservados. Suite general 316/3186 y focalizada final 27/242 aprobadas; navegador y MySQL aislado/local verificados. Detalle vigente en [Edición directa](EDICION-DIRECTA-COTIZACION.md).


Retornos contextuales corregidos (16/11/2026): Cancelar en materiales utilizados vuelve a la planificación o al detalle del pedido según origen, también tras un error de validación. Cotización conserva Volver al pedido al editar, guardar y aprobar. Verificación: 73 pruebas/612 aserciones aprobadas en SQLite aislado; Edge/Playwright retiro-materiales.cjs y cotizacion-pedido.cjs aprobados, sin errores JavaScript. Sin escrituras funcionales ni cambios de esquema en MySQL local.


Pagos por pedido (16/11/2026): entrada principal con pedidos, cliente, total acordado, pagado y saldo; incluye pedidos sin pagos. Ver abre admin.pagos.pedido con resumen e historial exclusivo del pedido. Registrar/editar/cancelar desde esa ficha regresa al mismo historial. Se conservan consulta/edición del comprobante individual y retornos al detalle del pedido. Sin tablas, migraciones ni cambios de importes existentes. Verificación: 34 pruebas y 303 aserciones (PagosTest, ReportesTest, DashboardTest), todas aprobadas en SQLite aislado; Edge/Playwright pagos-listado.cjs y cotizacion-pedido.cjs aprobados, sin errores JavaScript y con comprobaciones móvil/escritorio/claro/oscuro. Capturas: docs/capturas/pagos-listado-2026-11-16. Sin escrituras funcionales en MySQL local por esta entrega.


## Cotizaciones rechazadas y eliminación segura — 16/11/2026

Implementados rechazo con motivo/confirmación y eliminación exclusiva de preliminares sin aprobación ni pedido. Clientes, categorías y materiales muestran Eliminar deshabilitado con motivo accesible cuando tienen dependencias. Servidor vuelve a verificar permisos efectivos, versión, relaciones y claves foráneas dentro de transacciones. Rechazar conserva importes y aprobación anterior, bloquea edición, PDF comercial y creación de pedido; se refleja en filtros, panel y reportes. Sin reapertura automática ni nuevas tablas o migraciones.

Suite completa final: **339 pruebas y 3382 aserciones aprobadas** (587,45 s), SQLite en memoria. Suite nueva CotizacionesEstadoTest: 9 pruebas/85 aserciones aprobadas; integración focalizada previa: 80/760, con cobertura solapada y sin sumarla a la suite completa. Pint y npm run build aprobados. Edge/Playwright estados-eliminacion.cjs aprobado: eliminación confirmada, rechazo, filtros, bloqueo por relaciones, escritorio/móvil/oscuro y sin errores JavaScript. Seis capturas y resultado en docs/capturas/estados-eliminacion-2026-11-16.

MySQL aislado: carreras aprobar/eliminar y rechazar/crear pedido aprobadas; solo una transición válida, sin pedido sobre cotización rechazada. Clon temporal retirado y hashes de todas las tablas locales intactos. Evidencia: storage/app/private/pruebas-estados-2026-11-16/resultado-mysql.json. Verificación HTTP local de solo lectura: 12 respuestas esperadas (200 o 403 según permiso), sin cambios de datos.

Aplicación local limitada a cotizaciones.eliminar y cotizaciones.rechazar, asignados inicialmente al propietario y delegables mediante el sistema existente. Asignaciones previas, usuarios, contraseñas, registros y datos de negocio intactos. Se registra la operación administrativa en auditoría. Estado final: 31 tablas, 27 migraciones, 50 permisos. Evidencias: storage/app/private/resultado-permisos-estados-eliminacion.json y resultado-lectura-estados-eliminacion.json. No se modificó el sistema escolar.


## Presentación de Reportes — 16/11/2026

Ajustes limitados a admin/reportes/index.blade.php, show.blade.php y estilos propios: cabeceras refinadas, cuatro accesos compactos con iconos, filtros separados en filas/columnas, panel de saldos proporcionado a un único filtro, totales con acento menta y tabla al ancho del contenido con desplazamiento interno móvil. Conservados campos, filtros, etiquetas, permisos, datos y exportación PDF. Sin cambios en controladores, servicios, esquema ni MySQL local.

ReportesTest: 11 pruebas/124 aserciones aprobadas en SQLite aislado (16,99 s). Compilación Vite aprobada. Recorrido Edge/Playwright reportes-diseno.cjs aprobado: cuatro reportes y sus PDF, filtros, limpiar, ausencia de resultados, volver, escritorio/móvil y tema oscuro, sin errores JavaScript ni desbordamiento de página. Capturas y resultado en docs/capturas/reportes-diseno-2026-11-16; revisadas visualmente las fichas de pedidos en escritorio y saldos en móvil oscuro. La verificación visual usa SQLite temporal, sin operaciones funcionales de escritura en MySQL local.


## Dashboard: identidad del taller y agenda — 16/11/2026

Rediseño visual limitado a admin/index.blade.php y CSS propio: bienvenida azul oscuro/menta con ilustración de mueble hecha en CSS, cuatro indicadores enlazados, agenda de hasta cinco pedidos por entrega, tabla de cotizaciones recientes y accesos a clientes/materiales. Solo datos ya disponibles, sin gráficos con cifras inventadas, importes, contactos ni nuevas consultas. Mantener permisos efectivos de creación/consulta. Sin cambios de controlador, modelos, rutas, tablas ni MySQL local.

DashboardTest: 5 pruebas/40 aserciones aprobadas (5,97 s), con contadores mensuales, prioridad de entregas, registros limitados, ocultación y ausencia de consultas sin permiso, estados vacíos y acceso autenticado. Compilación Vite aprobada. Edge/Playwright dashboard-diseno.cjs aprobado en SQLite temporal: propietario/maestro, agenda con/sin fecha, enlaces a pedido/cotización/crear, 1440/1024/390 px, tema oscuro y sin errores JavaScript ni desbordamiento de página. Seis capturas en docs/capturas/dashboard-diseno-2026-11-16; inspección visual de escritorio claro y móvil oscuro.


## Tablero de pedidos en el dashboard — 16/11/2026

La sección de pedidos del panel se presenta como tablero de consulta con cuatro columnas: Aceptado, En fabricación, Terminado y Entregado. Total completo por estado y hasta cinco tarjetas por columna; enlace al listado filtrado cuando hay más. Abiertos por entrega próxima y cerrados por actualización reciente. Cada tarjeta permite Ver pedido; no arrastrar ni cambiar estados desde el tablero. Conservar transiciones, validaciones y permisos del detalle. Consultar solo con pedidos.consultar; no cargar importes ni datos personales para estas tarjetas. Sin tablas, migraciones ni cambios de datos existentes.

DashboardTest: 6 pruebas/46 aserciones aprobadas (8,05 s). Cobertura de las cuatro columnas, límites y totales reales, orden de abiertos/cerrados, datos mínimos, ausencia de consultas sin permiso, estados vacíos y lectura sin modificaciones. Pint y compilación aprobados. Edge/Playwright dashboard-diseno.cjs aprobado: cuatro columnas, tarjetas y vacíos, enlaces, propietario/maestro, 1440/1024/390 px y oscuro, sin errores JavaScript ni desbordamiento de página. Pruebas en SQLite temporal aislado; MySQL local sin escrituras. Capturas: docs/capturas/dashboard-tablero-2026-11-16.

Tablero compacto (16/11/2026): tarjetas grandes sustituidas por filas de pedido, fecha y Ver; línea lateral azul en Aceptado, menta en En fabricación, verde en Terminado y plomo en Entregado. Compilación y recorrido dashboard-diseno.cjs aprobados nuevamente en SQLite temporal para ambos roles, escritorio/tableta/móvil y oscuro. Sin cambios de consultas, estados ni permisos.


## Dashboard según referencia original — 16/11/2026

Por solicitud del usuario, adaptar el panel al archivo referencias/dashboard-referencia.png: ocho indicadores con degradados (cotizaciones del mes/por revisar, pedidos aceptados/en fabricación/terminados/entregados, clientes y materiales), gráfico circular de estados junto a cotizaciones recientes y tablero compacto con líneas de color debajo. Retirar el bloque grande de bienvenida y sus estilos exclusivos. Conservar menú y menta en lugar del amarillo. Mostrar únicamente indicadores autorizados; reutilizar totales completos existentes de tableroPedidos para el gráfico, sin nuevas consultas ni cifras ficticias. Datos de pedidos sin importes ni contactos. Estados, transiciones, permisos y límites permanecen vigentes.

Comprobación final: DashboardTest, **6 pruebas/49 aserciones aprobadas** (7,85 s), con gráfico de total completo aunque haya más de cinco tarjetas, cero explícito y ausencia de gráfico sin permiso. Se corrigió un cierre Blade incompleto detectado en el primer recorrido de pruebas; la reejecución final completa de DashboardTest pasó. Vite compiló correctamente. Edge/Playwright dashboard-diseno.cjs aprobado: ocho indicadores, total y leyenda del gráfico, tablero, enlaces y permisos para propietario/maestro, 1440/1024/390 px, claro/oscuro y sin errores JavaScript ni desbordamiento de página. Se inspeccionaron las capturas de escritorio claro y móvil oscuro. Evidencias en docs/capturas/dashboard-referencia-2026-11-16. Todo el recorrido funcional utiliza SQLite temporal aislado; sin escrituras en MySQL local ni cambios de esquema, controlador, cálculos o autorizaciones.


Bienvenida restaurada (16/11/2026): el usuario solicita incorporar de nuevo el bloque azul oscuro y menta «Todo listo para seguir creando», con ilustración CSS y accesos Nueva cotización/Ir a pedidos sujetos a sus permisos. Se coloca sobre los ocho indicadores; se conserva el gráfico y el tablero compacto. Nueva cotización aparece solo en el bloque para evitar duplicación. Esta decisión sustituye únicamente la retirada anterior de la bienvenida. Compilación y dashboard-diseno.cjs aprobados para ambos roles, 1440/1024/390 px y tema oscuro, sin errores JavaScript ni desbordamientos. Sin cambios de backend o MySQL.


## Revisión de redundancias del dashboard — 16/11/2026

Conservar la bienvenida y los ocho indicadores aprobados. Retirar Ir a pedidos del bloque de bienvenida porque duplica Ver todos del tablero; Nueva cotización permanece una sola vez. Retirar el subtítulo general que repite la descripción de bienvenida. Gráfico: total general en el centro y porcentajes por estado en la leyenda, en lugar de volver a repetir las cantidades de las tarjetas/columnas. Mantener totales de columnas porque expresan cuántos pedidos existen aunque solo se muestran cinco. Cotizaciones recientes conserva su función de acceso a registros concretos. Eliminar iconos ocultos de flechas, estilos de acción ya retirada, selector CSS repetido y condicional consecutivo redundante. No cambiar consultas, datos, permisos ni rutas.

DashboardTest: 6 pruebas/49 aserciones aprobadas (8,28 s), compilación Vite y navegador Edge/Playwright aprobados. Verificados ocho indicadores, un solo acceso Nueva cotización, ausencia de Ir a pedidos duplicado, porcentajes sin ceros decimales sobrantes, enlaces y tablero, propietario/maestro, escritorio/tableta/móvil y oscuro. Primer recorrido de navegador detectó 100,0 %; corregida presentación a 100 % y reejecución aprobada. SQLite temporal aislado, sin escrituras en MySQL local. Capturas actualizadas en docs/capturas/dashboard-referencia-2026-11-16.


## Acentos coherentes en los paneles del dashboard — 16/11/2026

Se colorean encabezados e iconos: menta en Estado de los pedidos, azul en Cotizaciones recientes y violeta en Tablero de pedidos. Usar los mismos acentos para cada estado en indicadores, gráfico, leyenda y columnas: cobre para Aceptado, menta para En fabricación, violeta para Terminado y azul petróleo para Entregado. Variables CSS compartidas --order-* para gráfico y tablero; fondos de encabezado suaves y contadores de color, conservando filas compactas. Cambio exclusivamente visual: sin consultas, datos, rutas ni permisos nuevos.

Compilación Vite aprobada. Resultado guardado de dashboard-diseno.cjs: APROBADO en SQLite temporal, propietario/maestro, escritorio/tableta/móvil y tema oscuro, sin errores JavaScript ni desbordamientos. Se inspeccionaron las capturas finales de escritorio claro y móvil oscuro en docs/capturas/dashboard-referencia-2026-11-16. No se repitió la suite funcional para este ajuste de colores. Sin escrituras en MySQL local.


Intensidad de color (16/11/2026): por solicitud del usuario, sustituir los encabezados suaves por color pleno en menta, azul y violeta; cabeceras de estados con cobre, menta, violeta y azul petróleo y texto de contraste. Intensificar también leyenda y cabecera de tabla. Mantener la paleta, distribución, datos y permisos. Vite y dashboard-diseno.cjs aprobados en SQLite temporal para escritorio/tableta/móvil, ambos roles y tema oscuro, sin errores JavaScript ni desbordamiento. Capturas actualizadas en docs/capturas/dashboard-referencia-2026-11-16.


## Recarga integral autorizada — 16/11/2026

El usuario autoriza reiniciar todos los registros de carpinteria_el_tajibo, incluidos usuarios y permisos, y decide crear el propietario manualmente con tajibo:crear-propietario. El esquema ya coincide: conservar 31 tablas y 27 migraciones, sin retirar columnas solo por no aparecer en formularios. Actualizar seeders por módulo con los campos vigentes; no copiar contraseñas ni asignaciones anteriores. DatabaseSeeder incluye UsuariosSeeder, FabricacionSeeder, PagosSeeder y AjustesSeeder además de los módulos existentes. Alta del propietario sin contraseña predeterminada; maestros con contraseña inicial temporal según UsuarioService. Los ejemplos conservan procedencia ficticia interna y no acreditan validación humana. Respaldo restaurado y ensayo aislado comprobados antes del reinicio local. Estado y pasos pendientes en docs/INFORME-RECARGA-DATOS.md; no afirmar carga local completa antes del alta manual y la verificación final.

Ensayo MySQL: relaciones sin huérfanos, costos/precios/saldos/aceptación, campos retirados vacíos e idempotencia aprobados. Suite general 339 aprobadas/1 expectativa antigua fallida; corregida y reejecución de 38 pruebas/602 aserciones aprobada. Reinicio local aplicado con respaldo final restaurado verificado, 0 usuarios y 50 permisos. Pendiente crear propietario y cargar/validar los ejemplos locales.


## Cierre de recarga integral — 16/11/2026

El usuario confirmó el alta manual del propietario. DatabaseSeeder se ejecutó en carpinteria_el_tajibo y se verificó la cuenta manual sin cambiar sus credenciales. Carga final: 3 usuarios (propietario y 2 maestros), 5 categorías, 36 clientes, 27 materiales, 26 cotizaciones, 11 pedidos, 4 pagos y 37 casos; 31 tablas, 27 migraciones, 2 roles y 50 permisos. Relaciones, aceptación/versiones, precios y saldos correctos; campos retirados de formularios sin valores en los ejemplos nuevos. Repetición idempotente comprobada. 34 respuestas HTTP 200 locales, cuatro PDF válidos y dos redirecciones obligatorias de maestros al perfil (302), sin cambios de datos por las lecturas ni sesiones reales. Aplicación fuera de mantenimiento. Respaldos privados verificados conservados y bases temporales retiradas. No quedan pasos pendientes; estado actualizado en docs/INFORME-RECARGA-DATOS.md.


## Renombrado de archivos de carga — 16/11/2026

DatosEjemploSeeder.php pasa a DatosSistemaSeeder.php y config/datos_ejemplo.php a config/datos_sistema.php. Documentos: DATOS-EJEMPLO.md pasa a CARGA-DATOS.md; CASOS-HISTORICOS-INICIALES.md pasa a CATALOGO-CASOS-HISTORICOS.md. Referencias activas de clases/configuración y enlaces actualizados, sin nombres antiguos pendientes en código o enlaces Markdown. Se regeneró la autocarga optimizada con Composer --no-scripts; incluye DatosSistemaSeeder. Configuración preferida TAJIBO_DATOS_SISTEMA, con alias de la variable anterior para conservar instalaciones existentes. Claves de auditoría y lotes persistidos intactos para evitar duplicación y mantener procedencia. No se ejecutaron seeders ni escrituras de negocio en MySQL local por este renombrado.

SeedersModulosTest, InstalacionSeedTest, CasosSeederTest y SimilitudTest: **54 pruebas/605 aserciones aprobadas** (166,64 s), SQLite aislado. Pint aprobado. Verificación de lectura local: 34 respuestas 200, cuatro PDF válidos y dos maestros redirigidos al cambio de contraseña, hashes de datos intactos. Respaldos y capturas históricas conservan sus nombres originales.


## MDF retirado — 16/11/2026

El usuario solicitó expresamente eliminar MDF y sus registros relacionados. Se retiraron 1 material, 4 cotizaciones, 1 pedido y 10 casos históricos de MDF, sus 54 líneas de materiales de casos, 17 de cotizaciones, 4 de pedido, 4 detalles de cotización, 1 detalle de pedido, 2 estados, 1 existencia y 1 movimiento. No se eliminaron pagos: el pedido afectado no tenía ninguno. Los registros ajenos a la retirada, incluidos cuentas, contraseñas, roles, permisos, clientes, ajustes, pagos y otros materiales/estados/consumos, se comprobaron idénticos antes/después. Auditoría histórica conservada; dos eventos de sistema documentan la retirada y la actualización del catálogo, sin atribuir la operación a un usuario que no la ejecutó por interfaz.

Materiales principales actuales: Madera y Melamina. Seeders y JSON ya no generan MDF. El catálogo conserva 25 posiciones originales de lote y la carga completa 27 casos (25 de catálogo y 2 desde pedidos). Se registra una nueva huella del catálogo mediante evento adicional; no se reescriben las auditorías anteriores ni se renumeran los registros. DatosSistemaSeeder cuenta los IDs que aún existen para su resumen, manteniendo el reconocimiento de los lotes y evitando recarga/duplicación tras eliminaciones autorizadas.

El perfil activo de similitud se actualizó a casos-historicos-v1-sin-mdf en el mismo archivo de configuración: 25 referencias, rangos recalculados a partir de ellas y nueva huella SHA-256. Distancia, pesos, k=3 y umbral=80 permanecen iguales. Algunas coberturas se reducen al faltar sus referencias extremas de MDF; no se amplían ni se inventan antecedentes. Los IDs esperados en pruebas se resuelven por posición de lote para conservar la identidad del caso aunque una instalación nueva tenga otra numeración.

Estado local: 3 usuarios, 5 categorías, 36 clientes, 26 materiales, 22 cotizaciones, 10 pedidos, 4 pagos y 27 casos históricos; 31 tablas, 27 migraciones y 50 permisos. No se modificó el esquema, el sistema escolar ni sus datos.

Retirada comprobada primero en MySQL aislado sobre un respaldo restaurado idéntico. Antes de aplicar se generó y restauró otro respaldo completo: C:/wamp64/www/carpinteria-el-tajibo/storage/app/private/retirada-mdf-20261116230229_ec34c0/respaldo-antes-aplicar.sql; SHA-256 30342c5b12cf5ec59a76b187dba18919851c34d5c936efe4fcdbbc6ad543bbbd. Eliminación en transacción con FK activas, dependencias cotejadas con el ensayo y hashes de todos los registros conservados iguales. Seeders repetidos sin cambios y sin recrear MDF. Evidencias privadas: plan.json, ensayo.json, perfil-verificado.json, resultado-local.json y verificacion-local.json en la misma carpeta.

Las primeras pruebas detectaron un recuento antiguo y que el perfil todavía exigía referencias MDF; corregidos el recuento y las referencias/rangos del perfil. Reejecución de SeedersModulosTest, InstalacionSeedTest, CasosSeederTest y SimilitudTest: 54 pruebas/599 aserciones aprobadas (167,18 s). Dos aserciones explícitas adicionales de ausencia de MDF: InstalacionSeedTest 4/48 aprobadas (14,17 s). No sumar suites solapadas. Pint aprobado. Búsqueda en MySQL aislado y local: dos referencias de mesa/madera, 96,63 % y 88,63 %, sin modificar datos. Verificación HTTP local: 33 respuestas 200, cuatro PDF válidos y dos redirecciones de maestros para cambiar contraseña; lecturas con hashes intactos y aplicación fuera de mantenimiento. No se presenta esta comprobación por kernel como navegación visual real sobre MySQL.


## Casos vinculados a pedidos — 16/11/2026

Decisión expresa del usuario: retirar los 25 casos sin pedido y completar 87 casos, todos con detalle de pedido. Se conservan los 2 casos vinculados anteriores y se generan 85 mediante 37 pedidos adicionales, con distintas líneas de muebles. Distribución final: 12 pedidos con 1 caso, 12 con 2, 9 con 3 y 6 con 4; 39 pedidos aportan los 87 casos. Un caso representa una línea de muebles iguales, aunque su cantidad física sea mayor que uno.

CasosHistoricosSeeder utiliza los servicios de cotización, aprobación, pedidos, fabricación y retención; FabricacionSeeder comparte el cierre validado. Se conservan materiales/cantidades, costos estimados y precio del grupo, sin multiplicar los totales dos veces. Las 25 entradas JSON son plantillas de referencia reutilizadas; no son 25 casos adicionales ni acreditan 87 diseños únicos. Se mantienen 25 vínculos técnicos con el perfil fijo de similitud; no se cambian sus pesos, rangos, k ni umbral. La procedencia sigue siendo simulada y la revisión humana pendiente.

No se reinician cuentas, permisos, existencias, pagos ni registros ajenos a los 25 casos retirados y sus líneas. La repetición del seeder conserva las ediciones y no duplica pedidos/casos; la cifra de 87 corresponde a esta carga, no impide retener nuevos trabajos posteriormente. Sin nuevas tablas ni migraciones. Estado y comprobaciones en INFORME-CASOS-PEDIDOS-87.md.


## Propuesta descartada: cabecera compacta — 17/11/2026

El usuario eligió el bloque «Tu taller, al día»: bienvenida azul oscuro con remate diagonal menta y acceso Nueva cotización a la izquierda; cuatro indicadores en cuadrícula 2×2 a la derecha: Cotizaciones (total general), Pedidos (total general), Casos históricos y Clientes. Sustituye las ocho tarjetas anteriores, sin duplicarlas debajo. Cada total y enlace se consulta/muestra solo con el permiso correspondiente; no usar cifras fijas de la maqueta. Mantener el menú lateral, la navegación superior, el gráfico, las cotizaciones recientes y el tablero de pedidos existentes. En pantallas pequeñas, bienvenida sobre los indicadores. Sin cambios de MySQL, permisos ni esquema.

Verificación: npm run build y Pint aprobados; DashboardTest: 6 pruebas y 49 aserciones en SQLite aislado. Edge/Playwright: propietario y maestro, 1440/1024/390 px, tema claro/oscuro, enlaces y totales, sin desbordamiento horizontal ni errores JavaScript. Capturas y resultado: capturas/dashboard-resumen-2026-11-17/. Los números de las capturas corresponden exclusivamente a la base temporal de pruebas.


## Dashboard anterior restaurado — 17/11/2026

El usuario descartó la cabecera compacta del último cambio. Se restaura «Todo listo para seguir creando» con ilustración de mueble y ocho indicadores debajo: cotizaciones del mes/por revisar, pedidos aceptados/en fabricación/terminados/entregados, clientes y materiales activos. Se restauran las consultas mensuales, permisos y estilos anteriores; sin cambios en MySQL ni en otros módulos. La propuesta compacta queda descartada.


## Dashboard sin tarjetas duplicadas — 17/11/2026

Retiradas las cuatro tarjetas de estados de pedidos; quedan cuatro indicadores. Edge/Playwright aprobado en SQLite temporal: propietario/maestro, 1440/1024/390 px y tema oscuro, sin errores JavaScript ni desbordamientos. Gráfico, tablero, accesos y bienvenida conservados. Resultado y capturas: capturas/dashboard-sin-duplicados-2026-11-17/. Cambio exclusivo de Blade; sin recursos CSS/JS nuevos ni escrituras en MySQL.



## Fecha automática de cotización — 17/11/2026

FaseCuatroATest, CotizacionPedidoTest, CotizacionesEstadoTest y PermisosCotizacionTest: 68 pruebas, 582 aserciones aprobadas (88,96 s), sobre SQLite aislado. Nuevas verificaciones: creación sin fecha manual, rechazo efectivo de una fecha enviada mediante su exclusión, reenvío tras cambio de día sin duplicación, preservación de una fecha histórica al editar y updated_at separado. Pint aprobado para los cuatro PHP modificados. Edge/Playwright aprobado: Crear, Editar y Ver, 1440/1024/390 px y tema oscuro, aprobación, PDF, búsqueda y permisos de maestro, sin errores JavaScript ni desbordamientos. Capturas y resultado: capturas/cotizaciones-fecha-automatica-2026-11-17/. No se ejecutaron migraciones ni escrituras sobre MySQL local; no se alteraron fechas existentes.



## Cotización sin observaciones generales — 17/11/2026

Por decisión del usuario se retiran las observaciones generales de Crear/Editar cotización, conservando Color y observaciones del mueble. Se elimina el cuadro Datos generales, que ya no tenía campos tras automatizar Fecha, y se integran sus indicaciones en la guía existente. Las notas generales y condiciones anteriores siguen en Ver y no se borran al editar sin esos campos. No se eliminan columnas ni datos, ni se alteran observaciones de pedidos/materiales. Se retiran únicamente los estilos sin uso del cuadro introductorio anterior.

Verificación: FaseCuatroATest y CotizacionPedidoTest, 44 pruebas y 375 aserciones aprobadas (57,55 s), SQLite aislado. Se comprueba conservación de notas anteriores y edición de notas por mueble. Pint y Vite aprobados. Edge/Playwright aprobado para alta, edición, consulta, aprobación y PDF, propietario/maestro, escritorio/tableta/móvil y tema oscuro, sin errores JavaScript ni desbordamientos. Capturas: capturas/cotizaciones-sin-notas-generales-2026-11-17/. Sin escrituras en MySQL local.


## Historial 2020–2026 — 17/11/2026

Aplicado y verificado en carpinteria_el_tajibo tras respaldo restaurado, ensayo y navegador. 4 pruebas de instalación y 655 aserciones aprobadas; Pint aprobado. 157 lecturas HTTP 200 y cuatro PDF válidos sin cambios de datos; recomendaciones y cronologías correctas. Excel reconciliado con 47 pedidos, 620 costos, 82 pagos y 36 clientes. Detalle: HISTORIAL-CARPINTERIA.md.



## Hora boliviana en guardados automáticos — 17/11/2026

Laravel/PHP usan APP_TIMEZONE=America/La_Paz. MySQL y MariaDB configuran ahora cada conexión con DB_TIMEZONE=-04:00, también como valor predeterminado, evitando depender de la zona SYSTEM del servidor. Mantener ambas variables en .env.example. La configuración se aplica de forma central a los timestamps y a las vistas existentes; no introducir restas manuales de cuatro horas en controladores o Blade. No cambiar globalmente la hora de Windows/MySQL ni afectar el sistema escolar.

El diagnóstico de la sesión actual (PHP web 8.4.15, consola 8.3/8.4 y MySQL) ya coincidía con Bolivia antes del cambio; no se reprodujo un adelanto en nuevos guardados. Se fijó explícitamente la conexión para mantener esa coherencia. No se desplazaron timestamps existentes ni se recalcularon las horas fijas del historial. No hay evidencia suficiente para tratarlos todos como UTC. El diagnóstico local temporal se eliminó inmediatamente.

Verificación: copia MySQL restaurada, 13 módulos y 63 campos de fecha/hora guardados mediante servicios, incluyendo usuarios/personal, catálogo, cotizaciones/aprobación, pedidos, estados, materiales utilizados, existencias/movimientos, casos, pagos y auditoría. Todos coinciden con el instante local; la base operativa quedó idéntica. Copia temporal retirada. FaseCuatroATest: 30 pruebas y 285 aserciones aprobadas; incluye cruce de medianoche UTC (02:30 UTC equivale a 22:30 del día anterior en Bolivia), persistencia y vista. Edge/Playwright con PHP 8.4: hora de actualización contrastada con la cabecera HTTP Date convertida a America/La_Paz; alta/edición/aprobación/PDF, roles, móvil y oscuro aprobados. Pint aprobado. Capturas: capturas/cotizaciones-hora-bolivia-2026-11-17/. Evidencia de MySQL: storage/app/private/hora-bolivia-actual.json.


## Listados de más recientes a antiguos — 17/11/2026

Decisión del usuario: ordenar los listados por fecha descendente, con ID descendente para desempatar. Cotizaciones usa fecha; Pedidos, Planificación y el listado de pedidos de Pagos usan fecha_aceptacion; Casos históricos usa fecha_referencia; pagos de un pedido mantienen fecha. Clientes, Categorías, Materiales, Existencias (listado de materiales), Usuarios, Tipos de muebles, Roles, Movimientos y Auditoría usan created_at. No usar únicamente ID como sustituto de fecha para los registros históricos.

Aplicar también a los pedidos dentro de Ver cliente, los materiales de Ver categoría y el reporte de saldos. Conservar filtros, paginación y permisos. Los selectores de formularios y los grupos de permisos conservan su organización; el tablero del dashboard mantiene sus prioridades de entrega. No ordenar por updated_at ni promover un registro antiguo por una edición. Sin cambios en datos, esquema o MySQL.

OrdenListadosTest: 4 pruebas y 42 aserciones aprobadas sobre SQLite aislado. Se utilizaron fechas opuestas al orden de los IDs/nombres y fechas empatadas, incluidos filtros, segunda página y listados relacionados. ReportesTest también aprobado en la ejecución conjunta inicial de 15 pruebas (164 aserciones); la comprobación adicional de roles se aprobó después en las mismas cuatro pruebas de orden. Pint aprobado. Edge/Playwright con PHP 8.4: listado, búsquedas, flujo de cotizaciones, permisos, PDF, escritorio/móvil y tema oscuro aprobados, sin errores JavaScript. Capturas: capturas/listados-recientes-2026-11-17/. El primer intento del test de movimientos usaba una ruta que no existe; se corrigió a admin.existencias.show y se repitió satisfactoriamente. No se modificaron registros locales.


## Listados por ID descendente — 17/11/2026

Aplicada la corrección del usuario: ID mayor primero en listados principales, relacionados y reportes. OrdenListadosTest y ReportesTest: 15 pruebas, 166 aserciones aprobadas (29,63 s), SQLite aislado. Fechas y nombres deliberadamente opuestos al ID; filtros, segunda página, movimientos, roles y listados relacionados comprobados. Pint aprobado. Cambio de consultas exclusivamente, sin escrituras en MySQL ni cambios de vistas/estilos.

