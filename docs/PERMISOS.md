# Permisos generales e individuales

Decisiones autorizadas del 13 y 14 de noviembre de 2026. Sustituye las restricciones anteriores por nombre de rol y la delegación limitada a cuatro operaciones. No incorpora módulos nuevos.

## Uso desde Usuarios

- **Ver permisos** abre los permisos generales de Propietario y Maestro carpintero. Marcar concede y desmarcar retira el permiso del rol.
- **Permisos** en la fila de una persona abre todas sus decisiones individuales, agrupadas por módulo.
- **Casilla marcada:** permiso disponible. Marcar una casilla concede esa capacidad individualmente.
- **Casilla desmarcada:** permiso no disponible. Desmarcar una casilla lo deniega individualmente, aunque el rol lo conceda.
- **Usar permisos del rol:** vuelve a la configuración de base; después hay que guardar.

Las casillas que no se modifican conservan su regla anterior; guardar no fija todos los permisos heredados como excepciones. Una excepción individual sigue prevaleciendo si posteriormente cambia el rol. El grupo Usuarios está bloqueado: activado para propietario y desactivado para maestro; el acceso de recuperación del propietario al Panel también permanece protegido. La vista usa los mismos cuadros compactos, iconos y casillas de los permisos generales, sin selectores ni explicaciones repetidas. Conceder un permiso administrativo no cambia el nombre del rol.

El propietario puede personalizar las capacidades de los demás módulos, incluidos aprobar precios, pagos, márgenes y ganancias. Por decisión del 14/11/2026, Usuarios y la administración de roles/permisos quedan reservados al propietario: no se pueden delegar por rol ni por persona. Sus cinco casillas están bloqueadas en ambas pantallas. El maestro conserva sus permisos operativos y puede recibir otros que estén permitidos.

## Prioridad

1. Cuenta activa y acceso autenticado a la operación.
2. Usuarios (usuarios.*): permitido únicamente al propietario, sin excepciones individuales ni asignaciones de Spatie que puedan cambiarlo.
3. El propietario conserva panel.consultar como acceso de recuperación.
4. Para los demás módulos, una excepción individual manda sobre el rol.
5. Sin excepción, resultado original de Spatie.
6. Sin concesión, acceso rechazado.

El acceso de recuperación no concede pagos ni aprobación automáticamente. El servidor rechaza una solicitud que intente denegarlo al propietario. Se conservan las reglas de único propietario y protección del último propietario activo. Mi perfil no permite cambiar rol, estado ni permisos.

## Dependencias de las operaciones

Conceder una acción no omite sus permisos de consulta ni sus validaciones. Para utilizar los módulos desde el panel se conserva panel.consultar.

| Operación | Capacidades y controles conservados |
|---|---|
| Aprobar cotización | cotizaciones.aprobar, cotizaciones.validar y costos.consultar; consulta para abrir la ficha. Versión actual, todos los detalles y costos conocidos; una aprobada sigue inmutable. |
| Consultar pagos | pagos.consultar y pedidos.consultar. |
| Registrar/editar pagos | Los anteriores y pagos.gestionar; importe, saldo, versión, motivo de edición y auditoría. |
| Reportes | reportes.administrativos y consulta del origen; pagos/saldos necesitan pagos.consultar y pedidos.consultar. PDF aplica los mismos controles. |
| Usuarios y permisos | Exclusivo del propietario; las cinco capacidades usuarios.* quedan fijas y bloqueadas en las pantallas generales e individuales. |
| Programar pedido | pedidos.programar y permisos del flujo de creación/edición; no concede finalizar o entregar. |
| Terminar fabricación | pedidos.terminar, consulta/modificación de pedidos, planificación y cotizaciones.validar, además de los datos finales completos y revisados. |
| Entregar | pedidos.entregar, consulta/modificación de pedidos y estado Terminado con validaciones conservadas. Saldo pendiente advierte sin bloquear. |
| Corregir existencias | materiales.modificar, inventario.gestionar e inventario.ajustes.crear; material activo, cantidades/motivo, concurrencia e idempotencia. |
| Eliminar catálogo | clientes.eliminar, materiales.eliminar o categorias.eliminar, según registro; se mantienen sus comprobaciones de dependencias. |
| Revisar precio de un caso | casos.consultar, costos.consultar y casos.validar_precios. |

