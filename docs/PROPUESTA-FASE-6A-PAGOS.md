# Propuesta 6A — Pagos de pedidos

> Diseño aprobado por el usuario el 10/11/2026; implementación y resultados en [entrega 6A](FASE-6A.md). Las menciones posteriores de propuesta describen el diseño previo a la aprobación.

10 de noviembre de 2026. Diseño para revisión antes de implementar o modificar MySQL. El usuario confirmó efectivo y QR, pagos editables y entrega permitida con saldo pendiente mostrando una advertencia.

## Diagnóstico del proyecto

Lectura MySQL: base carpinteria_el_tajibo, **29 tablas/25 migraciones**, **11 pedidos** y ausencia de tabla pagos. Los pedidos ya conservan precio_total_acordado DECIMAL(18,2), protegido e independiente de la cotización posterior. No se reconstruirá ese precio desde costos o catálogos.

Ya existen pagos.consultar y pagos.gestionar, asignados solo al propietario; Acceso bloquea el prefijo pagos para maestros incluso si reciben una asignación incompatible. El menú Pagos existe como opción sin ruta. No es necesario crear otros roles ni restablecer permisos.

La entrega actual no exige pago completo. Esta decisión se mantiene: advertencia al propietario, sin bloquear entrega ni cambiar automáticamente estados del pedido.

Diagnóstico privado de solo lectura: storage/app/private/preparacion-pagos-2026-11-10/diagnostico.json. No se ejecutaron migraciones, seeders o escrituras MySQL.

## Flujo sencillo propuesto

1. El propietario abre Pagos o el botón Pagos de un pedido.
2. Consulta precio acordado, total de pagos registrados y saldo según esos registros.
3. Registra **pedido, fecha, importe y medio (Efectivo o QR)**. Referencia de operación y observaciones son opcionales. Si entra desde el pedido, este ya está seleccionado; fecha predeterminada de hoy.
4. Guarda con confirmación SweetAlert2. Se recalcula el saldo en servidor, sin modificar el precio del pedido ni sus estados.
5. Puede Ver y Editar el registro. La corrección guarda valores anteriores/nuevos, responsable y fecha; no borra el historial. Se propone motivo breve obligatorio solo al editar.
6. Al entregar un pedido con saldo, el propietario recibe la advertencia y puede confirmar. El pago pendiente permanece visible para seguimiento.

Ejemplo: pedido Bs 1.500; primer pago Bs 500 → pagado Bs 500/saldo Bs 1.000. Segundo pago Bs 700 → pagado Bs 1.200/saldo Bs 300. Pago final Bs 300 → pagado Bs 1.500/saldo cero.

No exigir seleccionar Anticipo/Parcial/Saldo. Son pagos positivos del mismo pedido; el sistema calcula si está Sin pagos, Pago parcial o Pagado. Evitar porcentajes obligatorios o un número fijo de cuotas.

En los pedidos antiguos no se inventará un anticipo. Cero pagos registrados significa ausencia de registros en el sistema; el saldo calculado no certifica cuánto se cobró previamente fuera del sistema. El propietario podrá registrar pagos anteriores con su fecha.

## Edición propuesta

Se podrán corregir fecha, importe, medio, referencia y observaciones, con motivo. Se propone permitir corregir también el pedido asociado para resolver una imputación equivocada, recalculando los dos saldos y validando el pedido de destino. Es una decisión de diseño presentada para revisión, no una reasignación de datos existentes.

Registro y edición validan importe >0 con dos decimales; no se permite sobrepago. Al editar se excluye el importe anterior del saldo usado para validar el nuevo; no se suma dos veces. Fecha editable para pagos anteriores, sin registrar cobros futuros como recibidos.

No habrá Eliminar ni Anular en esta primera entrega, según la preferencia por edición. No se editarán retrospectivamente las auditorías. Una corrección puede volver a dejar saldo en un pedido entregado: se informa sin revertir su entrega.

## Esquema propuesto

**Una sola tabla nueva, pagos**, con migración individual propuesta 2026_11_10_200000_create_pagos_table.php. Ninguna alteración de tablas o migraciones existentes.

| Campo | Tipo / finalidad |
|---|---|
| id | ID autoincremental habitual; sin código personalizado. |
| pedido_id | FK restrictiva a pedidos, indexada. |
| fecha | DATE, fecha del pago indicada por el propietario. |
| importe | DECIMAL(18,2), positivo. |
| medio | VARCHAR(20), efectivo o qr. |
| referencia | VARCHAR(100) nullable; referencia de QR u otra nota breve de identificación. |
| observaciones | TEXT nullable, validación de longitud. |
| registrado_por_id | FK restrictiva a users; actor tomado de sesión. |
| version | Entero positivo, inicia en 1 para detectar edición obsoleta. |
| clave_registro | UUID único para prevenir reenvíos duplicados. |
| hash_solicitud | CHAR(64), identidad de la solicitud original. |
| created_at / updated_at | Fecha/hora del servidor. |

