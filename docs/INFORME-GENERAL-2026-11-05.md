# Informe general — Carpintería El Tajibo

**Corte:** 5 de noviembre de 2026, America/La_Paz. Consulta de MySQL: **2026-11-05T16:32:27-04:00**.  
**Aplicación independiente:** C:/wamp64/www/carpinteria-el-tajibo.  
**Referencia escolar:** C:/wamp64/www/sisgestionescolarlaravel; no se modificó.

Este documento reúne el estado comprobado al terminar las mejoras visuales y antes de autorizar la fase 4C. Los informes anteriores conservan sus resultados históricos; sus cantidades de tablas, migraciones y funcionalidades no deben interpretarse como el estado actual.

## 1. Situación actual

La aplicación dispone de autenticación, usuarios/perfil, permisos, clientes, categorías, materiales, existencias sencillas, cotizaciones manuales con aprobación y PDF, pedidos y planificación de materiales. Incluye dashboard operativo, paleta menta y selector de tema claro/oscuro.

**MySQL tiene 27 tablas y 21 migraciones registradas. El código contiene los mismos 21 nombres de migración, sin pendientes ni registros sin archivo.** Se verificaron 39 claves foráneas sin referencias huérfanas; no hay saldos negativos, diferencias entre saldos y último movimiento ni diferencias entre los precios de pedidos y la suma de sus detalles. Esta comprobación no es una comparación exhaustiva de todos los tipos, índices y restricciones contra un esquema MySQL recién instalado.

La fase 4A está implementada; la fase 4B también, incluida la planificación sencilla que se adelantó de la propuesta inicial de 4C. El seguimiento de cantidades realmente utilizadas, el cierre/entrega y el motor CBR/k-NN siguen pendientes. La propuesta de 4C expuesta en la conversación todavía requiere diseño y aprobación.

Esta revisión solo consultó MySQL en una transacción de lectura. No ejecutó migraciones, seeders, restauraciones ni cambios de datos. Los cambios de esta entrega son documentación y auxiliares privados de lectura; no se modificaron controladores, modelos, rutas, vistas ni requisitos funcionales.

## 2. Módulos y alcance efectivo

