# Diccionario de la base actual — 5 de noviembre de 2026

**Base:** carpinteria_el_tajibo. **Consulta:** 2026-11-05T16:32:27-04:00. **Motor:** MySQL 8.4.7.

Esquema observado directamente mediante consultas de solo lectura. Incluye nombres/tipos/nulabilidad de columnas y relaciones declaradas, sin valores personales, claves ni tokens. Las cantidades de filas incluyen datos ficticios. No es un volcado SQL ni una propuesta de migración. Para reglas, estado de módulos y limitaciones, consultar [el informe general](INFORME-GENERAL-2026-11-05.md).

La tabla migrations la administra Laravel; no tiene un archivo de creación de negocio. La migración de Spatie agrupa sus tablas. Las claves polimórficas de Spatie/auditoría no se describen como FK hacia users si no existe tal restricción SQL. Este listado no enumera todos los índices o CHECK: su ausencia aquí no significa que no existan.

## users

**Módulo:** Usuarios y acceso. **Filas:** 2. **Motor:** InnoDB.

Cuenta, clave cifrada, estado y control de contraseña temporal.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `name` | `varchar(255)` | No |
| `email` | `varchar(255)` | No |
| `password_temporal` | `tinyint(1)` | No |
| `email_verified_at` | `timestamp` | Sí |
| `password` | `varchar(255)` | No |
| `remember_token` | `varchar(100)` | Sí |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |
| `activo` | `tinyint(1)` | No |

Sin claves foráneas declaradas en esta tabla dentro del inventario consultado.

## personal

**Módulo:** Usuarios y perfil. **Filas:** 2. **Motor:** InnoDB.

Identificación y contacto, uno a uno con users.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `user_id` | `bigint unsigned` | No |
| `tipo_identificacion` | `varchar(20)` | Sí |
| `numero_identificacion` | `varchar(30)` | Sí |
| `telefono` | `varchar(50)` | Sí |
| `direccion` | `varchar(255)` | Sí |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `personal.user_id` → `users.id`.

## password_reset_tokens

**Módulo:** Autenticación. **Filas:** 0. **Motor:** InnoDB.

Infraestructura de recuperación de contraseña, condicionada a habilitación.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `email` | `varchar(255)` | No |
| `token` | `varchar(255)` | No |
| `created_at` | `timestamp` | Sí |

Sin claves foráneas declaradas en esta tabla dentro del inventario consultado.

## sessions

**Módulo:** Infraestructura. **Filas:** 0. **Motor:** InnoDB.

Tabla de sesiones disponible; el controlador actual es file.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `varchar(255)` | No |
| `user_id` | `bigint unsigned` | Sí |
| `ip_address` | `varchar(45)` | Sí |
| `user_agent` | `text` | Sí |
| `payload` | `longtext` | No |
| `last_activity` | `int` | No |

Sin claves foráneas declaradas en esta tabla dentro del inventario consultado.

## roles

**Módulo:** Roles/Spatie. **Filas:** 2. **Motor:** InnoDB.

Definiciones propietario y maestro.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `name` | `varchar(255)` | No |
| `guard_name` | `varchar(255)` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Sin claves foráneas declaradas en esta tabla dentro del inventario consultado.

## permissions

**Módulo:** Permisos/Spatie. **Filas:** 38. **Motor:** InnoDB.

Capacidades por acción, incluidas reservas para módulos futuros.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `name` | `varchar(255)` | No |
| `guard_name` | `varchar(255)` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Sin claves foráneas declaradas en esta tabla dentro del inventario consultado.

## model_has_roles

**Módulo:** Roles/Spatie. **Filas:** 2. **Motor:** InnoDB.

Asignación polimórfica de roles a cuentas.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `role_id` | `bigint unsigned` | No |
| `model_type` | `varchar(255)` | No |
| `model_id` | `bigint unsigned` | No |

Relaciones declaradas:

- `model_has_roles.role_id` → `roles.id`.

## model_has_permissions

**Módulo:** Permisos/Spatie. **Filas:** 0. **Motor:** InnoDB.

