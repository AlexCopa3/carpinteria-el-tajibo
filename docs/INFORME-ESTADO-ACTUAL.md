# Estado actual — Carpintería El Tajibo

> Actualización del 16/11/2026: las revisiones de pedidos conservan el ID de su cotización original. Esquema verificado con 33 tablas, 32 migraciones y 64 FK; datos anteriores preservados. Véase [Cotización con el mismo ID](EDICION-DIRECTA-COTIZACION.md).

> Estado vigente de permisos, 13/11/2026: 31 tablas, 27 migraciones, 55 FK y 48 permisos. Delegación completa por rol y excepciones individuales con prioridad. [Aplicación y verificaciones](INFORME-PERMISOS-GENERALES-INDIVIDUALES.md). Los cortes previos que aparecen debajo se conservan como antecedentes.

> Estado posterior vigente (9/11/2026): Casos históricos usa una sola búsqueda, sin selector o modo separado. Perfil único config/perfiles/similitud-casos-historicos-v1.json. Los registros conservan su procedencia y revisiones. Véase [flujo actualizado](FLUJO-COTIZACION-CON-CASOS.md); esta decisión sustituye las instrucciones anteriores de seleccionar demostración.

> Actualización posterior autorizada: buscar desde el formulario antes de guardar y elegir el vecino más similar como principal o una alternativa editable. Véase [flujo de cotización con casos](FLUJO-COTIZACION-CON-CASOS.md). Los límites de consulta sola de la entrega original quedan sustituidos únicamente para esta integración.

Actualizado el 10 de noviembre de 2026 tras [fase 6B](FASE-6B.md). El [informe del 5/11](INFORME-GENERAL-2026-11-05.md) es histórico; evidencias archivadas en [archivo histórico](ARCHIVO-HISTORICO.md).

## Implementado

Autenticación, usuarios/personal, perfil, roles/permisos, clientes, categorías, materiales, existencias sencillas, cotizaciones manuales con aprobación/PDF, pedidos, planificación y uso de materiales. Dashboard, iconos, acento menta, menú fijo y temas claro/oscuro.

4A–4D, 5A–5C y 6A–6B implementadas. Pedido: Aceptado → En fabricación → Terminado → Entregado, con validación de materiales y ratificación del precio por propietario. Retención idempotente por detalle y revisión diferenciada de materiales/precio. Casos con materiales/cantidades/costos estimados/precio conservan instantáneas, origen y revisión separados.

Desde Nueva cotización o Editar se consulta la descripción antes de guardar y sin materiales/costos. Se puede elegir el vecino más cercano como principal o una alternativa, confirmar y editar los campos completados. También se conserva la consulta desde Ver. Hasta tres referencias del mismo tipo, similitud exacta >=80 %, diferencias y valores del grupo original. Perfil piloto versionado y rangos fijos, sin extrapolación, modificación de cotización o existencias.

35 casos simulados/172 materiales, actualmente 34 pendientes y uno revisado; cero casos reales. Esa revisión preexistente se conservó. Consulta única de antecedentes completos con perfil fijo; sin selector de modo u origen. Los resultados sintéticos no acreditan validez comercial.

La selección editable desde el formulario está autorizada e implementada; véase el flujo actualizado. 5C completada con dos estimaciones para la misma cantidad solicitada y aviso por cambio de lote. Pagos implementado con efectivo/QR, edición auditada y saldos, exclusivamente para propietario. Reportes operativos implementados con filtros y PDF. Captura de gastos y ganancias contables pendientes. Sin descuentos automáticos de existencias.

## MySQL y conservación

carpinteria_el_tajibo: **30 tablas/26 migraciones/53 FK**. 6A añadió únicamente pagos y su migración. Respaldo completo restaurado y ensayo concurrente verificados antes de aplicar; todos los datos anteriores preservados. Pagos vacía, sin permisos o cargas nuevas. [Aplicación 6A](FASE-6A.md).

## Verificación

