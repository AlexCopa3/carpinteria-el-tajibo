# Recarga de datos con los campos actuales

Estado de consumos actualizado el 17/11/2026: se mantienen 87 casos; cantidades y costos corregidos mediante referencias documentadas. Véase [Consumos de materiales](CONSUMOS-MATERIALES.md). Los importes/consumos de las secciones anteriores conservan el historial de cargas.

Actualizado: 17 de noviembre de 2026. Recarga vigente: 87 casos con IDs del 1 al 87, acceso del propietario conservado. Véase la última sección; las anteriores documentan recargas previas.

## Decisión y estado

El usuario autorizó reiniciar los registros de carpinteria_el_tajibo, incluidos usuarios y permisos, y eligió crear manualmente la nueva cuenta del propietario. El reinicio y la carga local están **completos y verificados**. El usuario creó manualmente al propietario y después se ejecutó DatabaseSeeder. La cuenta conservó su nombre, correo y contraseña elegidos; no se copiaron credenciales anteriores.

La base conserva 31 tablas y las 27 migraciones. Se reiniciaron los registros y sus autoincrementos, conservando el historial de migraciones porque el esquema ya coincide. Se cargaron los catálogos de unidades, tipos de mueble y los roles/permisos iniciales: 2 roles y 50 permisos. Las cuentas anteriores ya no existen; no se copiaron sus contraseñas ni sus permisos personalizados.

## Diagnóstico

No se encontraron columnas que solo existieran en MySQL ni columnas faltantes respecto a las migraciones. Había diferencias únicamente de orden en users.activo y en dos índices de materiales; definiciones, claves y restricciones equivalentes. No se quitaron columnas ni tablas.

Retirar un campo de un formulario no elimina automáticamente su columna. Algunos campos de compatibilidad siguen en el esquema y en servicios vigentes; no se retiran sin analizar dependencias. Los ejemplos nuevos dejan condiciones, cliente_id de cotización y stock_minimo sin valores. Los ajustes opcionales de cotización que siguen vigentes y los campos técnicos no se confunden con campos abandonados.

## Seeders actualizados por módulo

DatabaseSeeder mantiene el alta interactiva del propietario y coordina la carga existente de categorías, clientes, materiales, existencias, cotizaciones, pedidos y casos. Se incorporaron UsuariosSeeder, FabricacionSeeder, PagosSeeder y AjustesSeeder.

- Usuarios: dos maestros con identificación, teléfono y dirección ficticios bolivianos; contraseña inicial temporal según la regla actual del sistema y cambio obligatorio al entrar. El propietario no tiene credenciales fijas en el código.
- Cotizaciones: escenarios en borrador, costos pendientes, aprobadas y una rechazada con motivo; sin exigir cliente ni rellenar condiciones retiradas del formulario.
- Fabricación: registrar consumo y cerrar dos pedidos mediante los servicios vigentes, comprobando aprobación y aceptación de la misma cotización; uno terminado y otro entregado. No saltar las validaciones por asignación directa del estado.
- Pagos: efectivo y QR, un pedido pagado y otros con pagos parciales, sin superar su total acordado.
- Ajustes: identidad de El Tajibo y contactos ilustrativos, sin moneda seleccionable ni credenciales.
- Casos: 35 antecedentes iniciales y 2 retenidos desde los pedidos ficticios terminados; conservan procedencia ficticia y no acreditan validación humana real.

Repetir la carga no duplica registros ni sobrescribe las ediciones posteriores. Los nuevos módulos solo actúan sobre los pedidos generados por los lotes de los seeders; no seleccionan pedidos ajenos.

## Ensayo y pruebas

Esquema nuevo ensayado en MySQL aislado, con una cuenta de propietario exclusiva de pruebas. Resultado: 3 usuarios, 36 clientes, 5 categorías, 27 materiales, 26 cotizaciones, 11 pedidos, 4 pagos y 37 casos; 177 líneas de materiales de casos. Estados de pedidos: 4 aceptados, 5 en fabricación, 1 terminado y 1 entregado.