| Módulo | Estado | Funcionalidades actuales y límites |
|---|---|---|
| Acceso | Implementado | Inicio/cierre de sesión, cuentas activas, limitación de intentos y registro público cerrado. Recuperación por correo condicionada a habilitación y SMTP; no se configura correo real en esta revisión. |
| Usuarios y personal | Implementado | Alta/consulta/edición/desactivación; cuenta en users y datos personales en personal. Tipo/número de identificación obligatorios en altas nuevas; teléfono/dirección opcionales. Clave inicial temporal generada en servidor y cambio obligatorio en primer acceso; claves cifradas y fuera de auditoría. No se publican credenciales. |
| Mi perfil | Implementado | Nombre, correo, teléfono, dirección y cambio de contraseña con clave actual y confirmación; rol, estado e identificación se administran por propietario. |
| Roles y permisos | Implementado | Spatie, dos roles y capacidades por operación. El propietario accede a Ver permisos desde Usuarios y puede modificar capacidades permitidas mediante casillas; se protegen capacidades obligatorias y restricciones del maestro. No hay CRUD libre de roles. |
| Clientes | Implementado | Listado, búsqueda, alta, edición, ficha Ver y activo/inactivo. Eliminación solo del propietario y si las relaciones lo permiten; el cliente se solicita al aceptar un pedido, no para cotizar. |
| Categorías | Implementado | Catálogo con estado, CRUD permitido, ficha y menú; selección desde Materiales y protección de relaciones. |
| Materiales | Implementado | Nombre, categoría, unidad, costo de referencia, descripción/especificaciones y estado. Sin código visible alternativo. Material principal filtra la categoría configurada Material principal; los materiales concretos y costos se eligen en el detalle. |
| Existencias de materiales | Implementado, simplificado | Módulo separado para consultar, agregar cantidades y corregir el total con motivo; movimientos con responsable, cantidad anterior/resultante e historial inmutable. No usa presentaciones de compra ni conversiones universales. |
| Cotizaciones manuales — 4A | Implementado | Listar/registrar/editar/Ver, varios muebles, materiales, mano de obra, otros costos, ajustes justificados, aprobación exclusiva del propietario y PDF. Aprobada no equivale a aceptación del cliente. |
| Pedidos — 4B | Implementado, avance parcial de fabricación | Desde una cotización aprobada y aceptación del cliente; propietario registra el pedido y sus datos de contacto. Fichas, filtros, entrega prevista, responsable, notas e historial. Solo Aceptado → En fabricación está habilitado. |
| Planificación — incluida en 4B | Implementada, informativa | Copia cantidades aplicadas de la cotización, permite corregir/añadir materiales con motivo, conserva iniciales y compara necesario/existencia/por comprar. No reserva ni descuenta saldos. |
| Configuración/Ajustes | Implementado | Nombre/contacto, descripción, web y logo de la carpintería. Sin selector de moneda: Bs/BOB fijo. En el corte consultado no hay ficha guardada en ajustes; el sistema usa valores predeterminados donde corresponda. |
| Auditoría | Implementada internamente | Responsables y valores anteriores/nuevos; primera/última modificación en fichas Ver; consulta administrativa protegida, sin convertirla en un menú principal de historial. |
| Dashboard | Implementado con registros actuales | Cotizaciones del mes/por revisar, pedidos aceptados/en fabricación, hasta cinco cotizaciones recientes y cinco pedidos activos priorizados por fecha de entrega; accesos a clientes/materiales. Consultas por permiso, sin importes ni datos personales en sus listas. |
| Casos históricos, CBR/k-NN | Pendiente | Configuración de similitud deshabilitada; sin tablas/motor operativo de casos, sin recomendaciones ni 35 casos históricos generados. |
| Pagos y reportes | Pendiente | Los nombres del menú/permisos son previsiones, con rutas nulas en navegación; no representan módulos funcionales. No hay gestión de anticipos, saldos ni ganancias implementada. |

## 3. Reglas vigentes de negocio

- Las dimensiones se introducen en centímetros. Cada detalle representa un tipo de mueble y una cantidad de muebles iguales.
- Materiales y mano de obra se introducen por un mueble; se multiplican por la cantidad solicitada. Los ajustes por fabricación conjunta requieren motivo y conservan nominales/unitarios.
- Otros costos son un importe total por detalle, sin multiplicarlo nuevamente por cantidad de muebles.
- La ganancia usa **recargo sobre costo**, no margen sobre precio de venta: precio sugerido = costo × (1 + porcentaje/100). El porcentaje solo se introduce por cotización y no tiene predeterminado automático.
- Cada detalle conserva su precio aprobado; el total aprobado es la suma de los detalles. No se recalcula una aprobación histórica al editar precios del catálogo.
- Los costos desconocidos de la cotización permanecen pendientes/nulos; no se presentan como materiales gratuitos ni permiten aprobar el precio sin revisión. Un cero de referencia de catálogo no se adopta como costo real gratuito.
- Cotizar, aprobar, registrar pedidos, cambiar planificación e iniciar fabricación **no descuenta existencias**.
- Por comprar = máximo(necesario − existencia registrada, 0), agrupado por material y unidad dentro del pedido. Es una comparación informativa: no es una reserva y varios pedidos pueden consultar el mismo saldo.
- Acabados actuales: Barnizado natural, Pintado, Melamina, Sin acabado y Otros. El catálogo de tipos contiene seis tipos de mueble.
- Los IDs visibles son los IDs normales, sin prefijos PED-/COT- ni ceros agregados; los UUID internos continúan evitando duplicados.
- Volver/Cancelar en edición de Pedidos y Planificación regresan al listado respectivo; Ver pedido es un acceso explícito. Después de guardar se conserva la consulta del registro correspondiente.
- En cotizaciones siguen vigentes Borrador, Costos pendientes y Aprobada. No se implementó una respuesta comercial separada Pendiente/Aceptada/Rechazada ni un procedimiento de revisión de cotizaciones aprobadas.