Permisos directos por modelo, si se conceden.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `permission_id` | `bigint unsigned` | No |
| `model_type` | `varchar(255)` | No |
| `model_id` | `bigint unsigned` | No |

Relaciones declaradas:

- `model_has_permissions.permission_id` → `permissions.id`.

## role_has_permissions

**Módulo:** Permisos/Spatie. **Filas:** 52. **Motor:** InnoDB.

Capacidades asignadas a cada rol.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `permission_id` | `bigint unsigned` | No |
| `role_id` | `bigint unsigned` | No |

Relaciones declaradas:

- `role_has_permissions.permission_id` → `permissions.id`.
- `role_has_permissions.role_id` → `roles.id`.

## categorias

**Módulo:** Categorías. **Filas:** 7. **Motor:** InnoDB.

Clasificación y estado del catálogo.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `nombre` | `varchar(100)` | No |
| `activo` | `tinyint(1)` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Sin claves foráneas declaradas en esta tabla dentro del inventario consultado.

## clientes

**Módulo:** Clientes. **Filas:** 37. **Motor:** InnoDB.

Datos de contacto para aceptación de pedidos.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `nombre` | `varchar(255)` | No |
| `tipo` | `varchar(15)` | No |
| `telefono` | `varchar(40)` | No |
| `email` | `varchar(255)` | Sí |
| `direccion` | `varchar(500)` | Sí |
| `observaciones` | `text` | Sí |
| `activo` | `tinyint(1)` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Sin claves foráneas declaradas en esta tabla dentro del inventario consultado.

## unidades_materiales

**Módulo:** Catálogo. **Filas:** 6. **Motor:** InnoDB.

Unidades configuradas y precisión de cantidades.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `codigo` | `varchar(20)` | No |
| `nombre` | `varchar(60)` | No |
| `fraccionaria` | `tinyint(1)` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Sin claves foráneas declaradas en esta tabla dentro del inventario consultado.

## materiales

**Módulo:** Materiales. **Filas:** 29. **Motor:** InnoDB.

Catálogo, unidad, categoría y costo de referencia.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `nombre` | `varchar(255)` | No |
| `descripcion` | `text` | Sí |
| `categoria` | `varchar(100)` | No |
| `especificaciones` | `text` | Sí |
| `unidad_id` | `bigint unsigned` | No |
| `precio_referencia` | `decimal(18,6)` | No |
| `stock_minimo` | `decimal(18,4)` | Sí |
| `activo` | `tinyint(1)` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |
| `categoria_id` | `bigint unsigned` | Sí |

Relaciones declaradas:

- `materiales.categoria_id` → `categorias.id`.
- `materiales.unidad_id` → `unidades_materiales.id`.

## existencias_materiales

**Módulo:** Existencias. **Filas:** 27. **Motor:** InnoDB.

Un saldo por material.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `material_id` | `bigint unsigned` | No |
| `cantidad` | `decimal(18,4)` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `existencias_materiales.material_id` → `materiales.id`.

## movimientos_materiales

**Módulo:** Existencias. **Filas:** 29. **Motor:** InnoDB.

Historial inmutable de altas/correcciones de cantidades.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `clave` | `char(36)` | No |
| `solicitud_hash` | `char(64)` | No |
| `usuario_id` | `bigint unsigned` | No |
| `motivo` | `text` | No |
| `material_id` | `bigint unsigned` | No |
| `unidad_id` | `bigint unsigned` | No |
| `material_nombre` | `varchar(255)` | No |
| `unidad_codigo` | `varchar(20)` | No |
| `tipo` | `varchar(25)` | No |
| `cantidad` | `decimal(18,4)` | No |
| `existencia_anterior` | `decimal(18,4)` | No |
| `existencia_resultante` | `decimal(18,4)` | No |
| `precio_referencia_historico` | `decimal(18,6)` | No |
| `estado_costo` | `varchar(20)` | No |
| `costo_total` | `decimal(18,2)` | Sí |
| `costo_unitario_base` | `decimal(24,10)` | Sí |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `movimientos_materiales.material_id` → `materiales.id`.
- `movimientos_materiales.unidad_id` → `unidades_materiales.id`.
- `movimientos_materiales.usuario_id` → `users.id`.