Los permisos conservan sus nombres para no romper rutas/asignaciones anteriores. inventario.* representa las existencias sencillas actuales. presentaciones.gestionar y otras capacidades previstas se conservan en el catálogo; asignarlas no crea funcionalidades retiradas o pendientes. Ganancias contables continúa fuera del alcance.

## Código y datos

Spatie mantiene roles, permissions y sus tablas intermedias. La nueva tabla permisos_usuario guarda id automático, user_id, permission_id, permitido y timestamps; combinación usuario/permiso única y dos claves foráneas. No se mueve información de users ni se eliminan asignaciones anteriores.

User::hasPermissionTo resuelve las excepciones; Acceso utiliza la misma comprobación. Gate, @can, middleware, políticas, servicios y PDF respetan el resultado efectivo. No se modifica vendor ni se agrega una autorización global del propietario a todas las operaciones.

PermisosRolService y PermisosUsuarioService reautorizan al actor, validan catálogo/decisiones, usan transacciones y registran antes/después con AuditoriaService. Si falla la auditoría se revierte la modificación. El destinatario se bloquea para serializar la edición individual.

El catálogo añade ocho nombres específicos: clientes.eliminar, materiales.eliminar, categorias.crear, categorias.modificar, categorias.eliminar, pedidos.terminar, pedidos.entregar y pedidos.programar. Evitan que delegar Modificar conceda operaciones adicionales. Los recibe inicialmente solo el propietario para conservar lo que ya podía hacer.

Aplicación y pruebas: [informe de implementación](INFORME-PERMISOS-GENERALES-INDIVIDUALES.md). La tabla y permisos se instalan en MySQL únicamente después de respaldo restaurado y ensayo aislado; los scripts privados no son instaladores.

## Bloqueo de Usuarios — 14/11/2026

Las cinco capacidades reservadas son usuarios.consultar, usuarios.crear, usuarios.modificar, usuarios.gestionar y usuarios.asignar_roles. El propietario las conserva activas y el maestro no puede obtenerlas, aunque exista una asignación antigua positiva por rol, directa o individual. Una cuenta inactiva sigue sin acceso. Las solicitudes de editar el rol que intenten conceder Usuarios al maestro o retirar esas capacidades del propietario se rechazan; las decisiones individuales permitir/denegar sobre Usuarios también se rechazan. La opción heredar no crea una excepción a la reserva.

Guardar otros permisos continúa permitido. Las casillas bloqueadas mantienen su estado también al usar Usar permisos del rol; el maestro no recibe Usuarios por ese botón. No se eliminan asignaciones antiguas automáticamente ni se ejecutan migraciones, recargas de seeders o escrituras de prueba en MySQL. La autorización efectiva deja de aceptarlas para este grupo. Si se guarda después un rol o una ficha individual, el servicio conserva las reglas fijas y registra sus cambios mediante la auditoría existente. Esta decisión sustituye únicamente la delegación de Usuarios que se había aprobado el 13/11.


## Tipos de muebles — 14/11/2026

TipoMueblePolicy reutiliza cotizaciones.consultar para ver listado/ficha y configuracion.gestionar, junto con la consulta anterior, para registrar/editar/activar/desactivar. Todos los endpoints requieren autenticación, panel.consultar y cotizaciones.consultar; las escrituras y formularios se autorizan mediante política en controlador. No hay DELETE, permisos nuevos ni asignaciones modificadas. Por defecto propietario administra y maestro consulta; una concesión o denegación individual de Configuración conserva prioridad mediante Acceso/Spatie/User. Esta entrega no crea un grupo separado en la pantalla de permisos.
