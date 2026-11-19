# Fase 4B — pedidos y planificación sencilla

Entrega: **5 de noviembre de 2026**, America/La_Paz. Basada en la [propuesta aprobada](ARCHIVO-HISTORICO.md). Aplicación independiente: C:/wamp64/www/carpinteria-el-tajibo; el sistema escolar no se modificó.

## Funcionalidades entregadas

- Cotización aprobada → Crear pedido: cliente existente o alta nueva con nombre, tipo y teléfono; confirmación de aceptación. Entrega prevista, responsable y observaciones opcionales.
- Transacción única para cliente nuevo, pedido, detalles, materiales y estado inicial. UUID/huella e índice único cotizacion_id impiden duplicados. Un reenvío compatible vuelve al mismo pedido; solicitudes incompatibles se rechazan.
- Pedidos tiene listado con filtros/paginación, Crear desde cotización, Ver y Editar. Datos del cliente al aceptar congelados; no se cambia la cotización aprobada ni se revalora el acuerdo con precios nuevos.
- Precio del detalle completo copiado exactamente; total acordado como suma de detalles. No se multiplica otra vez por cantidad de muebles.
- Planificación inicial automática por mueble desde cantidad_total_aplicada. Se puede corregir cantidad, anotar, añadir material o dejar 0 para retirar una necesidad. Se conserva cantidad_inicial, los valores anteriores/nuevos y el motivo/autor. Un material nuevo tiene cantidad inicial NULL.
- Resumen por pedido agrupado por material/unidad: necesario, existencia registrada y por comprar = max(necesario − existencia, 0). Por ejemplo 3 + 5 necesarios, 5 registrados → 3 por comprar. Sin reserva de saldo ni promesa de disponibilidad frente a otros pedidos.
- Inicio de fabricación por propietario/maestro autorizado, con confirmación SweetAlert2, control de versión y evento inmutable. **No descuenta existencias**.
- Los cambios administrativos de entrega/responsable solo corresponden al propietario; maestros actualizan observaciones operativas y planificación. Precios/cliente acordados no son editables desde ese flujo.
- El cambio de unidad y la eliminación de materiales/clientes ya vinculados se protegen mediante reglas/FK. Los materiales inactivos históricos se conservan; las altas nuevas de planificación requieren material activo.

## Alcance protegido

Solo Aceptado → En fabricación está habilitado. No hay cierre Terminado/Entregado sin la futura revisión final 4D. No se implementan pagos, anticipos/saldos, consumos reales, rechazo de cotizaciones, revisiones comerciales, pedidos parciales, reservas, compras/despachos/devoluciones, casos históricos, CBR/k-NN ni casos simulados operativos. El requisito futuro de incorporar un caso al terminar validado antes de entregar se conserva, sin generar registros falsos ahora.

## Estructura y archivos

| Área | Archivos/organización |
|---|---|
| Controladores nuevos | PedidoController (index/create/store/show/edit/update/cambiarEstado); PlanificacionMaterialController (index/show/edit/update). |
| Modelos nuevos | Pedido, DetallePedido, MaterialPedido y EstadoPedido; table, casts decimales y relaciones explícitas. EstadoPedido reutiliza RegistroInmutable. |
| Requests nuevos | app/Http/Requests/Pedidos: CrearPedidoRequest, ActualizarPedidoRequest, CambiarEstadoPedidoRequest, GuardarPlanificacionRequest. |
| Servicios nuevos | PedidoService y PlanificacionMaterialService: reglas repetidas para invocación directa, transacciones/bloqueos, control de versión y auditoría. |
| Políticas nuevas | PedidoPolicy y PlanificacionMaterialPolicy. |
| Vistas nuevas | admin/pedidos: index/create/edit/show/_datos; admin/planificacion: index/show/edit/_fila. Formularios con columnas, componentes habituales, errores y colores actuales. |
| Integración limitada | AppServiceProvider, Acceso, CatalogoService, relaciones de Cotizacion/Cliente, rutas individuales, navegación, config/permisos, etiqueta de auditoría y ficha de cotización (Crear pedido/Ver pedido). |
| Frontend | resources/js/pedidos.js importado desde app.js; compilación Vite. Sin nuevas dependencias. |
| Permiso incremental | database/seeders/FaseCuatroBPermisosSeeder.php: añade pedidos.crear al propietario y audita como proceso del sistema; no usa syncPermissions. |
| Pruebas nuevas | PedidosPlanificacionTest.php, tests/browser/fase4b.cjs, tests/mysql/pedidos-worker.php. Auxiliares de respaldo/ensayo/aplicación privados. |
| Regresión adaptada | ConsolidacionMigracionesTest (21 migraciones y reversión en orden); FaseCuatroATest (conteo); FaseDosTest (Pedidos ya existe); preparación SQLite de navegador incluye las cuatro creaciones. |