## 4. Permisos principales

| Acción | Propietario | Maestro |
|---|---|---|
| Consultar costos de materiales/mano de obra | Sí, con permiso | Sí, con permiso |
| Crear/modificar estimaciones preliminares | Sí, con permiso | Sí, con permiso; nunca aprobadas |
| Ganancia sobre costo y validación/aprobación final | Exclusivo del propietario | Bloqueado incluso ante permisos incompatibles |
| Registrar aceptación/crear pedido | Exclusivo del propietario | No |
| Consultar pedidos y planificación | Sí, con permiso | Sí, con permiso |
| Planificar materiales, notas operativas e iniciar fabricación | Sí, con permiso | Sí, con permiso |
| Cliente acordado, responsable y entrega prevista del pedido | Gestión administrativa autorizada | No se modifican por el flujo del maestro |
| Administrar clientes, materiales, categorías y cantidades | Sí, con permiso | Consulta de lo autorizado |
| Usuarios, permisos y ajustes | Exclusivo del propietario | No |
| Pagos/ganancias/reportes administrativos futuros | Reservados al propietario; módulos pendientes | No |

La interfaz no sustituye la autorización: se utilizan middleware, políticas, Acceso, validación y servicios. No se puede retirar el acceso del último propietario activo. Las cuentas se desactivan, no se eliminan. Categorías, clientes y materiales solo se eliminan cuando relaciones e historial lo permiten; movimientos y eventos de estado se conservan.

## 5. Tablas actuales, módulo y cantidad de registros

Las cantidades son un corte de **registros existentes**, incluyen ejemplos ficticios y pueden cambiar con el uso. No acreditan ventas ni fabricación reales.

| Tabla | Módulo | Filas | Función |
|---|---|---:|---|
| `users` | Usuarios y acceso | 2 | Cuenta, clave cifrada, estado y control de contraseña temporal. |
| `personal` | Usuarios y perfil | 2 | Identificación y contacto, uno a uno con users. |
| `password_reset_tokens` | Autenticación | 0 | Infraestructura de recuperación de contraseña, condicionada a habilitación. |
| `sessions` | Infraestructura | 0 | Tabla de sesiones disponible; el controlador actual es file. |
| `roles` | Roles/Spatie | 2 | Definiciones propietario y maestro. |
| `permissions` | Permisos/Spatie | 38 | Capacidades por acción, incluidas reservas para módulos futuros. |
| `model_has_roles` | Roles/Spatie | 2 | Asignación polimórfica de roles a cuentas. |
| `model_has_permissions` | Permisos/Spatie | 0 | Permisos directos por modelo, si se conceden. |
| `role_has_permissions` | Permisos/Spatie | 52 | Capacidades asignadas a cada rol. |
| `categorias` | Categorías | 7 | Clasificación y estado del catálogo. |
| `clientes` | Clientes | 37 | Datos de contacto para aceptación de pedidos. |
| `unidades_materiales` | Catálogo | 6 | Unidades configuradas y precisión de cantidades. |
| `materiales` | Materiales | 29 | Catálogo, unidad, categoría y costo de referencia. |
| `existencias_materiales` | Existencias | 27 | Un saldo por material. |
| `movimientos_materiales` | Existencias | 29 | Historial inmutable de altas/correcciones de cantidades. |
| `cambios_materiales` | Materiales | 0 | Instantáneas de cambios del catálogo; la tabla está activa aunque esté vacía. |
| `auditorias` | Auditoría transversal | 138 | Eventos, responsables y valores anteriores/nuevos; registra lotes ficticios. |
| `ajustes` | Configuración | 0 | Ficha y logo de la carpintería; moneda fija fuera de esta tabla. |
| `tipos_mueble` | Cotizaciones | 6 | Seis tipos base de mueble. |
| `cotizaciones` | Cotizaciones | 26 | Cabecera, costos calculados, estado y aprobación. |
| `detalles_cotizacion` | Cotizaciones | 27 | Un tipo de mueble por detalle: medidas, cantidad, características y costos. |
| `materiales_cotizacion` | Cotizaciones | 75 | Materiales/costos por detalle, cantidades nominales/aplicadas y ajustes. |
| `pedidos` | Pedidos | 11 | Aceptación del cliente, acuerdo comercial y estado operativo. |
| `detalles_pedido` | Pedidos | 12 | Detalle cotizado de origen, cantidad y precio acordado. |
| `materiales_pedido` | Planificación | 31 | Cantidades iniciales/planificadas por detalle; sin consumo real todavía. |
| `estados_pedido` | Pedidos | 18 | Eventos de cambio de estado, actor, fecha e idempotencia. |
| `migrations` | Infraestructura Laravel | 21 | Historial de migraciones aplicadas y lote. |