**269 pruebas PHP/2581 aserciones**, SQLite aislado. **133 comprobaciones Edge/Playwright**, sin errores JS. MySQL 6B: cuatro reportes y PDF por propietario/maestro, solo lectura y comparación íntegra antes/después, sin autenticación HTTP real. No son métricas de precisión ni revisión humana. [Verificación](VERIFICACION.md).

Instalación local con php artisan migrate --seed: catálogos, ejemplos y 35 casos simulados; solicita propietario si falta, conserva cuentas y evita duplicaciones. Producción excluye ejemplos. Perfil portable por lote/posición/código/alias, sin IDs fijos de esta computadora. [Instalación](INSTALACION-CON-SEED.md).

Controladores explícitos, Eloquent, rutas /admin y admin.*, Blade por módulo y servicios para lógica compleja. Sistema escolar sin modificaciones. [Índice documental](README.md).

## Preparación del siguiente módulo — 10/11/2026

Pagos: diseño aprobado e implementado (efectivo/QR, edición y entrega con advertencia). [Entrega 6A](FASE-6A.md): migración aplicada, 30 tablas/26 migraciones; 11 pedidos previos conservados y cero pagos iniciales inventados. Permisos existentes solo en propietario, sin alteraciones.

## Reportes 6B — 10/11/2026

Cotizaciones, pedidos, pagos recibidos y saldos actuales, solo propietario con permisos del módulo. Todos los registros del sistema según confirmación del usuario; PDF imprimible y pantalla con filtros. Sin nuevas tablas, dependencias, migraciones o escrituras MySQL. Saldos actuales sin fecha de corte histórica. [Entrega y verificaciones](FASE-6B.md).

## Actualización de permisos — 13/11/2026

Implementación aplicada y verificada el 13 de noviembre de 2026, tras aprobación del usuario, respaldo completo restaurado y ensayo aislado. Permisos generales editables para todos los módulos; excepciones individuales Según su rol / Permitir / Denegar con prioridad sobre Spatie. El propietario conserva las cuatro capacidades de recuperación documentadas. Base carpinteria_el_tajibo: 31 tablas, 27 migraciones, 55 FK y 48 permisos; propietario 48 y maestro sus mismos 15. Sin delegaciones automáticas al maestro, cambios de contraseñas ni pérdidas de registros. Informe: docs/INFORME-PERMISOS-GENERALES-INDIVIDUALES.md. Esta decisión sustituye las restricciones históricas por rol; conservar las validaciones del negocio y no avanzar a fases nuevas.

Suite final: 293 pruebas y 2920 aserciones aprobadas; 25 grupos de comprobación en Edge con datos temporales, sin errores JavaScript. Nueve comprobaciones MySQL en clon con escrituras revertidas, más renderizado local CLI de solo lectura. Las pruebas no acreditan precisión académica del recomendador ni validación humana.


## Actualización: Tipos de muebles — 14/11/2026

Se incorpora el catálogo administrable sobre tipos_mueble existente: index/create/store/show/edit/update en TipoMuebleController, TipoMueblePolicy, seis rutas /admin/tipos-mueble, cuatro vistas admin/tipos_mueble y opción en Catálogo. Nombre/estado, búsqueda/paginación y auditoría; sin eliminación ni cambios de esquema. Consulta con cotizaciones.consultar y administración adicional con configuracion.gestionar. El resto de reglas individuales permanece. Código técnico interno conservado en ediciones e instantáneas históricas intactas. Tipos nuevos disponibles para cotización manual, sin inventar recomendaciones ni ampliar el perfil KNN automáticamente. Verificación: 89 pruebas/680 aserciones, 20 comprobaciones de navegador y MySQL solo lectura con 31 tablas/27 migraciones/seis tipos, sin diferencias de datos. Detalle en VERIFICACION.md. No se modifica el sistema escolar.


## Edición directa de la cotización del pedido — 16/11/2026

Decisión vigente aprobada: retirar completamente el mecanismo de cotización original, revisiones comerciales y cambios de pedido. Se edita la misma cotización y sus líneas actuales, conservando sus IDs. No crear otra cabecera ni archivar versiones de detalles; no mantener ciclos, mapas JSON, tablas de cambios ni referencias originales.