## cambios_materiales

**Módulo:** Materiales. **Filas:** 0. **Motor:** InnoDB.

Instantáneas de cambios del catálogo; la tabla está activa aunque esté vacía.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `material_id` | `bigint unsigned` | No |
| `usuario_id` | `bigint unsigned` | No |
| `anterior` | `json` | No |
| `posterior` | `json` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `cambios_materiales.material_id` → `materiales.id`.
- `cambios_materiales.usuario_id` → `users.id`.

## auditorias

**Módulo:** Auditoría transversal. **Filas:** 138. **Motor:** InnoDB.

Eventos, responsables y valores anteriores/nuevos; registra lotes ficticios.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `registro_type` | `varchar(255)` | No |
| `registro_id` | `bigint unsigned` | No |
| `usuario_id` | `bigint unsigned` | Sí |
| `nombre_registro` | `varchar(255)` | No |
| `accion` | `varchar(40)` | No |
| `datos_anteriores` | `json` | Sí |
| `datos_nuevos` | `json` | Sí |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `auditorias.usuario_id` → `users.id`.

## ajustes

**Módulo:** Configuración. **Filas:** 0. **Motor:** InnoDB.

Ficha y logo de la carpintería; moneda fija fuera de esta tabla.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `nombre` | `varchar(255)` | No |
| `descripcion` | `varchar(500)` | Sí |
| `direccion` | `varchar(255)` | No |
| `telefono` | `varchar(50)` | No |
| `email` | `varchar(255)` | No |
| `web` | `varchar(255)` | Sí |
| `logo` | `varchar(255)` | Sí |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Sin claves foráneas declaradas en esta tabla dentro del inventario consultado.

## tipos_mueble

**Módulo:** Cotizaciones. **Filas:** 6. **Motor:** InnoDB.

Seis tipos base de mueble.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `codigo` | `varchar(30)` | No |
| `nombre` | `varchar(80)` | No |
| `activo` | `tinyint(1)` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Sin claves foráneas declaradas en esta tabla dentro del inventario consultado.

## cotizaciones

**Módulo:** Cotizaciones. **Filas:** 26. **Motor:** InnoDB.

Cabecera, costos calculados, estado y aprobación.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `clave_registro` | `char(36)` | No |
| `solicitud_hash` | `char(64)` | No |
| `cliente_id` | `bigint unsigned` | Sí |
| `responsable_id` | `bigint unsigned` | No |
| `fecha` | `date` | No |
| `estado` | `varchar(20)` | No |
| `version` | `int unsigned` | No |
| `observaciones` | `text` | Sí |
| `condiciones` | `text` | Sí |
| `costo_materiales` | `decimal(18,2)` | Sí |
| `costo_mano_obra` | `decimal(18,2)` | No |
| `otros_costos` | `decimal(18,2)` | No |
| `costo_total` | `decimal(18,2)` | Sí |
| `recargo_porcentaje` | `decimal(9,4)` | Sí |
| `precio_sugerido` | `decimal(18,2)` | Sí |
| `precio_final` | `decimal(18,2)` | Sí |
| `validado_por_id` | `bigint unsigned` | Sí |
| `validado_at` | `timestamp` | Sí |
| `aprobado_por_id` | `bigint unsigned` | Sí |
| `aprobado_at` | `timestamp` | Sí |
| `datos_cliente_aprobados` | `json` | Sí |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `cotizaciones.aprobado_por_id` → `users.id`.
- `cotizaciones.cliente_id` → `clientes.id`.
- `cotizaciones.responsable_id` → `users.id`.
- `cotizaciones.validado_por_id` → `users.id`.

## detalles_cotizacion

**Módulo:** Cotizaciones. **Filas:** 27. **Motor:** InnoDB.