La diferencia entre **27 tablas y 21 migraciones es normal**: la migración base de usuarios crea tres tablas; Spatie crea cinco; Laravel administra migrations; y una migración posterior modifica cotizaciones sin crear una tabla nueva.

### Relaciones importantes

- users tiene una ficha personal (personal.user_id único) y se relaciona con roles/permisos mediante las tablas de Spatie.
- categorías y unidades clasifican materiales. materiales tiene, como máximo, un saldo en existencias_materiales; sus movimientos e instantáneas de cambios son relaciones de uno a muchos.
- cotizaciones contiene detalles_cotizacion; cada detalle tiene materiales_cotizacion y conserva características/costos de la cotización.
- cotizaciones puede tener un pedido: pedidos.cotizacion_id es único. Su antigua relación con clientes admite NULL; una nueva cotización no solicita cliente.
- pedidos pertenece a clientes, conserva datos_cliente_acordados y tiene detalles_pedido, estados_pedido y responsables. detalles_pedido enlaza el detalle de cotización y sus materiales_pedido; estos conservan cantidad_inicial y cantidad_planificada.
- auditorias utiliza referencia de tipo/ID de registro; los vínculos polimórficos no son todas claves foráneas SQL. La revisión de 39 FK no sustituye una revisión de cada referencia lógica JSON o polimórfica.

El [diccionario de base de datos](DICCIONARIO-BD-ACTUAL-2026-11-05.md) enumera columnas, tipos, nulabilidad y FK observadas en MySQL, sin valores personales.

## 6. Migraciones del código y correspondencia

**21 archivos / 21 nombres registrados; 0 pendientes y 0 registrados sin archivo.** No se ejecutaron migraciones para elaborar este informe.