Editar muebles y precios abre el formulario mediante GET sin escribir datos. Guardar usa CotizacionService con transacción, bloqueo del pedido/cotización, permisos efectivos y comprobación de versión. Restablece la aprobación y recalcula costos y precio sugerido. Conservar el diseño de los muebles aceptados y permitir aumentar su cantidad y añadir muebles; no reducir muebles aceptados desde este flujo.

Registrar materiales utilizados sincroniza exactamente cantidades explícitas, incluidos aumentos, reducciones, cero y materiales nuevos, sin multiplicarlas nuevamente. Vacío sigue pendiente. Conservar costos unitarios anteriores; los nuevos usan una referencia positiva del catálogo como estimación. Costo desconocido permanece NULL y bloquea la aprobación; nunca se convierte automáticamente en gratuito. Si falta autorización de cotizaciones, guardar uso y advertir que alguien autorizado debe actualizar costos. Los errores de una sincronización autorizada revierten la operación completa.

El precio_total_acordado del pedido y precio_acordado de cada detalle contienen el acuerdo actual. Guardar o aprobar una cotización no modifica pagos ni saldo. Actualizar pedido exige aprobación de todos los detalles, aceptación del cliente, permisos de precios/planificación, versiones actuales y total no inferior a lo pagado. PedidoService::aceptarCotizacion actualiza el acuerdo y planificación, conservando consumo, autores y fechas. cotizacion_aceptada_version es un marcador técnico de aceptación, sin historial ni copias. Reenvío idéntico es idempotente.

Cierre exige la cotización actual aprobada y aceptada, importes/cantidades coherentes y materiales validados. Casos usan costos actuales de esa cotización y las cantidades utilizadas. Conservar auditoría general de responsables, permisos, pagos y existencias; no realizar descuentos automáticos de materiales por esta limpieza.

Se retiran las dos tablas de cambios, cinco columnas del mecanismo anterior y sus cinco migraciones de modificación/creación. La migración original de pedidos incorpora el marcador de aceptación para instalaciones nuevas. El usuario autoriza consolidar los datos iniciales y omitir respaldos adicionales en esta operación. Ensayar primero en MySQL aislado y verificar precios, pagos, saldos, integridad y datos de otros módulos. No modificar el sistema escolar.



Actualización aplicada 16/11/2026: edición directa sin mecanismo de originales/revisiones. MySQL: 31 tablas, 27 migraciones y 55 FK; precios, pagos y saldos preservados. Suite general 316/3186 y focalizada final 27/242 aprobadas; navegador y MySQL aislado/local verificados. Detalle vigente en [Edición directa](EDICION-DIRECTA-COTIZACION.md).


Pagos por pedido (16/11/2026): entrada principal con pedidos, cliente, total acordado, pagado y saldo; incluye pedidos sin pagos. Ver abre admin.pagos.pedido con resumen e historial exclusivo del pedido. Registrar/editar/cancelar desde esa ficha regresa al mismo historial. Se conservan consulta/edición del comprobante individual y retornos al detalle del pedido. Sin tablas, migraciones ni cambios de importes existentes. Verificación: 34 pruebas y 303 aserciones (PagosTest, ReportesTest, DashboardTest), todas aprobadas en SQLite aislado; Edge/Playwright pagos-listado.cjs y cotizacion-pedido.cjs aprobados, sin errores JavaScript y con comprobaciones móvil/escritorio/claro/oscuro. Capturas: docs/capturas/pagos-listado-2026-11-16. Sin escrituras funcionales en MySQL local por esta entrega.


## Cotizaciones: rechazo y eliminación controlada — 16/11/2026

Decisión vigente: eliminar solo borradores/cotizaciones con costos pendientes, sin aprobación ni pedido; registrar Rechazada con motivo y confirmación cuando el cliente no acepta y no existe pedido. Rechazar conserva importes y aprobación anteriores, bloquea edición, aprobación, PDF comercial y creación de pedido. No se implementa reapertura automática. Auditoría general, permisos efectivos y versión técnica se conservan.