Un tipo de mueble por detalle: medidas, cantidad, características y costos.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `cotizacion_id` | `bigint unsigned` | No |
| `posicion` | `int unsigned` | No |
| `tipo_mueble_id` | `bigint unsigned` | No |
| `material_principal_id` | `bigint unsigned` | No |
| `tipo_nombre` | `varchar(80)` | No |
| `material_principal_nombre` | `varchar(255)` | No |
| `largo_cm` | `decimal(14,4)` | No |
| `ancho_cm` | `decimal(14,4)` | No |
| `alto_cm` | `decimal(14,4)` | No |
| `puertas` | `int unsigned` | No |
| `cajones` | `int unsigned` | No |
| `divisiones` | `int unsigned` | No |
| `acabado` | `varchar(150)` | No |
| `cantidad_muebles` | `int unsigned` | No |
| `observaciones` | `text` | Sí |
| `mano_obra_unitaria` | `decimal(18,2)` | No |
| `mano_obra_total_nominal` | `decimal(18,2)` | No |
| `mano_obra_total_aplicada` | `decimal(18,2)` | No |
| `motivo_ajuste_mano_obra` | `text` | Sí |
| `ajustado_por_id` | `bigint unsigned` | Sí |
| `ajustado_at` | `timestamp` | Sí |
| `otros_costos_total` | `decimal(18,2)` | No |
| `costo_materiales_total` | `decimal(18,2)` | Sí |
| `costo_total` | `decimal(18,2)` | Sí |
| `precio_propuesto` | `decimal(18,2)` | Sí |
| `precio_sugerido` | `decimal(18,2)` | Sí |
| `precio_final` | `decimal(18,2)` | Sí |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `detalles_cotizacion.ajustado_por_id` → `users.id`.
- `detalles_cotizacion.cotizacion_id` → `cotizaciones.id`.
- `detalles_cotizacion.material_principal_id` → `materiales.id`.
- `detalles_cotizacion.tipo_mueble_id` → `tipos_mueble.id`.

## materiales_cotizacion

**Módulo:** Cotizaciones. **Filas:** 75. **Motor:** InnoDB.

Materiales/costos por detalle, cantidades nominales/aplicadas y ajustes.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `detalle_cotizacion_id` | `bigint unsigned` | No |
| `posicion` | `int unsigned` | No |
| `material_id` | `bigint unsigned` | No |
| `unidad_id` | `bigint unsigned` | No |
| `material_nombre` | `varchar(255)` | No |
| `unidad_nombre` | `varchar(60)` | No |
| `componente` | `varchar(100)` | No |
| `cantidad_unitaria` | `decimal(18,4)` | No |
| `cantidad_total_nominal` | `decimal(18,4)` | No |
| `cantidad_total_aplicada` | `decimal(18,4)` | No |
| `precio_referencia_capturado` | `decimal(18,6)` | No |
| `costo_unitario_estimado` | `decimal(18,6)` | Sí |
| `estado_costo` | `varchar(20)` | No |
| `fuente_costo` | `varchar(30)` | No |
| `motivo_gratuidad` | `text` | Sí |
| `motivo_ajuste_cantidad` | `text` | Sí |
| `ajustado_por_id` | `bigint unsigned` | Sí |
| `ajustado_at` | `timestamp` | Sí |
| `subtotal_nominal` | `decimal(18,2)` | Sí |
| `subtotal_aplicado` | `decimal(18,2)` | Sí |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `materiales_cotizacion.ajustado_por_id` → `users.id`.
- `materiales_cotizacion.detalle_cotizacion_id` → `detalles_cotizacion.id`.
- `materiales_cotizacion.material_id` → `materiales.id`.
- `materiales_cotizacion.unidad_id` → `unidades_materiales.id`.

## pedidos

**Módulo:** Pedidos. **Filas:** 11. **Motor:** InnoDB.