| Archivo en database/migrations | Módulo | Tablas/efecto |
|---|---|---|
| `0001_01_01_000000_create_users_table.php` | Usuarios/acceso | users, password_reset_tokens, sessions |
| `2026_10_27_173645_create_permission_tables.php` | Roles y permisos | roles, permissions, model_has_roles, model_has_permissions, role_has_permissions |
| `2026_10_27_195959_create_categorias_table.php` | Categorías | categorias |
| `2026_10_27_200000_create_clientes_table.php` | Clientes | clientes |
| `2026_10_27_200001_create_unidades_materiales_table.php` | Catálogo | unidades_materiales |
| `2026_10_27_200002_create_materiales_table.php` | Materiales | materiales |
| `2026_10_27_200004_create_existencias_materiales_table.php` | Existencias | existencias_materiales |
| `2026_10_27_200005_create_movimientos_materiales_table.php` | Existencias | movimientos_materiales |
| `2026_10_27_200008_create_cambios_materiales_table.php` | Materiales | cambios_materiales |
| `2026_10_27_200009_create_auditorias_table.php` | Auditoría transversal | auditorias |
| `2026_10_28_200010_create_ajustes_table.php` | Configuración | ajustes |
| `2026_11_03_210000_create_tipos_mueble_table.php` | Cotizaciones | tipos_mueble |
| `2026_11_03_210002_create_cotizaciones_table.php` | Cotizaciones | cotizaciones |
| `2026_11_03_210003_create_detalles_cotizacion_table.php` | Cotizaciones | detalles_cotizacion |
| `2026_11_03_210004_create_materiales_cotizacion_table.php` | Cotizaciones | materiales_cotizacion |
| `2026_11_04_000000_allow_cotizaciones_without_cliente.php` | Cotizaciones | Modifica cotizaciones.cliente_id para admitir NULL; no crea tabla. |
| `2026_11_04_150000_create_personal_table.php` | Usuarios y perfil | personal |
| `2026_11_05_200000_create_pedidos_table.php` | Pedidos | pedidos |
| `2026_11_05_200001_create_detalles_pedido_table.php` | Pedidos | detalles_pedido |
| `2026_11_05_200002_create_materiales_pedido_table.php` | Planificación | materiales_pedido |
| `2026_11_05_200003_create_estados_pedido_table.php` | Pedidos | estados_pedido |

Las tablas roles, permissions y pivotes de acceso pertenecen a la migración de Spatie: no falta una migración independiente de roles. Las migraciones de negocio nuevas mantienen una tabla por archivo; la migración allow_cotizaciones_without_cliente es una modificación explícita, no una tabla adicional.

No existen en el esquema actual presentaciones_compra, operaciones_inventario, entradas_materiales, movimientos_inventario ni configuraciones_cotizacion. Cache y colas usan file/sync: no necesitan tablas cache/jobs en esta instalación. Las tablas sessions y password_reset_tokens pueden permanecer vacías sin implicar un fallo o autorizar su eliminación.

## 7. Organización y convenciones del código

```text
app/
  Http/Controllers/          CRUD por entidad; Auth para acceso
  Http/Requests/             Cotizaciones, Pedidos y permisos por rol
  Models/                   Eloquent y relaciones por módulo
  Policies/                 Autorización de recursos
  Services/                 Cálculos, cantidades, aceptación, auditoría y acceso
  Support/                  Decimal, Acceso y Navigation
config/                     Permisos, navegación, moneda, acabados y similitud
resources/
  views/admin/<modulo>/     index/create/edit/show y parciales cuando corresponde
  views/auth/              Acceso y recuperación condicionada
  views/components/        Elementos reutilizables, iconos, tarjetas y tema
  views/vendor/adminlte/   Overrides propios; vendor original sin cambios
  css/app.css              Estilos compartidos y ambos temas
  js/                      app, cotizaciones, pedidos y tema
database/
  migrations/              21 archivos
  seeders/                 Organización por módulo y coordinación de ejemplos
  factories/               User, Cliente, Categoria y Material
tests/
  Feature/                 19 archivos de pruebas funcionales
  browser/                 Escenarios Edge/Playwright en datos aislados
  mysql/                   Auxiliares de pruebas específicas de MySQL
docs/                      Diseño, decisiones e informes históricos/actuales
storage/app/private/       Respaldos y evidencias/auxiliares privados
```

Se conservan estructuras familiares del escolar: controladores CRUD, variables descriptivas, validación y asignación explícita en CRUD sencillos, rutas individuales /admin con nombres admin.*, vistas por módulo y mensajes SweetAlert2. Las operaciones complejas mantienen servicios, Form Requests, transacciones, bloqueos y control de versión/idempotencia; no se trasladan al controlador por imitar el proyecto escolar.

Servicios activos: AuditoriaService, CatalogoService, CotizacionService, CotizacionPdfService, EliminacionService, ExistenciaMaterialService, PedidoService, PermisosRolService, PlanificacionMaterialService y UsuarioService. Hay nueve políticas y siete Form Requests. Algunas vistas/directivas y migraciones aún tienen formato compacto o variables abreviadas; no se hizo una refactorización general en esta revisión.