Clientes, categorías y materiales mantienen desactivación como alternativa y Eliminar deshabilitado con explicación cuando existen relaciones, existencias o historial. Misma comprobación en servidor, transacciones y protección por FK. No se añade eliminación a otros módulos.

Se agregaron cotizaciones.eliminar y cotizaciones.rechazar para el propietario, sin cambiar asignaciones anteriores ni concederlos automáticamente al maestro; disponibles para delegación general/individual. Sin tablas, columnas ni migraciones nuevas: 31 tablas, 27 migraciones y 50 permisos. Datos de negocio locales preservados. Suite completa: 339 pruebas/3382 aserciones aprobadas; navegador, concurrencia MySQL aislada, 12 verificaciones HTTP locales, build y formato aprobados. Ver VERIFICACION.md y docs/capturas/estados-eliminacion-2026-11-16.


Dashboard visual (16/11/2026): composición adaptada a referencias/dashboard-referencia.png, con ocho indicadores autorizados de colores, gráfico de los totales actuales por estado y tablero compacto de pedidos. Reutiliza los datos existentes; sin nuevas consultas, dependencias o cambios de esquema. DashboardTest 6/49, navegador y compilación aprobados. Capturas: docs/capturas/dashboard-referencia-2026-11-16.


## Cierre de recarga integral — 16/11/2026

El usuario confirmó el alta manual del propietario. DatabaseSeeder se ejecutó en carpinteria_el_tajibo y se verificó la cuenta manual sin cambiar sus credenciales. Carga final: 3 usuarios (propietario y 2 maestros), 5 categorías, 36 clientes, 27 materiales, 26 cotizaciones, 11 pedidos, 4 pagos y 37 casos; 31 tablas, 27 migraciones, 2 roles y 50 permisos. Relaciones, aceptación/versiones, precios y saldos correctos; campos retirados de formularios sin valores en los ejemplos nuevos. Repetición idempotente comprobada. 34 respuestas HTTP 200 locales, cuatro PDF válidos y dos redirecciones obligatorias de maestros al perfil (302), sin cambios de datos por las lecturas ni sesiones reales. Aplicación fuera de mantenimiento. Respaldos privados verificados conservados y bases temporales retiradas. No quedan pasos pendientes; estado actualizado en docs/INFORME-RECARGA-DATOS.md.


Nombres de carga vigentes: DatosSistemaSeeder y config/datos_sistema.php; documentos CARGA-DATOS.md y CATALOGO-CASOS-HISTORICOS.md. Autocarga/referencias actualizadas sin alterar datos ni sus lotes. Verificación: 54 pruebas/605 aserciones aprobadas y lectura local sin cambios.


## MDF retirado — 16/11/2026

El usuario solicitó expresamente eliminar MDF y sus registros relacionados. Se retiraron 1 material, 4 cotizaciones, 1 pedido y 10 casos históricos de MDF, sus 54 líneas de materiales de casos, 17 de cotizaciones, 4 de pedido, 4 detalles de cotización, 1 detalle de pedido, 2 estados, 1 existencia y 1 movimiento. No se eliminaron pagos: el pedido afectado no tenía ninguno. Los registros ajenos a la retirada, incluidos cuentas, contraseñas, roles, permisos, clientes, ajustes, pagos y otros materiales/estados/consumos, se comprobaron idénticos antes/después. Auditoría histórica conservada; dos eventos de sistema documentan la retirada y la actualización del catálogo, sin atribuir la operación a un usuario que no la ejecutó por interfaz.

Materiales principales actuales: Madera y Melamina. Seeders y JSON ya no generan MDF. El catálogo conserva 25 posiciones originales de lote y la carga completa 27 casos (25 de catálogo y 2 desde pedidos). Se registra una nueva huella del catálogo mediante evento adicional; no se reescriben las auditorías anteriores ni se renumeran los registros. DatosSistemaSeeder cuenta los IDs que aún existen para su resumen, manteniendo el reconocimiento de los lotes y evitando recarga/duplicación tras eliminaciones autorizadas.