Relación Pedido hasMany Pago y Pago belongsTo Pedido; relación con usuario registrador y TieneAuditoria para consultar responsables/correcciones. Las modificaciones se registran en auditorias existente, sin crear otra tabla de historial. El motivo de edición se conserva en ese evento.

No guardar pagado/saldo como nuevas columnas de pedidos: calcularlos con suma decimal exacta. No duplicar clientes, precios, pagos ni documentos de la cotización. Los campos técnicos se generan en servidor, no se piden en el formulario.

Si se aprueba y aplica: esquema esperado **30 tablas/26 migraciones**; dos FK nuevas. Sin cambios en usuarios/contraseñas, roles/permisos o datos de otras tablas.

## Código e interfaz propuestos

- PagoController con index/create/store/show/edit/update, siguiendo métodos y estilo escolar.
- Pago, PagoPolicy y PagoService. Form Requests para registro/edición y estructuras que necesitan controlar campos permitidos, versión y UUID.
- Rutas explícitas /admin/pagos y nombres admin.pagos.*, sin sustituir agrupaciones/nombres existentes. No ruta destroy.
- Blade en admin/pagos/index/create/edit/show, formularios con dos o tres columnas, iconos, botones registrar azul/actualizar verde/volver gris y confirmación SweetAlert2; temas claro/oscuro y móvil.
- Conectar el menú Pagos existente y añadir acceso desde Ver pedido. Resumen y advertencia solo para propietario autorizado.
- Integración mínima en la entrega: advertencia de saldo; no cambiar sus restantes validaciones o estados.
- Reutilizar permisos actuales, auditoría y Decimal/Brick Math; sin nuevas dependencias ni tecnologías.

El maestro no verá listados, importes cobrados, saldos, botones o datos de pagos en HTML/JSON, y recibirá 403 por URL/solicitud directa aunque tenga un permiso asignado por error. Precio acordado del pedido mantiene su acceso actual; no se confunde con información de cobros.

## Consistencia y concurrencia

PagoService usará transacciones y bloqueo del pedido antes de sumar/guardar. En una corrección de pedido, bloquear ambos pedidos por ID en orden estable y luego el pago; evitar sobrepagos simultáneos y bloqueos cruzados.

El reenvío idéntico de la creación devuelve el mismo registro, sin sumar ni auditar por duplicado. La clave reutilizada con otros datos se rechaza. Si el pago fue editado después, repetir su alta no revierte la corrección.

Edición con versión: una pantalla obsoleta se rechaza; el usuario debe recargar. El servidor recalcula saldo y no acepta saldo/precio/registrador/roles enviados por el navegador. Guardado y auditoría se confirman juntos o se revierten juntos.

## Aplicación segura a MySQL, sujeta a aprobación

1. Confirmar base correcta, tablas/migraciones, ausencia de pagos y conservación de registros/permisos.
2. Generar respaldo completo nuevo, verificar archivo/hash, restaurarlo en una base aislada y comparar estructura/datos.
3. Ensayar allí únicamente la nueva tabla y el flujo de pagos; comprobar edición, reenvíos, sobrepago concurrente y rollback.
4. Ejecutar pruebas SQLite y navegador aisladas, con las regresiones de las fases anteriores.
5. Aplicar a la base autorizada únicamente la migración aprobada de pagos. No ejecutar migrate:fresh, db:wipe, recargas de otros módulos o cambios destructivos.
6. Comparar datos originales, estructura y migraciones; probar lecturas operativas. Todas las escrituras de prueba se mantienen aisladas.
7. Documentar respaldo, ensayo, resultados y capturas. No crear pagos ficticios automáticamente en MySQL ni suponer cobros históricos.

## Pruebas previstas

Propietario/maestro y permisos manipulados, registro parcial/total, efectivo/QR, fechas/decimales inválidos, importe cero/negativo, sobrepago, edición que libera o aumenta saldo, posible cambio de pedido, versiones obsoletas, reenvíos, rollback de auditoría y concurrencia real MySQL en clon. Entrega con advertencia/saldo y sin bloqueo, sin cambiar precios o estados por registrar pagos. Navegador: claro/oscuro, escritorio/móvil, crear/editar/Ver y conservación de formularios tras errores.

## Fuera del alcance

Sin transferencias bancarias como medio, conexión bancaria, cobro QR automático, emisión fiscal, pasarelas, devoluciones, caja diaria, gastos, ganancias, reportes generales o modificaciones comerciales de pedidos. Este módulo registra pagos ya recibidos; no ejecuta movimientos de dinero.

La propuesta requiere revisión antes de crear tablas en MySQL. Las decisiones confirmadas del usuario ya están incorporadas; las reglas de edición y esquema de este documento son el diseño concreto para aprobar.