Se mantienen rutas individuales /admin/pedidos y /admin/planificacion con nombres admin.pedidos.* y admin.planificacion.*; POST /create para alta, PUT para edición. No se renombraron rutas existentes, ni se editó vendor/node_modules ni se añadieron paquetes.

## Migraciones y estado final

Aplicadas exclusivamente en carpinteria_el_tajibo, lote **8**:

1. 2026_11_05_200000_create_pedidos_table.php
2. 2026_11_05_200001_create_detalles_pedido_table.php
3. 2026_11_05_200002_create_materiales_pedido_table.php
4. 2026_11_05_200003_create_estados_pedido_table.php

Antes: 17 registradas / 23 tablas. Después: **21 registradas / 27 tablas**. FK restrictivas, índices únicos por cotización/origen/posición/versión y UUID. Cantidades decimal(18,4), importes decimal(18,2); las reglas de unidad/estado se aplican en servidor. No se afirman triggers de inmutabilidad ni restricciones SQL para todas las reglas de negocio.

Las cuatro tablas nuevas permanecen **vacías en MySQL local**. Se verificó conservación por huellas de usuarios, contraseñas, clientes, materiales, saldos, movimientos y otras tablas previas. Las filas anteriores de migrations, permissions, role_has_permissions y auditorias también se compararon individualmente: solo se añadieron cuatro migraciones, un permiso, su asociación al propietario y una auditoría. No se resembraron roles/usuarios ni se eliminó ninguna tabla/columna.

## Respaldos y ensayo

Directorio privado: storage/app/private/fase4b-1793894364373. Los SQL contienen datos privados: no publicarlos ni incluirlos en una entrega compartida.

- Respaldo inicial completo: antes-4b.sql. SHA-256 efe4002ba2e4c27d7dd1c666ae17006fcf4726a86367db44a76574c0c2fcb6d0.
- Restauración idéntica y ensayo: carpinteria_el_tajibo_ensayo4b_20261105120913_2335d0. Se conservaron las 23 tablas previas antes de probar las cuatro creaciones. Las fixtures y solicitudes concurrentes se ejecutaron solo en esa copia.
- Respaldo inmediato: inmediato-4b.sql. SHA-256 **b2c7f2f6c7e3c00968b605d89d98f91c90e7b4a4e4fed26d077f81d73a2d1b0d**.
- Segunda restauración idéntica: carpinteria_el_tajibo_respaldo4b_20261105121654_df32e0.
- Aplicación con ventana breve de mantenimiento, relectura de huellas y comparación del esquema con el ensayo. La aplicación volvió a estar disponible al terminar.
- Evidencias: [aplicación](ARCHIVO-HISTORICO.md), [MySQL aislado](ARCHIVO-HISTORICO.md), [consulta local sin escrituras](ARCHIVO-HISTORICO.md).

No ejecutar de nuevo los auxiliares privados. No se utilizaron migrate:fresh, db:wipe, borrados ni renumeración de registros. Se aplicaron solo las rutas de las cuatro migraciones nuevas.

## Pruebas ejecutadas

- **144 pruebas PHP / 1.375 aserciones aprobadas**, SQLite en memoria, 119,42 segundos. Doce pruebas nuevas de pedidos/planificación incluyen permisos, rollback con fallo de auditoría, doble envío, copia de cantidades/precios, snapshot de cliente, inyección de campos, estados protegidos, versionado, disponibilidad agrupada y protección de unidades y denegación de consulta de clientes cuando se retira ese permiso. Se mantuvo la suite anterior.
- **8 verificaciones MySQL aisladas aprobadas**, incluyendo dos aceptaciones simultáneas y dos inicios simultáneos. Dos aceptaciones generan un pedido; dos inicios con la misma versión permiten uno y rechazan el otro.
- El ensayo detectó una lectura obsoleta bajo REPEATABLE READ: se corrigieron las consultas de existencia/idempotencia con lockForUpdate después de bloquear la cotización/pedido y se repitió el ensayo con resultado aprobado. No fue una alteración de datos locales.
- Navegador: recorrido de propietario/maestro, aceptación, selección de cliente, planificación, material adicional, edición, aprobación/PDF anteriores, SweetAlert2 y pantallas de 1440/1024/390 px. 55 comprobaciones aprobadas. Evidencia en [reporte](capturas/fase4b/resultado-browser.json).
- Consultas locales GET de Pedidos y Planificación, propietario/maestro: **cuatro HTTP 200**, con sesiones/caché en memoria y huellas de MySQL idénticas antes/después.
- npm run build aprobado. Pint aplicado a los PHP nuevos. Revisión visual de alta de pedido en escritorio y ficha de maestro en móvil.