El perfil activo de similitud se actualizó a casos-historicos-v1-sin-mdf en el mismo archivo de configuración: 25 referencias, rangos recalculados a partir de ellas y nueva huella SHA-256. Distancia, pesos, k=3 y umbral=80 permanecen iguales. Algunas coberturas se reducen al faltar sus referencias extremas de MDF; no se amplían ni se inventan antecedentes. Los IDs esperados en pruebas se resuelven por posición de lote para conservar la identidad del caso aunque una instalación nueva tenga otra numeración.

Estado local: 3 usuarios, 5 categorías, 36 clientes, 26 materiales, 22 cotizaciones, 10 pedidos, 4 pagos y 27 casos históricos; 31 tablas, 27 migraciones y 50 permisos. No se modificó el esquema, el sistema escolar ni sus datos.


## Casos vinculados a pedidos — 16/11/2026

Decisión expresa del usuario: retirar los 25 casos sin pedido y completar 87 casos, todos con detalle de pedido. Se conservan los 2 casos vinculados anteriores y se generan 85 mediante 37 pedidos adicionales, con distintas líneas de muebles. Distribución final: 12 pedidos con 1 caso, 12 con 2, 9 con 3 y 6 con 4; 39 pedidos aportan los 87 casos. Un caso representa una línea de muebles iguales, aunque su cantidad física sea mayor que uno.

CasosHistoricosSeeder utiliza los servicios de cotización, aprobación, pedidos, fabricación y retención; FabricacionSeeder comparte el cierre validado. Se conservan materiales/cantidades, costos estimados y precio del grupo, sin multiplicar los totales dos veces. Las 25 entradas JSON son plantillas de referencia reutilizadas; no son 25 casos adicionales ni acreditan 87 diseños únicos. Se mantienen 25 vínculos técnicos con el perfil fijo de similitud; no se cambian sus pesos, rangos, k ni umbral. La procedencia sigue siendo simulada y la revisión humana pendiente.

No se reinician cuentas, permisos, existencias, pagos ni registros ajenos a los 25 casos retirados y sus líneas. La repetición del seeder conserva las ediciones y no duplica pedidos/casos; la cifra de 87 corresponde a esta carga, no impide retener nuevos trabajos posteriormente. Sin nuevas tablas ni migraciones. Estado y comprobaciones en INFORME-CASOS-PEDIDOS-87.md.


## Propuesta descartada: cabecera compacta — 17/11/2026

El usuario eligió el bloque «Tu taller, al día»: bienvenida azul oscuro con remate diagonal menta y acceso Nueva cotización a la izquierda; cuatro indicadores en cuadrícula 2×2 a la derecha: Cotizaciones (total general), Pedidos (total general), Casos históricos y Clientes. Sustituye las ocho tarjetas anteriores, sin duplicarlas debajo. Cada total y enlace se consulta/muestra solo con el permiso correspondiente; no usar cifras fijas de la maqueta. Mantener el menú lateral, la navegación superior, el gráfico, las cotizaciones recientes y el tablero de pedidos existentes. En pantallas pequeñas, bienvenida sobre los indicadores. Sin cambios de MySQL, permisos ni esquema.

Verificación: npm run build y Pint aprobados; DashboardTest: 6 pruebas y 49 aserciones en SQLite aislado. Edge/Playwright: propietario y maestro, 1440/1024/390 px, tema claro/oscuro, enlaces y totales, sin desbordamiento horizontal ni errores JavaScript. Capturas y resultado: capturas/dashboard-resumen-2026-11-17/. Los números de las capturas corresponden exclusivamente a la base temporal de pruebas.


## Dashboard anterior restaurado — 17/11/2026

