# Plan de permisos generales e individuales — 13 de noviembre de 2026

Estado: plan aprobado por el usuario, implementado y aplicado el 13 de noviembre de 2026. Los apartados siguientes conservan el diagnóstico y la estrategia aprobados; resultados finales en [informe de implementación](INFORME-PERMISOS-GENERALES-INDIVIDUALES.md).

## Decisiones confirmadas

El propietario puede conceder o retirar cualquier permiso disponible, también pagos, ganancias, aprobación de precios y administración de usuarios. Una decisión individual prevalece sobre el rol. Esta decisión sustituye las restricciones históricas del maestro; no autoriza omitir validaciones de negocio, alterar importes o romper la consistencia de datos.

## Diagnóstico de solo lectura

Conexión verificada a MySQL, base carpinteria_el_tajibo: 26 migraciones registradas, 2 usuarios, 2 roles, 40 permisos, 55 relaciones entre roles y permisos y ninguna asignación directa a usuarios. Propietario: 1 usuario/40 permisos. Maestro: 1 usuario/15 permisos. No existe permisos_usuario. No se modificó MySQL.

Los bloqueos actuales están en Acceso y en políticas de cotizaciones, pagos, pedidos, categorías, clientes, materiales y casos. PedidoService también restringe fecha prevista/responsable por rol. La interfaz mantiene permisos obligatorios y solo ofrece cuatro adicionales individuales. Cambiar únicamente las casillas no bastaría.

## Estructura aditiva propuesta

Una tabla nueva: permisos_usuario. Campos: id autoincremental, user_id (FK users.id), permission_id (FK permissions.id), permitido booleano, created_at y updated_at. Índice único user_id/permission_id. Las dos FK usan eliminación en cascada únicamente de la excepción si algún día se elimina su usuario/permiso; las auditorías previas permanecen. Sin columnas nuevas en users ni modificaciones de las migraciones ya aplicadas.

- Sin fila: usar el resultado de los roles/permisos de Spatie existentes.
- permitido = 1: permitir individualmente.
- permitido = 0: denegar individualmente, aunque el rol o una asignación directa lo concedan.

Spatie conserva sus tablas y asignaciones. La tabla nueva guarda solo excepciones explícitas y no reemplaza roles. Volver a Según su rol elimina exclusivamente esa excepción. El borrador original se conservó en el archivo privado de preparación. La migración aprobada activa es database/migrations/2026_11_13_100000_create_permisos_usuario_table.php; las instalaciones nuevas también la incluyen.

## Catálogo y operaciones

Se conservan los 40 nombres actuales. Para no conceder acciones adicionales accidentalmente con Modificar al retirar los bloqueos por rol, se proponen 8 permisos específicos para operaciones ya existentes:

| Permiso | Operación existente |
|---|---|
| clientes.eliminar | Eliminar clientes con las comprobaciones de relaciones actuales |
| materiales.eliminar | Eliminar materiales con las comprobaciones de relaciones actuales |
| categorias.crear | Registrar categorías |
| categorias.modificar | Editar categorías |
| categorias.eliminar | Eliminar categorías con sus restricciones de datos actuales |
| pedidos.terminar | Terminar fabricación, manteniendo las validaciones de cierre |
| pedidos.entregar | Registrar entrega, manteniendo los controles de saldo/estado |
| pedidos.programar | Modificar responsable y fecha de entrega prevista |

Son permisos de operaciones implementadas, no módulos o flujos nuevos. El catálogo pasaría de 40 a 48. El propietario recibe los ocho para conservar las facultades que ya tiene; el rol maestro conserva sus 15 permisos y no recibe facultades nuevas automáticamente. No se ejecutará PermisosSeeder para restablecer las asignaciones existentes.

Las dependencias entre permisos (por ejemplo, consultar pedidos para operar sobre ellos), el estado de fabricación, la inmutabilidad de cotizaciones aprobadas, los decimales, las transacciones y las validaciones de cierre se conservan. Tampoco se habilita la creación de otro propietario ni se permite desactivar/eliminar al único propietario activo.

