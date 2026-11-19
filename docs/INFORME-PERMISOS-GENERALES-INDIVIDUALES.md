# Implementación de permisos generales e individuales

Fecha: 13 de noviembre de 2026. Alcance autorizado: [plan de aplicación](PLAN-PERMISOS-GENERALES-INDIVIDUALES.md). Estado: implementado, aplicado a MySQL local y verificado.

## Cambios funcionales

Se eliminan las restricciones por nombre de rol para delegar funciones existentes. El propietario puede conceder o retirar cualquier permiso del catálogo, incluidos aprobación de precios, pagos, márgenes, ganancias, reportes y administración de usuarios. Se conservan los controles del negocio y no se crean módulos adicionales.

En Usuarios → Ver permisos se editan las casillas generales de cada rol. En la acción Permisos de cada usuario se elige Según su rol, Permitir o Denegar. La excepción individual prevalece sobre el rol y las asignaciones directas, permanece al modificar el rol y puede retirarse volviendo a heredar. No cambia a otros usuarios ni sus contraseñas.

El propietario conserva cuatro capacidades de recuperación: panel.consultar, usuarios.consultar, usuarios.gestionar y usuarios.asignar_roles. El servidor impide denegárselas individualmente; retirar capacidades del rol no le impide volver a gestionarlas. Las otras capacidades también se le pueden restringir. Se conservan el único propietario, la protección de la cuenta activa y las validaciones de identificación/contraseña.

## Implementación y convenciones

- Controladores y rutas existentes /admin y admin.*, Blade por módulo, AdminLTE/Bootstrap 4 y SweetAlert2.
- PermisoUsuario es un modelo Eloquent sencillo; User::hasPermissionTo resuelve las excepciones y conserva Spatie como base. Acceso, Gate, políticas, servicios, middleware y PDF utilizan esa autorización efectiva.
- PermisosRolService y PermisosUsuarioService validan el catálogo, reautorizan, bloquean los registros y auditan dentro de transacciones. No se modifica vendor ni se añade otra dependencia.
- Las políticas de categorías, clientes, materiales, cotizaciones, pedidos, pagos, casos y usuarios sustituyen los bloqueos por rol por capacidades específicas. PedidoService comprueba la programación también al crear: delegar pedidos.crear no concede responsable/fecha de entrega automáticamente.
- Se mantienen versiones, decimales, aprobación inmutable, validaciones finales de materiales, idempotencia, dependencias antes de eliminar y controles de saldo/sobrepago. No hay descuentos automáticos de existencias.
- Vistas generales e individuales conservan iconos por módulo, encabezados claros, cuadros compactos y botones a la izquierda. No se alteró la barra superior ni el menú de cuenta.

Los nombres anteriores de permisos previstos o heredados se conservan para respetar las asignaciones. presentaciones.gestionar no reactiva el módulo retirado; los nombres inventario.* activos corresponden a existencias sencillas. Véanse [dependencias de permisos](PERMISOS.md).

## Base de datos y respaldo

Se confirmó carpinteria_el_tajibo: 30 tablas, 26 migraciones, dos usuarios, dos roles, 40 permisos, 55 relaciones rol/permiso y cero asignaciones directas. Propietario tenía 40 permisos; maestro, 15. No existía permisos_usuario.

Respaldo completo privado: storage/app/private/permisos-completos-2026-11-13/carpinteria_el_tajibo-62e36167.sql. Incluye datos, estructura, rutinas, eventos y triggers. SHA-256: df6e4c9449ada00843038c4b372d17e0e75f26938282f741c1da6df18625f2f4. Se restauró en tajibo_permisos_20261113_62e36167 y se compararon las 30 tablas, todas las filas y sus estructuras. La comparación de SHOW CREATE normaliza exclusivamente la representación redundante CHARACTER SET utf8mb4 antes de COLLATE utf8mb4_; no modifica columnas ni sus tipos.

Evidencia privada: respaldo-verificado.json y estado-previo.json en el mismo directorio. Las credenciales temporales del cliente MySQL se retiraron al finalizar el respaldo. No se copiaron credenciales escolares.

Migración nueva individual: database/migrations/2026_11_13_100000_create_permisos_usuario_table.php. Crea id automático, user_id, permission_id, permitido y timestamps, unicidad usuario/permiso y dos FK. No modifica las 26 migraciones anteriores.

Se añaden ocho permisos para acciones existentes: clientes.eliminar, materiales.eliminar, categorias.crear, categorias.modificar, categorias.eliminar, pedidos.terminar, pedidos.entregar y pedidos.programar. Solo se conceden al propietario por continuidad. No se ejecuta el seeder general ni se restablece la configuración de roles.

## Ensayo MySQL aislado

Migración y alta incremental ensayadas en el clon restaurado: 31 tablas, 27 migraciones, 48 permisos y 63 relaciones rol/permiso. Propietario: 48; maestro: los mismos 15. Se verificó la conservación exacta de usuarios, contraseñas, registros, relaciones y estructuras anteriores; en las tres tablas ampliadas se comprobó cada fila previa y solo los incrementos previstos.

Nueve comprobaciones aprobadas: consulta heredada inicial, ausencia inicial de finanzas en maestro, denegación sobre el rol, concesión financiera individual, Gate, vuelta a herencia, retiro de concesión, protección de recuperación y ausencia de datos residuales. Las escrituras funcionales/auditorías del ensayo se revirtieron en una transacción. Los contadores automáticos del clon pueden avanzar por inserciones revertidas; no cambian los del origen. Resultado privado: ensayo-mysql.json.

## Pruebas