El usuario descartó la cabecera compacta del último cambio. Se restaura «Todo listo para seguir creando» con ilustración de mueble y ocho indicadores debajo: cotizaciones del mes/por revisar, pedidos aceptados/en fabricación/terminados/entregados, clientes y materiales activos. Se restauran las consultas mensuales, permisos y estilos anteriores; sin cambios en MySQL ni en otros módulos. La propuesta compacta queda descartada.


## Indicadores sin duplicados — 17/11/2026

El usuario solicita retirar indicadores redundantes de la fila superior. Se eliminan únicamente las cuatro tarjetas de estados de pedidos porque sus datos ya aparecen en el gráfico y el tablero. Se conservan en una fila Cotizaciones del mes, Cotizaciones por revisar, Clientes registrados y Materiales activos, con sus diseños, permisos y enlaces anteriores. La bienvenida grande, el gráfico y el tablero permanecen. Sin cambios en consultas, datos, MySQL o menú lateral. Esta decisión sustituye únicamente la restauración de las ocho tarjetas.


## Fecha automática de cotización — 17/11/2026

Por aprobación del usuario, retirar Fecha de los formularios Crear/Editar. CotizacionService asigna today() únicamente en una creación nueva, después de verificar si se trata de un reenvío; usa la zona horaria configurada (America/La_Paz). Conservar fecha al editar, incluidas las cotizaciones históricas y la sincronización de materiales desde pedidos. GuardarCotizacionRequest excluye cualquier fecha enviada para impedir alteraciones y mantener estable la huella de idempotencia, incluso si se reenvía otro día. En Ver, mostrar Fecha de cotización y Última actualización (updated_at) por separado. No modificar registros existentes, esquema, migraciones, fechas de pagos o entrega. Mantener cálculos, permisos y convenciones del módulo.


## Cotización sin observaciones generales — 17/11/2026

Por decisión del usuario se retiran las observaciones generales de Crear/Editar cotización, conservando Color y observaciones del mueble. Se elimina el cuadro Datos generales, que ya no tenía campos tras automatizar Fecha, y se integran sus indicaciones en la guía existente. Las notas generales y condiciones anteriores siguen en Ver y no se borran al editar sin esos campos. No se eliminan columnas ni datos, ni se alteran observaciones de pedidos/materiales. Se retiran únicamente los estilos sin uso del cuadro introductorio anterior.


## Historial de la carpintería desde 2020 — 17/11/2026

El usuario autoriza preparar el historial tanto en Excel como en la base local. HistorialCarpinteriaSeeder se ejecuta al final de DatabaseSeeder, solo en destinos permitidos y una vez por lote. Organiza exclusivamente los registros reconocidos de las cargas anteriores: 59 cotizaciones, 47 pedidos, 36 clientes y 87 casos. Las fechas de cotización, aceptación, estados, uso de materiales, cierre, casos y pagos son coherentes entre 2020 y 2026. Se conservan las cuentas, contraseñas, roles, permisos, cantidades, materiales, importes acordados y descriptores del perfil de similitud.

Los pedidos históricos terminados se entregan mediante el servicio existente; permanecen 4 aceptados, 4 en fabricación, 3 terminados recientes y 36 entregados. Se completan anticipos y cancelaciones, con 82 pagos en total, sin superar acuerdos. Contactos de clientes con nombres bolivianos, prefijo +591, direcciones registradas y correos de formato válido en correo.test. No son direcciones de correo verificadas ni se utilizan dominios personales ajenos. Los usuarios de acceso conservan sus correos.

En pantallas y en el Excel se utilizan nombres normales de registros; se retira el aviso Registro inicial de casos históricos y las notas de uso que mencionaban ejemplos. Conservar internamente origen=simulado, revisión humana pendiente y claves de lotes/auditoría, sin atribuir a estos datos validación humana ni autenticidad documental. No renombrar claves persistidas de cargas anteriores. La aplicación normal mantiene la fecha automática al crear y la fecha original al editar cotizaciones; la asignación histórica pertenece exclusivamente al seeder autorizado. Sin migraciones ni cambios de esquema.