### Rutas

| Grupo de rutas | Entradas |
|---|---:|
| Acceso e infraestructura | 8 |
| admin.index | 1 |
| admin.ajustes | 3 |
| admin.auditoria | 1 |
| admin.categorias | 7 |
| admin.clientes | 7 |
| admin.cotizaciones | 8 |
| admin.existencias | 6 |
| admin.materiales | 7 |
| admin.pedidos | 7 |
| admin.perfil | 3 |
| admin.permisos | 3 |
| admin.planificacion | 4 |
| admin.usuarios | 6 |

Total registrado: **71 entradas de rutas**. Una entrada puede admitir más de un verbo (por ejemplo PUT/PATCH). Incluye 63 entradas admin.* y ocho de acceso/infraestructura. No se contabilizan opciones de menú con ruta nula como módulos implementados.

## 8. Dependencias y configuración comprobadas

| Paquete instalado | Versión |
|---|---|
| barryvdh/laravel-dompdf | v3.1.2 |
| brick/math | 0.14.8 |
| dompdf/dompdf | v3.1.6 |
| fakerphp/faker | v1.24.1 |
| jeroennoten/laravel-adminlte | v3.15.3 |
| laravel/framework | v12.70.2 |
| laravel/ui | v4.6.3 |
| phpunit/phpunit | 11.5.57 |
| spatie/laravel-permission | 6.20.0 |

- PHP de ejecución de pruebas: 8.3.28; requisito Composer ^8.3. No se deduce de ello una certificación de la versión/configuración de Apache.
- MySQL consultado: **8.4.7**; las 27 tablas son InnoDB.
- Bootstrap local: **4.6.1**; Vite instalado: **6.4.5**; Playwright: **1.64.1**.
- Idioma es; zona America/La_Paz; moneda fija BOB/Bs, dos decimales de presentación. No existe selector de divisa en Ajustes.
- Cantidades e importes almacenados como DECIMAL; cálculos complejos con Brick Math. No hay conversiones universales entre caja, pieza, tablero u otras unidades.
- Sesiones file; caché file; colas sync. La tabla sessions existe como infraestructura, pero no almacena las sesiones del controlador file.
- similitud.habilitado = false; parámetros definitivos de distancia, normalización, pesos, k y umbral sin configurar.
- SweetAlert2 y demás recursos de interfaz se sirven desde recursos locales; no se modificó vendor.

## 9. Seeders, factories y datos ficticios

DatabaseSeeder carga permisos y catálogos base de unidades/tipos; no carga pedidos o ejemplos por accidente. DatosSistemaSeeder coordina CategoriasSeeder, ClientesSeeder, MaterialesSeeder, ExistenciasMaterialesSeeder, CotizacionesSeeder y PedidosSeeder, con activación explícita y controles de repetición. FaseCuatroBPermisosSeeder corresponde a instalación incremental de capacidades, no a un módulo nuevo.

Se verificaron dos lotes internos de auditoría, con origen simulado: datos_bolivianos_20261105_v1 y datos_bolivianos_20261105_v2. Las aprobaciones y estados de fabricación generados para estos ejemplos **no son validaciones comerciales humanas verificadas**. Los nombres de incremento ya no son clases de seeders activas; se conserva su identificación histórica en auditoría para no duplicar datos.

Los ejemplos comparten la base actual por decisión expresa del usuario, sin etiquetas visibles SIM/DEMO. El dashboard cuenta registros del sistema, incluidos esos ejemplos; no es una medición de ventas reales. Los futuros casos/reportes de operaciones reales deben excluir los IDs de todos los lotes simulados según las auditorías. No se generaron los 35 casos históricos para CBR/k-NN.

Estados del corte: **cotizaciones: 15 aprobadas, 8 borradores y 3 con costos pendientes; pedidos: 4 aceptados y 7 en fabricación**. No existen pedidos terminados o entregados en la base consultada.