El navegador y PHP usan datos ficticios aislados. No se crearon pedidos reales para llenar las pantallas, ni se presentaron pruebas como trabajos reales de la carpintería.

## Capturas

- [Crear pedido](capturas/fase4b/pedido-create-1440.png) y [móvil](capturas/fase4b/pedido-create-390.png).
- [Planificación](capturas/fase4b/planificacion-1440.png) y [móvil](capturas/fase4b/planificacion-390.png).
- [Pedido visto por maestro](capturas/fase4b/pedido-maestro-1440.png) y [móvil](capturas/fase4b/pedido-maestro-390.png).

## Cómo comprobarlo

1. Como propietario, abrir una cotización aprobada → Crear pedido. Seleccionar cliente o registrar sus datos, confirmar aceptación y guardar.
2. Verificar precio y muebles; abrir Planificar materiales y revisar las cantidades ya cargadas. No volver a multiplicarlas por muebles.
3. Editar una cantidad o añadir material, explicar el motivo y guardar. La cantidad inicial se conserva. Revisar el resumen por comprar; consultar existencias para confirmar que no cambiaron.
4. Como maestro, consultar el pedido, revisar materiales y usar Iniciar fabricación. No debe aparecer aceptación de pedido ni ganancias, ni permitir cambiar precio/cliente/asignación.
5. Reabrir la cotización: Ver pedido lleva al mismo registro. No se puede aceptar dos veces. Terminado/Entregado siguen protegidos hasta nueva autorización de cierre.

**No se avanzó a una fase posterior.**


## Identificación simplificada — 5 de noviembre de 2026

Pedidos y cotizaciones muestran su ID real sin prefijos ni ceros de relleno, incluidos planificación, fichas y PDF. Búsquedas y encabezados indican ID. Se retiraron los accessors de folio formateado; no hubo renumeración, migraciones o cambios en MySQL. Validación: 38 pruebas de cotizaciones/pedidos, 333 aserciones; 55 comprobaciones de navegador en base aislada, con PDF y ambos roles; cinco consultas locales HTTP 200 y huellas MySQL idénticas antes/después. Evidencia: [verificación](ARCHIVO-HISTORICO.md).


## Encabezado de pedido — ajuste visual del 5 de noviembre

La ficha muestra «Pedido [id]» con un icono y fecha, separando el estado en una etiqueta redondeada de tamaño normal. Aceptado usa azul suave y En fabricación ámbar suave. Los botones conservan sus permisos y colores, alineados a la derecha en escritorio y debajo en móvil. El ID continúa siendo el real de la tabla, sin código ni relleno. Cambios limitados a resources/views/admin/pedidos/show.blade.php, estilos en resources/css/app.css y su compilación Vite; sin consultas, lógica, rutas o MySQL modificados. Validación: npm run build correcto, 12 pruebas de PedidosPlanificacionTest (99 aserciones) y 55 comprobaciones de navegador aprobadas; revisión visual de escritorio/móvil. [Captura](ARCHIVO-HISTORICO.md).


## Encabezados de planificación — ajuste visual del 5 de noviembre

Se reutiliza el encabezado de pedidos en la consulta y edición de planificación: «Pedido [id]», icono de tareas y estado separado. En el listado el estado usa la misma etiqueta suave con icono. Se conservan IDs, botones autorizados, cálculos, cantidades y rutas. Cambios limitados a admin/planificacion/index.blade.php, show.blade.php y edit.blade.php, sin CSS adicional ni cambios en MySQL. Validación: 12 pruebas de pedidos/planificación (99 aserciones) y 55 comprobaciones de navegador aprobadas; revisión visual de escritorio y comprobación de tamaños móviles. [Captura](ARCHIVO-HISTORICO.md).


## Regreso a los listados — 5 de noviembre de 2026

Los botones Volver al listado de Pedidos y Planificación regresan al índice de su propio módulo. Cancelar en ambos formularios de edición también vuelve al listado. Planificación conserva un enlace independiente Ver pedido, autorizado mediante la política del pedido. Se mantienen los destinos después de guardar y la cancelación del alta desde la cotización, sin modificar controladores, rutas, permisos, cálculos ni MySQL.

Verificación: 12 pruebas funcionales de PedidosPlanificacionTest aprobadas (99 aserciones) y 56 comprobaciones de Edge/Playwright aprobadas en SQLite temporal, incluyendo regreso/cancelación de ambos módulos y acceso explícito al pedido; sin errores JavaScript. Evidencia: docs/capturas/navegacion-modulos/resultado-browser.json.