Aceptación del cliente, acuerdo comercial y estado operativo.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `cotizacion_id` | `bigint unsigned` | No |
| `cliente_id` | `bigint unsigned` | No |
| `datos_cliente_acordados` | `json` | No |
| `fecha_aceptacion` | `date` | No |
| `fecha_prevista_entrega` | `date` | Sí |
| `responsable_id` | `bigint unsigned` | Sí |
| `registrado_por_id` | `bigint unsigned` | No |
| `estado` | `varchar(30)` | No |
| `version` | `int unsigned` | No |
| `observaciones` | `text` | Sí |
| `precio_total_acordado` | `decimal(18,2)` | No |
| `clave_registro` | `char(36)` | No |
| `hash_solicitud` | `varchar(64)` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `pedidos.cliente_id` → `clientes.id`.
- `pedidos.cotizacion_id` → `cotizaciones.id`.
- `pedidos.registrado_por_id` → `users.id`.
- `pedidos.responsable_id` → `users.id`.

## detalles_pedido

**Módulo:** Pedidos. **Filas:** 12. **Motor:** InnoDB.

Detalle cotizado de origen, cantidad y precio acordado.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `pedido_id` | `bigint unsigned` | No |
| `detalle_cotizacion_id` | `bigint unsigned` | No |
| `posicion` | `int unsigned` | No |
| `cantidad_muebles` | `int unsigned` | No |
| `precio_acordado` | `decimal(18,2)` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `detalles_pedido.detalle_cotizacion_id` → `detalles_cotizacion.id`.
- `detalles_pedido.pedido_id` → `pedidos.id`.

## materiales_pedido

**Módulo:** Planificación. **Filas:** 31. **Motor:** InnoDB.

Cantidades iniciales/planificadas por detalle; sin consumo real todavía.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `detalle_pedido_id` | `bigint unsigned` | No |
| `material_cotizacion_id` | `bigint unsigned` | Sí |
| `material_id` | `bigint unsigned` | No |
| `unidad_id` | `bigint unsigned` | No |
| `material_nombre` | `varchar(255)` | No |
| `unidad_nombre` | `varchar(60)` | No |
| `componente` | `varchar(100)` | No |
| `cantidad_inicial` | `decimal(18,4)` | Sí |
| `cantidad_planificada` | `decimal(18,4)` | No |
| `observaciones` | `text` | Sí |
| `actualizado_por_id` | `bigint unsigned` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `materiales_pedido.actualizado_por_id` → `users.id`.
- `materiales_pedido.detalle_pedido_id` → `detalles_pedido.id`.
- `materiales_pedido.material_cotizacion_id` → `materiales_cotizacion.id`.
- `materiales_pedido.material_id` → `materiales.id`.
- `materiales_pedido.unidad_id` → `unidades_materiales.id`.

## estados_pedido

**Módulo:** Pedidos. **Filas:** 18. **Motor:** InnoDB.

Eventos de cambio de estado, actor, fecha e idempotencia.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `bigint unsigned` | No |
| `pedido_id` | `bigint unsigned` | No |
| `estado_anterior` | `varchar(30)` | Sí |
| `estado_nuevo` | `varchar(30)` | No |
| `usuario_id` | `bigint unsigned` | No |
| `ocurrido_at` | `timestamp` | No |
| `motivo` | `text` | Sí |
| `version_resultante` | `int unsigned` | No |
| `clave_operacion` | `char(36)` | No |
| `hash_solicitud` | `varchar(64)` | No |
| `created_at` | `timestamp` | Sí |
| `updated_at` | `timestamp` | Sí |

Relaciones declaradas:

- `estados_pedido.pedido_id` → `pedidos.id`.
- `estados_pedido.usuario_id` → `users.id`.

## migrations

**Módulo:** Infraestructura Laravel. **Filas:** 21. **Motor:** InnoDB.

Historial de migraciones aplicadas y lote.

| Columna | Tipo MySQL | Admite NULL |
|---|---|---|
| `id` | `int unsigned` | No |
| `migration` | `varchar(255)` | No |
| `batch` | `int` | No |

Sin claves foráneas declaradas en esta tabla dentro del inventario consultado.