Excel Registros-El-Tajibo.xlsx generado desde una consulta de la base actual: 47 pedidos, 620 líneas de costos, 82 pagos y 36 clientes, en cuatro hojas. Es una exportación editable y no una conexión en vivo. Conserva precios/cantidades de la base; mano de obra/otros costos mantienen su carácter estimado. Detalle en HISTORIAL-CARPINTERIA.md.


## Hora boliviana en guardados automáticos — 17/11/2026

Laravel/PHP usan APP_TIMEZONE=America/La_Paz. MySQL y MariaDB configuran ahora cada conexión con DB_TIMEZONE=-04:00, también como valor predeterminado, evitando depender de la zona SYSTEM del servidor. Mantener ambas variables en .env.example. La configuración se aplica de forma central a los timestamps y a las vistas existentes; no introducir restas manuales de cuatro horas en controladores o Blade. No cambiar globalmente la hora de Windows/MySQL ni afectar el sistema escolar.

El diagnóstico de la sesión actual (PHP web 8.4.15, consola 8.3/8.4 y MySQL) ya coincidía con Bolivia antes del cambio; no se reprodujo un adelanto en nuevos guardados. Se fijó explícitamente la conexión para mantener esa coherencia. No se desplazaron timestamps existentes ni se recalcularon las horas fijas del historial. No hay evidencia suficiente para tratarlos todos como UTC. El diagnóstico local temporal se eliminó inmediatamente.

Verificación: copia MySQL restaurada, 13 módulos y 63 campos de fecha/hora guardados mediante servicios, incluyendo usuarios/personal, catálogo, cotizaciones/aprobación, pedidos, estados, materiales utilizados, existencias/movimientos, casos, pagos y auditoría. Todos coinciden con el instante local; la base operativa quedó idéntica. Copia temporal retirada. FaseCuatroATest: 30 pruebas y 285 aserciones aprobadas; incluye cruce de medianoche UTC (02:30 UTC equivale a 22:30 del día anterior en Bolivia), persistencia y vista. Edge/Playwright con PHP 8.4: hora de actualización contrastada con la cabecera HTTP Date convertida a America/La_Paz; alta/edición/aprobación/PDF, roles, móvil y oscuro aprobados. Pint aprobado. Capturas: capturas/cotizaciones-hora-bolivia-2026-11-17/. Evidencia de MySQL: storage/app/private/hora-bolivia-actual.json.


## Listados de más recientes a antiguos — 17/11/2026

Decisión del usuario: ordenar los listados por fecha descendente, con ID descendente para desempatar. Cotizaciones usa fecha; Pedidos, Planificación y el listado de pedidos de Pagos usan fecha_aceptacion; Casos históricos usa fecha_referencia; pagos de un pedido mantienen fecha. Clientes, Categorías, Materiales, Existencias (listado de materiales), Usuarios, Tipos de muebles, Roles, Movimientos y Auditoría usan created_at. No usar únicamente ID como sustituto de fecha para los registros históricos.

Aplicar también a los pedidos dentro de Ver cliente, los materiales de Ver categoría y el reporte de saldos. Conservar filtros, paginación y permisos. Los selectores de formularios y los grupos de permisos conservan su organización; el tablero del dashboard mantiene sus prioridades de entrega. No ordenar por updated_at ni promover un registro antiguo por una edición. Sin cambios en datos, esquema o MySQL.


## Orden vigente por ID descendente — 17/11/2026

El usuario corrige expresamente la decisión anterior: los listados deben ordenarse por ID de mayor a menor, sin priorizar fechas ni nombres. Aplicar a los listados principales y relacionados de clientes, categorías, materiales, existencias/movimientos, usuarios, roles, tipos de muebles, cotizaciones, pedidos, planificación, casos, pagos y auditoría, y a las filas de reportes. Se conservan los filtros por fecha y la paginación. Los selectores, grupos de permisos y prioridades del tablero del dashboard no se alteran. Esta decisión sustituye el orden cronológico solicitado anteriormente. Sin cambios en los datos almacenados.