- Sintaxis: 43 archivos PHP modificados/nuevos, sin errores.
- Comprobación inicial de categorías, usuarios/perfil y auditoría: 33 pruebas y 369 aserciones aprobadas.
- Cobertura específica de delegación, roles, excepciones, PDF y migraciones: 22 pruebas y 297 aserciones aprobadas.
- Primer recorrido general: 286 aprobadas y tres expectativas anteriores pendientes; se actualizaron los dos recuentos de migraciones y la prueba de denegación de márgenes. No se ocultaron errores ni se retiraron casos para obtener el resultado final.
- Edge/Playwright: 25 grupos de comprobación y 18 capturas en SQLite temporal aislado; sin errores JavaScript. Permisos generales: 17 grupos/12 capturas. Individuales: ocho grupos/seis capturas.
- Navegador verifica 48 selectores, guardado y SweetAlert2, ID inválido, concesión de pagos, denegación de consulta heredada, ausencia de autogestión sin delegación, retiro de excepciones y conservación del cliente creado. General permite editar todas las casillas y volver a Usuarios. Sin desborde en 1440, 1024 y 390 px, incluido modo oscuro.

Evidencias: [permisos generales](capturas/permisos-generales-2026-11-13/resultado-browser.json) y [permisos individuales](capturas/permisos-individuales-2026-11-13/resultado-browser.json). Las capturas contienen únicamente datos temporales de pruebas.

## Archivos afectados

Modelo User y nuevo PermisoUsuario; Acceso; PermisoController/PermisoUsuarioController; PermisosRolRequest; PermisosRolService/PermisosUsuarioService; políticas de autorización; comprobación de programación de PedidoService y su parcial de campos; configuraciones permisos/gestion_permisos; vistas de permisos y textos de cotización/casos/perfil que indicaban exclusividad por rol; migración nueva; pruebas de permisos y módulos afectados. Los archivos concretos y sus respaldos están inventariados en storage/app/private/permisos-completos-2026-11-13/archivos-modificados.json y codigo-previo. Se actualiza además la fixture de recurso de cotización de pruebas para usar permisos en vez del nombre del rol.

La documentación vigente se actualiza en AGENTS.md, REQUISITOS.md, PERMISOS.md, ARQUITECTURA.md, CONVENCIONES-CODIGO.md e INFORME-ESTADO-ACTUAL.md. Los informes de fases previas son históricos; la decisión del 13 de noviembre sustituye sus restricciones por rol.

No se modificó el sistema escolar. No se implementaron módulos o fases nuevas.

## Aplicación local y cierre

Se revalidó que el origen conservaba el estado del respaldo. Se ejecutó únicamente la migración nueva y el alta incremental de los ocho permisos/concesiones del propietario. No se usaron migrate:fresh, db:wipe, borrados de tablas/columnas, seeders generales ni restablecimientos de asignaciones. Las 55 relaciones anteriores permanecen y se añaden ocho: total 63. Tabla permisos_usuario vacía, sin reglas individuales inventadas. Estado final: 31 tablas, 27 migraciones, 55 FK, dos usuarios, dos roles y 48 permisos. Propietario 48 y maestro 15. Las filas y estructuras anteriores se verificaron de nuevo y se conservaron.

Suite final completa: **293 pruebas / 2920 aserciones aprobadas**, SQLite aislado. El registro completo está en storage/app/private/permisos-completos-2026-11-13/pruebas-finales.txt.

Verificación local adicional: los controladores y Blade de listado de roles, edición del rol maestro y permisos individuales de ambos roles se renderizan con las cuentas MySQL existentes, mediante proceso CLI autenticado de solo lectura. Se comprobaron los 48 selectores, las 55 FK, la continuidad del propietario y la ausencia de permisos administrativos nuevos en el maestro; hashes de todas las tablas idénticos antes/después de esta comprobación. No se inició una sesión HTTP de navegador con las contraseñas reales: las pruebas de navegación y escritura se realizaron con cuentas temporales aisladas.

Evidencia privada final: aplicacion-mysql.json, estado-aplicar.json y verificacion-local.json. El respaldo SQL y codigo-previo se conservan en el directorio privado descrito. No se necesitan acciones manuales ni ejecutar seeders para activar estos permisos.

## Incidencia de despliegue resuelta

En el primer intento local la migración se aplicó, pero una caché de Spatie compartida con el ensayo contenía el ID 41 de un permiso aún ausente del origen. La clave foránea detuvo el alta; la transacción de permisos se revirtió y permanecieron los 40 permisos/55 relaciones originales. No se eliminó la tabla nueva ni se revirtieron datos del usuario. Se confirmó la causa comparando la búsqueda de caché (41) con la consulta directa MySQL (sin fila).

Se invalidó la caché compartida y los scripts de ensayo/actualización pasaron a usar un almacén array propio desde el arranque, reinicializando PermissionRegistrar. Se restauró de nuevo el mismo respaldo verificado en tajibo_permisos_20261113_f4e7ec05 y se repitieron satisfactoriamente las comprobaciones completas. Antes de continuar el alta en el origen se verificó que la única diferencia era la tabla vacía y su migración autorizadas; todas las tablas y filas previas seguían intactas. Se completaron solo los ocho permisos y concesiones pendientes, sin repetir la migración ni ejecutar seeders. La caché operativa se renovó. Los scripts son privados y no forman parte del flujo normal de la aplicación.

La comprobación CLI de renderizado necesitó aportar ViewErrorBag, que en HTTP incorpora el middleware web; se ajustó únicamente el verificador privado. La aplicación y sus componentes de alertas no se modificaron para esa comprobación.

Después del ajuste de mensajes que todavía indicaban exclusividad del propietario, pasaron otras 75 pruebas y 786 aserciones de cotizaciones, usuarios/perfil, roles y permisos. Los textos ahora corresponden a la delegación autorizada.