## 10. Diseño actual

- AdminLTE/Bootstrap 4, menú azul oscuro con acento menta, tarjetas y botones redondeados; Volver/Cancelar plomo e iconos junto a las etiquetas de los formularios.
- Formularios organizados en columnas y secciones; Cotizaciones agrupa características, materiales y costos y despliega opciones secundarias para reducir carga visual.
- Tablas con acciones alineadas y desplazamiento interno en móvil. Menú lateral fijo, opciones desplazables y acceso al menú disponible desde formularios largos.
- Botón de tema claro/oscuro en barra superior y acceso. Preferencia local tajibo.tema, sin nuevas columnas de usuario; cambio sin recargar ni perder campos.
- Dashboard con conteos y accesos operativos; sin gráfico vacío ni resumen financiero ficticio.
- PDF de cotización sin recargo/ganancia administrativa visible al cliente y sin depender del tema oscuro del navegador.

Capturas de referencia del código actual: [panel claro](capturas/tema-claro-oscuro/dashboard-propietario-1440.png), [panel oscuro](capturas/tema-claro-oscuro/tema-oscuro-dashboard-1440.png), [formulario oscuro móvil](capturas/tema-claro-oscuro/tema-oscuro-cotizacion-390.png). Se conservan como evidencia de la entrega visual anterior, no capturas nuevas tomadas durante esta revisión documental.

## 11. Comprobaciones y evidencia

| Comprobación | Resultado y alcance |
|---|---|
| Suite PHP ejecutada en esta revisión | **158 pruebas aprobadas, 1.731 aserciones; 167,29 s.** SQLite en memoria y configuración aislada; no es una ejecución de escrituras en MySQL operativo. |
| Destino MySQL | carpinteria_el_tajibo, host local comprobado; consultas en transacción de solo lectura y finalizada con rollback. |
| Inventario de esquema | 27 tablas InnoDB; 21 migraciones registradas y 21 archivos; sin nombres pendientes ni huérfanos en historial. |
| Relaciones | 39 FK comprobadas; 0 referencias huérfanas. |
| Existencias | 0 saldos negativos y 0 diferencias contra el último movimiento de cada material con movimiento. |
| Pedidos | 0 diferencias entre precio_total_acordado y suma de precio_acordado de sus detalles. |
| Rutas | 71 entradas inventariadas mediante route:list, sin ejecutarlas para escribir registros. |
| Navegador, evidencia previa del mismo día | 75 comprobaciones aprobadas en Edge/Playwright con SQLite temporal. Persistencia de tema, conservación de campos, ambos roles, escritorio/móvil, PDF, SweetAlert2 y flujos de pedidos/planificación. **No se repitió el navegador en esta revisión**, que solo modifica documentación. |
| Pruebas MySQL de concurrencia y respaldos restaurados | Antecedentes documentados de 4B y cargas de ejemplo; no reejecutados ni presentados como pruebas nuevas. |

La suite verifica permisos, validaciones, aprobación/inmutabilidad, cálculos, PDF, aceptación única, idempotencia, planificación, existencias, usuarios/perfil, seeders y rollback. El corte no certifica rendimiento, precisión de CBR/k-NN, producción, entrega de correo real ni todas las referencias lógicas/JSON de la base.

Auxiliar privado utilizado: storage/app/private/revision-general-lectura.php; inventario privado: revision-general-2026-11-05.json; rutas: rutas-revision-general.json. Solo el inventario de esquema y agregados se usa en la documentación pública: no se incluyen correos, documentos, teléfonos, contraseñas, tokens ni credenciales de conexión.

Los respaldos y ensayos anteriores se describen en [FASE-4B](FASE-4B.md), [DATOS-BOLIVIANOS](CARGA-DATOS.md) e [INFORME-CORRECCION-BD](ARCHIVO-HISTORICO.md). Este informe no ejecutó un respaldo nuevo porque no modifica la base. Antes de futuras migraciones siguen siendo necesarios respaldo verificado, ensayo aislado y la aprobación correspondiente.