Comprobadas todas las FK sin huérfanos, cantidades, aceptación/versiones de cotización, precios acordados, saldos no negativos, cierres y repetición idempotente. Origen local intacto durante el ensayo. La verificación inicial del origen se corrigió para usar una conexión independiente tras reconectar el destino de ensayo; la reejecución confirmó los hashes del origen.

Suite general: 340 pruebas ejecutadas; 339 aprobadas y una expectativa antigua que buscaba Pedidos activos. Se actualizó a Tablero de pedidos. Reejecución de FaseDosTest, InstalacionSeedTest, SeedersModulosTest y CasosSeederTest: **38 pruebas y 602 aserciones aprobadas**. No se presenta el primer recorrido como completamente aprobado ni se suman recuentos solapados. Pint aprobado en los archivos modificados. Pruebas funcionales en SQLite aislado y ensayo de datos en MySQL aislado.

## Respaldo y reinicio local

Destino verificado: carpinteria_el_tajibo en MySQL local. Sin dependencias desde otras bases. Se generaron y restauraron dos respaldos completos: diagnóstico y justo antes del reinicio. Coinciden los hashes/recuentos de todas las tablas restauradas.

Respaldo final: C:/wamp64/www/carpinteria-el-tajibo/storage/app/private/reinicio-datos-20261116220032_81d219/respaldo-antes-reinicio.sql

SHA-256: 516fdd3d29be4b5aeb60bdaceb90657f80386874c6a4db953607764d1d547a5d

Evidencias privadas: C:/wamp64/www/carpinteria-el-tajibo/storage/app/private/reinicio-datos-20261116220032_81d219 (preparacion.json, ensayo.json, verificaciones-aprobadas.json y reinicio-aplicado.json). Incluyen información privada; no compartir los SQL ni incorporarlos a entregas públicas.

Durante el vaciado se usó mantenimiento, se restauró la comprobación de FK y después se volvió a habilitar la aplicación. Se conservaron esquema y migraciones. No se modificó el sistema escolar ni su base.

## Resultado final local

La carga está aplicada en carpinteria_el_tajibo: **3 usuarios (propietario manual y 2 maestros), 5 categorías, 36 clientes, 27 materiales, 26 cotizaciones, 11 pedidos, 4 pagos y 37 casos históricos**. Se conservan 31 tablas, 27 migraciones, 2 roles y 50 permisos.

Cotizaciones: 14 aprobadas, 8 borradores, 3 con costos pendientes y 1 rechazada. Pedidos: 4 aceptados, 5 en fabricación, 1 terminado y 1 entregado. Relaciones sin huérfanos; precios, aceptación/versiones y saldos correctos. Los campos retirados del formulario que se conservan para compatibilidad quedaron sin datos en la carga nueva (condiciones y cliente de cotización; stock mínimo de material).

La ejecución repetida no añadió ni modificó registros. Se comprobó en memoria que la cuenta manual mantiene sus credenciales, sin exportarlas en las evidencias. Los dos maestros tienen contraseña inicial temporal y deben cambiarla desde su perfil en el primer acceso.

Comprobación HTTP por el kernel local y sesiones array: **34 respuestas 200**, incluidos dashboard, listados, los 11 detalles de pedido y cuatro reportes PDF válidos. Ambos maestros reciben la redirección obligatoria al perfil (302) antes de usar el panel. Hashes de todas las tablas idénticos antes/después de estas lecturas; aplicación fuera de mantenimiento. Esta comprobación no se presenta como un recorrido visual de navegador sobre MySQL.

Evidencias privadas adicionales: resultado-final.json y verificacion-vistas-locales.json en la carpeta del reinicio. Las dos bases temporales de ensayo/restauración se retiraron; los respaldos SQL verificados siguen conservados. No quedan pasos pendientes para esta recarga.

Para futuras instalaciones, php artisan migrate --seed solicita el propietario y luego carga todos los módulos; no reinicia datos existentes por sí mismo. En esta instalación ya no hay que volver a crear al propietario ni repetir la carga.


Actualización posterior: MDF y sus dependencias fueron retirados por instrucción del usuario. El estado actual es 26 materiales, 22 cotizaciones, 10 pedidos y 27 casos; las cifras de la recarga descrita arriba corresponden al estado anterior a esta retirada. Ver INFORME-ESTADO-ACTUAL.md y VERIFICACION.md.

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