## Prioridad de autorización

1. Cuenta autenticada y activa.
2. El propietario mantiene acceso de recuperación a Panel y gestión de permisos: panel.consultar, usuarios.consultar, usuarios.gestionar y usuarios.asignar_roles. Esta protección conserva su capacidad de corregir configuraciones; no concede automáticamente los demás permisos.
3. Si existe una excepción individual, su decisión manda.
4. Sin excepción, se usan los permisos de Spatie existentes.
5. Sin concesión, se rechaza.

La comprobación debe unificarse en User/Acceso y Gate, además de las políticas, para que una denegación funcione también en middleware, Blade, servicios y PDF. No modificar vendor. Autorizar administración de usuarios/roles se basará en sus permisos, sin cambiar el nombre del rol ni convertir a un maestro en propietario. Un usuario sin permisos de administración no podrá autorizarse por su cuenta.

## Vistas y archivos previstos

- General: casillas para conceder/retirar permisos del rol, sin obligatorios del maestro ni exclusiones financieras por nombre de rol.
- Individual: todos los permisos agrupados por módulo con selector Según su rol / Permitir / Denegar. Se muestra el resultado efectivo y se distinguen las excepciones. Protección visible del acceso de recuperación del propietario.
- Se conservan los encabezados, iconos por módulo, cuadros compactos y botones a la izquierda aprobados.
- Modelo PermisoUsuario y una migración de creación por tabla.
- User, Acceso, servicios de permisos, controladores/requests y sus vistas se adaptan para resolver excepciones y validar el catálogo completo.
- Políticas de cada operación sustituyen los bloqueos por rol por los permisos correspondientes. PedidoService conserva su lógica y consulta pedidos.programar donde ahora comprueba el rol.
- Auditoría transaccional del antes/después y actor; no registrar contraseñas. Validación del servidor de todos los nombres/IDs y decisiones; rechazo de permisos inexistentes o de otro guard.

## Ensayo realizado

Se ejecutó exclusivamente SQLite :memory: con el esquema actual más el borrador. Resultado: 10 comprobaciones aprobadas: tabla y seis columnas, FK activas, ID automático, almacenamiento de permitir/denegar, unicidad, rechazo de usuario/permiso inexistente, vuelta a herencia y conservación de usuario/catálogo al eliminar una excepción.

Resultado guardado en storage/app/private/preparacion-permisos-2026-11-13/resultado-estructura.json. Es una prueba de estructura; no acredita todavía la autorización funcional ni un ensayo MySQL.

## Aplicación después de aprobar

1. Revalidar base, esquema, usuarios y asignaciones frente al diagnóstico; detener si hay diferencias no previstas.
2. Generar respaldo completo nuevo y restaurarlo en una copia aislada; comparar datos y estructura.
3. Implementar y probar reglas, servicios, Gate, políticas, menús, vistas y permisos específicos en bases aisladas. Cubrir permitir, denegar, heredar, conflictos rol/usuario, cambios posteriores del rol, revocación, autoescalamiento, operaciones financieras y rollback de auditoría.
4. Ensayar la migración y los ocho permisos en el clon MySQL, verificando que las 55 asignaciones actuales y los usuarios/contraseñas se conservan; solo se agregan las nuevas concesiones de continuidad del propietario.
5. Aplicar únicamente la nueva tabla y esos permisos al MySQL local, tras aprobación y ensayo correctos, sin reiniciar ni recargar datos. Registrar la migración cuando su estructura quede comprobada.
6. Verificar tablas/filas/FK, pruebas funcionales y navegador con propietario y maestro. Las pruebas de escritura se ejecutan en bases aisladas.

No se usará migrate:fresh, db:wipe, eliminación de tablas anteriores, sustitución de hashes ni reasignaciones masivas. No se modificará el proyecto escolar.