## 12. Elementos heredados que siguen presentes

Estos puntos no se eliminan ni se consideran un fallo por su mera existencia:

- materiales.categoria conserva una copia textual además de categoria_id; CatalogoService mantiene ambos al guardar. No es un campo adicional del formulario.
- materiales.stock_minimo permanece en la base/modelo, pero el formulario no lo solicita y el controlador prohíbe enviarlo. No hay alertas operativas de umbral en el dashboard.
- movimientos_materiales conserva campos de costos históricos; la operación sencilla de cantidad registra estado_costo = no_aplica y no recalcula precios del catálogo.
- cambios_materiales está vacía en este corte, pero CatalogoService la escribe cuando se modifica el material: **no es una tabla sin uso**. Se distingue de auditorias, que centraliza eventos de varios módulos.
- Permisos como inventario.gestionar e inventario.ajustes.crear se utilizan en la autorización de cantidades. El prefijo técnico no representa el inventario antiguo. presentaciones.gestionar y otros nombres del alcance anterior/futuro permanecen en configuración de permisos sin que existan rutas/tablas de presentaciones.
- personal admite datos nulos para conservar cuentas anteriores; el alta nueva valida identificación obligatoria. No es una contradicción entre migración y formulario.
- Ajustes, tokens y sesiones pueden tener cero filas y seguir siendo componentes válidos. Cambios de cantidad no deben mezclarse con edición del catálogo ni borrarse para simplificar nombres.
- Algunas cabeceras de documentos antiguos aún describen cortes anteriores (13, 16, 22 o 23 migraciones, tablas vacías, pedidos pendientes). Este informe y su fecha permiten interpretar esos antecedentes; no se reescribieron los históricos.

Una limpieza estructural adicional requeriría una propuesta aparte, análisis de referencias y aprobación; esta revisión no borra tablas, columnas, permisos, archivos ni respaldos.

## 13. Trabajo pendiente y siguiente autorización

1. **Diseño de 4C:** seguimiento sencillo de fabricación y cantidades realmente utilizadas, reutilizando la planificación para evitar doble llenado. Es una propuesta pendiente; no habilita consumos automáticos de existencias.
2. **4D:** revisión/validación final y transiciones Terminado/Entregado. Definir responsabilidades y conservación del dato final por mueble antes de implementar.
3. **Fase 5:** casos históricos, separación de origen/revisión y motor CBR/k-NN. Acordar parámetros y metodología; generar 35 casos sintéticos solo con autorización y revisión explícita, sin presentarlos como trabajos reales.
4. **Posterior:** pagos/reportes y seguimiento comercial de cotizaciones cuando se apruebe su alcance. No se implementó el cambio de etiquetas/estados comerciales sugerido en la conversación.

La cotización aprobada sigue protegida; el siguiente trabajo no autoriza cambiar su precio ni descontar materiales al iniciar fabricación. La integración futura de un caso real debe ocurrir al terminar y validar el trabajo, antes de entregar, cuando se implemente esa fase; ahora no se simula esa retención.

## 14. Documentos de consulta

- [Diccionario del esquema actual](DICCIONARIO-BD-ACTUAL-2026-11-05.md).
- [Convenciones de código](CONVENCIONES-CODIGO.md).
- [Fase 4B entregada](FASE-4B.md) y [fase 4A/historia de fase 4](ARCHIVO-HISTORICO.md).
- [Organización de ejemplos y seeders](CARGA-DATOS.md).
- [Dashboard](ARCHIVO-HISTORICO.md), [temas visuales](DISENO-INTERFAZ.md), [menú lateral](ARCHIVO-HISTORICO.md) y [diseño general](ARCHIVO-HISTORICO.md).
- AGENTS.md y REQUISITOS.md conservan decisiones acumuladas; los apartados más recientes deben leerse junto a sus antecedentes. Este informe no cambia los requisitos ni autoriza una fase.
