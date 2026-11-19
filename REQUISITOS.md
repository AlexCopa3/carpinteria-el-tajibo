# Especificación funcional inicial — Carpintería El Tajibo

**Estado:** Requisitos confirmados y decisiones acumuladas; revisión documental del 3 de noviembre de 2026. La especificación incluye funcionalidades futuras, no una declaración de que ya estén implementadas.  
**Propósito:** Documento de contexto para Codex, sujeto a revisión y ajustes durante el desarrollo.  
**Proyecto:** Sistema web de asistencia para mejorar la cotización de pedidos de muebles y la planificación de materiales para su fabricación aplicando razonamiento basado en casos y k-nearest neighbors.  
**Ámbito:** Tienda en La Paz y taller en El Alto, Bolivia.  
**Stack aprobado:** Laravel 12, PHP ^8.3, Blade, Bootstrap 4, JeroenNoten/AdminLTE 3 y MySQL. Versiones instaladas en docs/ARQUITECTURA.md. El estado del código y las discrepancias locales se describen en docs/INFORME-ESTADO-ACTUAL.md.

## 1. Objetivo y alcance
El sistema asiste al propietario y a los maestros carpinteros para registrar las características de muebles personalizados, estimar precios y materiales a partir de casos comparables, mantener un catálogo de materiales y costos, controlar los pedidos y sus pagos, y documentar resultados de fabricación para nuevas recomendaciones.

No pretende sustituir la decisión del propietario ni calcular automáticamente despieces exactos. No se incluyen fotografías ni planos adjuntos. La venta eventual de muebles ya terminados no alimenta los casos de razonamiento en la primera versión.

## 2. Usuarios y permisos

**Decisión vigente del 13/11/2026:** cualquier permiso puede delegarse o retirarse por rol y por persona. La decisión individual prevalece; las restricciones por rol de apartados/entregas anteriores expresan la configuración de partida y quedan sustituidas por esta decisión. Véase docs/PERMISOS.md y el alcance confirmado al final.

| Operación | Propietario | Maestro carpintero |
|---|---|---|
| Consultar todos los pedidos | Sí | Sí |
| Consultar cotizaciones | Sí | Sí |
| Crear cotización preliminar | Sí | Sí |
| Consultar costos de materiales y mano de obra | Sí | Sí |
| Modificar costos estimados antes de aprobar la cotización | Sí | Sí |
| Revisar y validar materiales y cantidades utilizados durante fabricación | Sí | Sí |
| Consultar/modificar márgenes administrativos | Sí, según permiso | Delegable |
| Aprobar precio final | Sí, según permiso | Delegable |
| Consultar/modificar pagos y ganancias | Sí, según permiso | Delegable |
| Registrar evolución de fabricación | Sí | Sí |
| Crear usuarios y permisos | Exclusivo | No |
| Validar datos finales de precio | Sí, según permiso | Delegable |
| Validar datos finales de materiales | Sí | Sí |

Spatie Permission distinguirá `cotizaciones.consultar`, `cotizaciones.crear`, `cotizaciones.modificar` y `cotizaciones.aprobar`. Propietario: todos; maestro: consultar, crear y modificar costos estimados antes de aprobar. La configuración inicial reserva validación/aprobación final, pagos, ganancias, márgenes y gestión de usuarios al propietario; este puede conceder o retirar cualquiera de esos permisos a un maestro, mediante el rol o una excepción individual. Una denegación individual prevalece sobre las concesiones del rol. Los maestros también validan materiales y cantidades utilizados durante la fabricación.

Los permisos deben verificarse también en endpoints y respuestas; no basta ocultar opciones del menú. Usar políticas y validaciones del lado del servidor (en controlador para CRUD sencillos; Form Requests para operaciones complejas): validar importes, impedir edición de estimaciones aprobadas y rechazar campos de aprobación o administrativos en solicitudes preliminares. No aceptar roles, aprobadores ni estados privilegiados del cliente. Los maestros podrán consultar todos los pedidos, pero no información administrativa restringida.

## 3. Datos de cotización

**Pedido:** cliente, fecha, líneas por tipo de mueble, cantidad, estado, fecha prevista de entrega, observaciones y responsables.  
**Mueble:** tipo (escritorio, mesa, silla, armario, estante, puerta), largo/ancho/alto, componentes cuantificados (puertas, cajones, divisiones), material principal, materiales secundarios por componente, acabado y observaciones.  
**Costos:** material(es), mano de obra, otros costos, margen porcentual y total. Conservar el desglose de cada presupuesto validado.

Las líneas con tipos de muebles diferentes se manejan separadas dentro de un pedido. Cotizaciones rechazadas se registran como `Rechazada`, sin incorporarlas a la base de casos.

## 4. Flujo de cotización

1. El propietario o un maestro registra las especificaciones del mueble sin solicitar cliente; los datos personales se pedirán al aceptar el pedido; las cotizaciones iniciadas por maestros son preliminares. Los maestros pueden consultar costos de materiales y mano de obra y modificar los costos estimados antes de la aprobación.
2. El motor busca casos **del mismo tipo de mueble** y calcula similitudes. Se comparan medidas, materiales, acabado y componentes con normalización/ponderación documentada.
3. Cuando hay similitudes que superan el umbral, mostrar **hasta tres** antecedentes elegibles con porcentajes de similitud y recomendaciones editables de materiales, cantidades y precio. La recomendación de mano de obra parte de casos similares y puede modificarse.
4. Mostrar dos estimaciones separadas: **histórica** (derivada de casos comparables) y **actual** (con precios vigentes del catálogo para materiales y costos editables). Las estimaciones no son automáticamente precios finales aprobados.
5. El propietario puede aplicar y editar el porcentaje de ganancia predeterminado. Solo el propietario valida y aprueba el precio final; los maestros no acceden a márgenes administrativos.
6. Si no hay similitud suficiente, mostrar `No hay antecedentes suficientemente similares` y permitir calcular manualmente los costos y materiales.
7. Emitir un **PDF imprimible** de cotización con folio, descripción/características, cantidad, precio y condiciones pertinentes; sin revelar ganancias/margen al cliente.
8. Ante aceptación, registrar anticipo/saldo cuando proceda y crear/actualizar el pedido correspondiente; ante rechazo, no crear caso de aprendizaje.

No hay escalamiento geométrico automático de despiece: la diferencia de dimensiones y componentes se muestra para revisión humana.

## 5. Catálogo y planificación simplificada

### Categorías y materiales
Clasificar materiales por categoría. Registrar nombre, unidad de medida y costo de referencia, conservando descripción, especificaciones y estado ya disponibles. Cualquier conversión de unidades debe definirse explícitamente. El costo de referencia sirve para cotizar y no representa una existencia física.

### Uso cotidiano
Mostrar la existencia en Materiales y su ficha Ver. El propietario puede Agregar cantidad recibida o Corregir cantidad total, con motivo e historial interno. Los maestros consultan cantidades y costos. No mostrar Inventario como módulo separado ni exigir umbrales o presentaciones de compra. Conservar los datos y bloquear las pantallas anteriores; las nuevas acciones tienen autorización específica.

### Planificación futura
Registrar cantidades necesarias por trabajo y revisar con el carpintero qué materiales están disponibles y cuáles necesita comprar. No calcular faltantes desde los saldos históricos ni descontar existencias automáticamente. Mantener cantidades previstas y realmente utilizadas para el aprendizaje de casos; el detalle se definirá al autorizar la fase correspondiente.

### Conservación
No eliminar tablas ni registros de inventario. Editar el catálogo no modifica movimientos ni cantidades previas. Cotizaciones mantiene sus cálculos, costos desconocidos, aprobaciones, inmutabilidad y PDF sin depender de stock.

## 6. Pedidos, estados y registro de casos

Flujo principal: `Aceptado` → `En fabricación` → `Terminado` → `Entregado`.

- `Aceptado`: cliente aprueba la cotización; registrar acuerdo y, si corresponde, anticipo.
- `En fabricación`: iniciar trabajo según la planificación, sin descuentos automáticos de inventario.
- `Terminado`: registrar/validar precio final, materiales y cantidades reales; **una vez validados**, incorporar automáticamente el trabajo a la base de casos, **antes** de la entrega. Impedir duplicados.
- `Entregado`: registrar la fecha de entrega; no duplicar casos.
- `Rechazada`: estado para cotizaciones no aceptadas; no se usa como caso histórico.

La validación final del precio corresponde al propietario; la de materiales se realiza con maestros y propietario. Los pedidos pueden corregirse durante producción, conservando historial de cambios.

## 7. Motor de razonamiento basado en casos y k-nearest neighbors

**Entrada:** tipo exacto de mueble; largo, ancho, alto; puertas/cajones/divisiones; material principal y secundarios; acabado; cantidad y demás atributos estructurados pertinentes. Observaciones libres no se consideran numéricamente sin un diseño explícito.

**Filtrado:** solo casos del mismo tipo.  
**Comparación:** proponer normalización, tratamiento de categorías, ponderación y medida de distancia; acordar parámetros tras analizar datos disponibles.  
**Recuperación:** hasta 3 casos que superen el umbral mínimo, con % de similitud y diferencias relevantes.  
**Reutilización:** recomendar materiales/cantidades, mano de obra y precio basados en antecedentes; mostrar una estimación alternativa con costos actualizados del catálogo.  
**Revisión:** usuarios autorizados pueden ajustar materiales; propietario define/aprueba margen y precio.  
**Retención:** al finalizar y validar un trabajo real, generar/actualizar un caso real con sus valores finales.  
**Sin antecedentes suficientes:** informar y habilitar cotización manual.

Distinguir recomendación ilustrativa de decisión aprobada, y mostrar cuándo la base utilizada incluye datos sintéticos.

## 8. Datos iniciales simulados y evaluación académica

**Corrección expresa:** los aproximadamente 35 casos históricos completos de la carpintería que se mencionaron antes **no existen como registros reales verificados**. El usuario solicita **generar 35 registros sintéticos** adecuados para demostrar y probar el sistema en el contexto boliviano. **No inventar su procedencia real ni afirmar que el personal ya los validó.**

Condiciones:
- Identificador y campo de origen (`simulado` o `real`), fecha ficticia coherente, tipo, dimensiones, componentes, materiales, cantidades, costos ilustrativos en Bs y estado de revisión.
- El propietario y los dos maestros **revisarán** la coherencia de materiales, cantidades y precios; registrar quién/ cuándo revisó cada caso. La revisión humana no convierte datos simulados en operaciones reales.
- Generación reproducible mediante seeder/fixture separado de producción, con opción segura para cargar y limpiar datos de ejemplo sin tocar pedidos reales.
- Los importes pueden ser ficticios plausibles para prototipo, pero deben rotularse **ilustrativos y sujetos a validación local**. Para establecer precios actuales reales se necesitarán datos verificables del negocio o fuentes actuales.
- No inventar mediciones reales de tiempo, encuestas, entrevistas, exactitud, evaluaciones del propietario ni comparaciones antes/después.
- Si se hacen experimentos sobre datos sintéticos, rotularlos y explicar sus limitaciones; evaluar posteriormente con datos reales de referencia cuando estén disponibles.

**Importación de datos históricos:** todavía pendiente de definir (registro manual, importación desde Excel o ambas). No asumir una modalidad obligatoria.

## 9. Panel administrativo y presentación

Reutilizar componentes convenientes del sistema escolar solo después de revisión técnica. Referencia visual: `referencias/dashboard-referencia.png`.

Estilo: menú lateral azul oscuro, fondo claro, tarjetas de indicadores con degradados, botones visibles, gráficos y tablas. Sustituir textos escolares por módulos de carpintería. Indicadores posibles: cotizaciones del mes, pedidos en fabricación, pedidos terminados, pendientes de revisión, materiales necesarios por trabajo (mostrar datos reales una vez implementadas consultas; nunca números ficticios como reales).

Menú propuesto: Panel principal, Cotizaciones, Pedidos, Planificación de materiales, Categorías, Catálogo de materiales, Clientes, Casos históricos, Pagos, Reportes, Usuarios/configuración. Mostrar únicamente lo autorizado a cada rol.

## 10. Requisitos no funcionales
- Protección de rutas y datos por rol; contraseñas seguras y sesiones.
- Validación de formularios en servidor y mensajes claros en español.
- Base de datos relacional con integridad referencial; operaciones críticas transaccionales y auditables; conservar el historial de inventario anterior.
- Almacenamiento de importes `decimal`; moneda Bs y unidades registradas expresamente.
- Responsive para tienda y mediciones de campo, cuando proceda.
- Separar casos sintéticos y reales en cálculos, filtros y reportes.
- Pruebas funcionales y de precisión en condiciones explícitas, sin afirmar resultados antes de ejecutarlas.
- Mantener documentación y un esquema de permisos, migraciones y decisiones técnicas.

## 11. Entidades candidatas (diseño por validar)
`usuarios`, `roles/permisos`, `clientes`, `pedidos`, `detalle_pedido`, `cotizaciones`, `detalle_cotizacion`, `materiales`, `planificaciones_materiales`, `detalle_planificacion`, `pagos`, `casos_historicos`, `detalle_materiales_caso`, `revisiones_caso`.

Los nombres son orientativos. Codex debe revisar el proyecto escolar, proponer cardinalidades y presentar migraciones antes de implantarlas.

## 12. Criterios mínimos de aceptación

1. Un maestro puede ver todos los pedidos y cotizaciones, consultar costos de materiales y mano de obra, crear cotizaciones preliminares y modificar costos estimados antes de la aprobación, así como revisar y validar materiales y cantidades utilizados. No puede validar/aprobar el precio final ni acceder a pagos, márgenes, ganancias o gestión de usuarios, incluso mediante URL directa o solicitudes manipuladas.
2. El propietario aprueba un precio final luego de comparar estimación histórica y estimación a precios vigentes.
3. Si no hay caso con similitud suficiente, la cotización manual está disponible y el sistema no inventa una coincidencia.
4. Las recomendaciones muestran como máximo tres casos elegibles con porcentajes y diferencias.
5. La planificación permite revisar materiales necesarios, disponibles y por comprar, sin exigir un inventario permanente ni descontarlo automáticamente.
6. Los cambios de planificación conservan cantidades previstas y utilizadas y sus responsables, sin generar movimientos de stock.
7. Un pedido terminado y con datos finales validados origina exactamente un caso real; la entrega posterior no duplica ese caso.
8. Un caso marcado `simulado` nunca aparece como trabajo real, ni en reportes ni en la evaluación.
9. Se genera PDF de cotización imprimible y sin datos administrativos restringidos.
10. Los costos de materiales del catálogo pueden actualizarse sin reescribir importes históricos aprobados.

## 13. Decisiones aún abiertas
- Forma de incorporar los históricos (manual/Excel/ambas); mientras tanto, preparar seeds reproducibles para pruebas.
- Fórmula final de normalización y similitud, pesos, valor de k (máximo de resultados = 3) y umbral.
- Conversiones explícitas de unidades de materiales cuando sean necesarias para cotizar.
- Diseño sencillo de revisión de materiales disponibles y por comprar en la fase futura de planificación.
- Tratamiento de pedidos por lote y posibles descuentos por escala (definir al implementar cotización por cantidades).
- Integración funcional del PDF de cotización y su diseño: barryvdh/laravel-dompdf ya está instalado; la elección técnica no significa que el flujo esté implementado.
- Proceso operativo y registro de evidencia de revisión de los casos sintéticos por propietario y maestros.

Estas cuestiones **no bloquean el análisis inicial ni el diseño de la interfaz**; Codex debe documentarlas y no decidirlas silenciosamente.



## Decisión histórica de fase 3: procedencia del costo de entradas (sustituida el 4 de noviembre de 2026)

La decisión anterior permitía materiales gratuitos con costo Bs 0, cantidad siempre mayor que cero y motivo obligatorio conservado en el historial. Distinguir costo conocido, material gratuito y costo desconocido. El costo desconocido se registra pendiente, con importes nulos, y nunca se transforma automáticamente en costo real cero ni se utiliza así para futuras cotizaciones. Las compras por presentación conservan factor, cantidades y costos históricos. No se implementa aún descuento por pedidos o fabricación.



## Decisión confirmada: categorías, retirada de códigos y bordes

Por solicitud expresa del usuario, los materiales se clasifican mediante categorias y materiales.categoria_id, seleccionados desde un formulario. El propietario registra y modifica categorías; los maestros pueden consultarlas sin administrarlas, incluso si reciben por error un permiso administrativo. Se retiró el código de material de formularios, listados, búsquedas, fichas e inventario, y el esquema final no contiene materiales.codigo ni movimientos_inventario.material_codigo. Las modificaciones se integraron después en migraciones de creación consolidadas; la base existente requiere comprobarse separadamente. El ID interno y los códigos de unidades de medida se conservan. Las instantáneas históricas JSON de auditoría no se reescriben. Los botones mantienen sus colores e iconos, esquinas redondeadas y bordes suaves sin contorno oscuro, según la convención visual vigente. No se incorporan otras fases.


## Decisión confirmada: auditoría, estados, eliminación y permisos por rol

Por aprobación del usuario del 28 de octubre de 2026, registrar responsable, fecha y valores anteriores/nuevos de las altas y modificaciones de clientes, materiales, categorías, presentaciones y usuarios. Mostrar registrado por y primera/última modificación en la ficha Ver; conservar la auditoría interna y su consulta protegida por propietario, sin opción de historial en el menú ni en edición. No inventar responsables de registros anteriores; las contraseñas y tokens nunca se guardan en auditoría. Registrar también cambios de permisos, creación inicial por consola como proceso del sistema y recuperación de contraseña sin almacenar el secreto.

Permitir eliminación física únicamente al propietario para clientes, materiales, categorías y presentaciones sin relaciones o historial de negocio que lo impidan. Confirmar mediante SweetAlert2 y validar nuevamente en políticas/servicio transaccional, conservando el evento de eliminación. Los materiales requieren saldo cero y ausencia de presentaciones, operaciones, movimientos y cambios históricos. Las cuentas de usuario se desactivan, no se eliminan. Los movimientos de inventario siguen siendo inmutables y se corrigen con movimientos compensatorios.

Categorías y usuarios incluyen activo/inactivo. Las categorías inactivas no pueden asignarse a materiales nuevos; puede conservarse una ya asignada. Las cuentas inactivas no pueden autenticarse ni conservar una sesión administrativa abierta. El propietario no puede desactivar su cuenta, retirar su propio rol ni dejar al sistema sin propietario activo.

El propietario consulta y modifica permisos por rol mediante casillas; el cambio afecta a todos los usuarios de ese rol. Solo se aceptan permisos compatibles con el rol. Mantener bloqueadas en servidor las capacidades obligatorias y las restricciones administrativas del maestro, incluido acceso a usuarios, pagos, márgenes, ganancias y aprobación del precio final. La pantalla conserva las capacidades operativas confirmadas del maestro. El seeder respeta personalizaciones registradas para no restablecerlas.

Mantener las migraciones consolidadas: activo se integra en las creaciones de users/categorias y se añade una sola migración para la tabla nueva auditorias. Aplicar las diferencias en la base existente con respaldo previo y verificación de datos; no ejecutar migrate:fresh/db:wipe ni tocar el sistema escolar.


## Presentación vigente: consulta mediante Ver

Por solicitud del usuario, los datos del registro, su creador y primera/última modificación se consultan en show.blade.php mediante un botón Ver con icono. No mostrar el componente de responsables dentro de formularios edit. Clientes, materiales, categorías, presentaciones y usuarios disponen de ficha; mantener los permisos de consulta correspondientes. Retirar Historial de cambios del menú y el enlace al historial general de las fichas. La auditoría interna se conserva sin modificar datos ni reglas de negocio.


## Decisión vigente: código explícito como el escolar

El usuario confirmó expresamente trasladar la validación de los CRUD sencillos a $request->validate() dentro del controlador y guardar mediante new Modelo, asignación campo por campo y save(). Esta decisión sustituye la exigencia anterior de Form Requests y RegistroService para esos CRUD. ClienteController y CategoriaController realizan esos pasos dentro de transacciones que también registran la auditoría, conservan campos opcionales omitidos y mantienen autorizaciones del servidor.

Las reglas HTTP de materiales, presentaciones y usuarios están igualmente en sus controladores. CatalogoService y UsuarioService conservan las operaciones relacionadas y sus garantías, con asignaciones explícitas y nombres descriptivos. InventarioService, sus Form Requests y PermisosRolRequest/PermisosRolService se mantienen para operaciones complejas y restricciones de acceso. No convertir los movimientos inmutables en CRUD ni retirar transacciones, bloqueos o idempotencia para imitar el escolar.

UsuarioController se encuentra en app/Http/Controllers junto a los otros controladores de negocio. Las rutas siguen siendo explícitas /admin y admin.*, con la referencia completa del controlador visible como en el escolar. No cambian URLs, métodos ni nombres de rutas. En modelos, declarar los campos fillable de forma legible. En las vistas create/edit, usar form-group, label, input/select/textarea, old, error y CSRF explícitos; no ocultar los campos principales tras componentes genéricos. Mantener el layout, las ayudas accesibles, los botones, los colores y la presentación responsiva aprobados. Conservar una migración por tabla sin modificar esquemas para esta adaptación.


## Configuración autorizada: AjusteController

El usuario proporcionó AjusteController y pidió usar su estructura. Configuración abre admin.ajustes.index y guarda mediante POST admin.ajustes.store. El controlador autoriza, valida con request->validate y asigna cada propiedad de Ajuste antes de save, dentro de una transacción auditada. Mantener una única ficha (ID autoincremental), sin aceptar su identificador del formulario. Se incluyen nombre, descripción, dirección, teléfono, email, web y logo; no añadir márgenes ni otras funciones por iniciativa propia.

La moneda global es fija BOB/Bs en config/tajibo.php; no se selecciona ni se guarda desde el formulario de Ajustes. El propietario gestiona los ajustes; los maestros no acceden ni con una asignación errónea de permisos. El nombre y logo se muestran en el menú y el nombre en el pie; la página de acceso conserva el diseño actual.

Los logos JPG/PNG y SVG gráficos se validan con límite de 2 MB. Un SVG no debe incluir contenido activo ni referencias externas. No eliminar el archivo anterior antes de confirmar el guardado; retirar el archivo nuevo si falla la transacción. Servir el logo mediante su ruta de consulta para usuarios autenticados, sin exigir enlaces simbólicos de Windows. AjusteController conserva el aviso with(swal) del controlador proporcionado; los demás módulos mantienen sus avisos actuales. La nueva tabla ajustes tiene su propia migración de creación, aplicada con respaldo previo y verificación de datos; no modificar ni reiniciar otras tablas. Esta autorización no comprende otras fases.


## ID automático de ajustes

Por solicitud del usuario, la tabla ajustes utiliza $table->id(), el modelo conserva el incremento automático de Eloquent y el controlador no asigna el ID. Se consulta la primera ficha existente, sin depender de un identificador fijo. Las actualizaciones conservan el ID que ya tenga el registro. Mantener transacción y bloqueo al consultar/guardar la ficha. Este acuerdo sustituye cualquier referencia previa a id interno 1 o clave fija; no renumerar ni borrar registros.



## Nota de revisión documental — 3 de noviembre de 2026

Esta actualización no elimina ni reduce requisitos funcionales, incluidos cotizaciones, fabricación, retención de casos, pagos y evaluación. La creación futura de 35 casos simulados sigue sin autorización de ejecución en las fases actuales.

No confundir decisiones confirmadas con resultados de instalación: hoy el MySQL consultado no coincide con las migraciones del código. La ausencia de tablas/columnas y de un recurso SweetAlert2 se registra como discrepancia en INFORME-ESTADO-ACTUAL.md, sin adaptar los requisitos a ese estado.

Las reglas implementadas de conversión presentación→unidad base no cierran por sí mismas la decisión pendiente sobre conversiones/devoluciones de fabricación. Las convenciones de código y presentación se mantienen en CONVENCIONES-CODIGO.md para evitar repetir su detalle técnico aquí.

## Decisiones confirmadas para fase 4 — 3 de noviembre de 2026

- La fórmula elegida es recargo sobre costo: precio sugerido = costo aplicado × (1 + porcentaje / 100). No utilizar la fórmula de margen sobre precio de venta. El porcentaje permanece visible/editable únicamente al propietario y no se fijó un valor predeterminado.
- Largo, ancho y alto se capturan en centímetros; no realizar despiece ni escalamiento geométrico automático.
- Materiales y mano de obra se registran por unidad de mueble y se multiplican por la cantidad solicitada. Se permiten ajustes justificados por fabricación conjunta cuando existan ahorros, conservando valores unitarios, totales nominales y totales aplicados en la cotización. Los maestros pueden modificar estas estimaciones antes de su aprobación; el precio definitivo sigue siendo exclusivo del propietario.
- La fase se divide en 4A cotizaciones manuales/PDF, 4B pedidos, 4C planificación/inventario y 4D cierre/verificación. Por ahora está autorizada la preparación; el diseño detallado debe revisarse antes de nuevas migraciones o cambios grandes.
- En fase 4 no se implementa el módulo de casos ni se generan registros de ese módulo. El requisito final de retener un caso real al terminar y validar, antes de entregar, se implementará en fase 5; fase 4D conserva los datos y las validaciones necesarias. No se generan los 35 casos sintéticos ni se implementan pagos o k-nearest neighbors.

Las entidades, permisos nuevos y reglas marcadas como propuestas en docs/DISENO-FASE-4.md no se convierten en requisitos confirmados por esta anotación. El alcance original permanece en docs/ALCANCE-FASE-4.md.
## Decisiones aprobadas de implementación 4A — 3 de noviembre de 2026

Esta entrega comprende cotizaciones manuales y PDF, con materiales/mano de obra por unidad multiplicados por cantidad, cm y recargo sobre costo. Conserva los valores unitarios y nominales originales y permite ajustes conjuntos justificados con total aplicado. Otros costos son un importe TOTAL por detalle y no se multiplican por muebles.

El propietario configura el recargo predeterminado; inicia sin valor y no se presupone 30 % ni otro porcentaje. Se conserva el precio final aprobado de cada detalle; el total aprobado es su suma. Los maestros consultan costos y modifican estimaciones preliminares; únicamente el propietario valida y aprueba mediante una acción con las comprobaciones del servidor. No se aprueba con costos desconocidos. Tras aprobar se bloquea cualquier modificación; una revisión comercial futura requiere un procedimiento específico.

El PDF imprimible usa precios aprobados y datos comerciales permitidos, sin costos internos, recargo ni ganancias. Aprobar precio no acepta un pedido ni descuenta existencias. Pedidos, planificación/fabricación, casos históricos y k-nearest neighbors quedan fuera de 4A. No se generan casos sintéticos ni se avanza a 4B sin nueva aprobación. Los requisitos generales de esas funciones permanecen vigentes para sus fases correspondientes.

## Decisión vigente — 4 de noviembre de 2026

Se retira la opción «gratuito» de inventario y cotizaciones, incluidos sus motivos específicos y su lógica para nuevas altas. Costo conocido requiere importe mayor que cero; costo desconocido permanece pendiente con importe NULL y no puede utilizarse como cero. La cantidad física siempre debe ser positiva. Se conservan los registros e instantáneas históricas sin reclasificarlos ni borrar su información. Esta decisión sustituye expresamente la regla anterior de costo cero con motivo.

No solicitar cliente ni datos personales para preparar, editar, validar, aprobar o generar PDF de una cotización. Se solicitarán al aceptar el pedido. El PDF nuevo presenta folio, características, cantidades y precios aprobados sin exigir destinatario identificado. Se conservan el módulo Clientes y los vínculos/snapshots de cotizaciones anteriores. La aceptación/pedido se implementará únicamente al autorizar 4B; esta modificación no adelanta esa fase. Las demás reglas y permisos de 4A permanecen vigentes.


## Ajustes simplificados: moneda fija — 4 de noviembre de 2026

Ajustes no incluye selección de moneda. El sistema utiliza BOB/Bs de forma fija, según su ámbito boliviano. Se conserva una sola migración de creación para la tabla, limpia y coherente con el esquema real. La normalización del historial se realiza con respaldo verificado, conservando todos los demás campos y registros.


## Datos del personal, contraseña inicial y perfil — 4 de noviembre de 2026

Por confirmación del usuario, registrar en Usuarios: nombre, correo de acceso, tipo/número de identificación obligatorios, teléfono y dirección opcionales y rol. La cuenta nueva inicia activa; el estado se administra al editar. El prefijo fijo de la contraseña inicial será tj., abreviación de El Tajibo, concatenado con el número de identificación. Se genera y cifra en servidor. Esa contraseña es temporal y debe cambiarse al primer ingreso.

Desde Mi perfil, propietario y maestros pueden modificar sus propios nombre, correo, teléfono, dirección y contraseña. La contraseña actual y la confirmación de la nueva son obligatorias para cambiarla. Rol, estado e identificación permanecen bajo administración exclusiva del propietario. Se mantienen las reglas de seguridad de la nueva clave, las restricciones de cuentas inactivas y la auditoría sin secretos. El perfil no permite actuar sobre otro usuario ni otorgarse permisos.

Las cuentas existentes conservan su contraseña y no se fuerzan al flujo de clave temporal. Sus datos personales se completan cuando el propietario disponga de ellos; no se generan identificaciones ficticias. Cambiar el documento de una cuenta no restablece su contraseña. El módulo permanece integrado en Usuarios; no crear un módulo adicional de personal ni avanzar de fase.



## Separación de acceso y datos personales — 4 de noviembre de 2026

Por solicitud del usuario, users conserva nombre, email, contraseña y estado, además de los datos técnicos de autenticación. La nueva tabla personal contiene tipo/número de identificación, teléfono y dirección, vinculados uno a uno mediante user_id único. El nombre no se duplica. Credenciales y marca de contraseña temporal permanecen en users; los permisos/roles mantienen sus tablas Spatie.

El formulario de Usuarios y Mi perfil continúan integrados y conservan todas las reglas aprobadas. Guardar/actualizar usuario y ficha es una operación transaccional; no dejar una cuenta nueva sin su ficha por un fallo de guardado. El propietario administra identificación/rol/estado y cada persona solo sus datos de perfil permitidos. Conservar los datos existentes, claves e historial al trasladar la información; verificar respaldo/restauración y la copia antes de retirar las columnas de origen. No crear un menú ni módulo adicional de Personal.


## Catálogo sin control de inventario — antecedente sustituido por existencias integradas

El usuario aprobó simplificar el sistema para una carpintería pequeña: mantener Categorías y Materiales con unidad de medida y costo de referencia para cotizaciones, sin exigir registro permanente de existencias, entradas, salidas ni ajustes. No pedir umbral de stock ni mostrar alertas de faltantes calculadas desde saldos antiguos.

La futura planificación registrará materiales y cantidades necesarios y permitirá revisar lo disponible y lo que debe comprarse, sin descontar automáticamente inventario. Su diseño detallado se presentará al autorizar esa fase; no implementar fabricación ni compras ahora. Esta decisión sustituye las reglas anteriores que exigían descuentos al iniciar fabricación, compensaciones por planificación y entregas automáticas de faltantes.

Conservar las tablas, columnas, usuarios, roles, permisos, existencias, movimientos y auditorías actuales, sin eliminar ni reinterpretar registros. El módulo anterior queda retirado del uso normal y bloqueado en servidor, no solo oculto en el menú. No ejecutar migraciones ni limpieza de MySQL por esta simplificación. Al editar Materiales no sobrescribir umbrales ni saldos históricos. Mantener costos desconocidos como NULL en cotizaciones y las restricciones administrativas de los maestros.

Implementación y pruebas: docs/SIMPLIFICACION-INVENTARIO-2026-11-04.md. No autoriza fase 4B ni módulos nuevos.

## Existencias sencillas en Materiales — decisión vigente del 4 de noviembre de 2026

El usuario confirmó que sí necesita conocer las existencias, integradas en Materiales, sin un módulo independiente de Inventario. Esta decisión sustituye únicamente la retirada de saldos y operaciones de cantidad de la simplificación anterior. Mantener el menú sin Inventario, sin umbral de stock ni presentaciones de compra en el uso normal.

Mostrar la existencia en la unidad de cada material en el listado y en Ver. Desde Ver, el propietario puede Agregar cantidad (suma una cantidad recibida mayor que cero) y Corregir cantidad (indica el total físico no negativo, incluido cero). Ambas operaciones requieren motivo y registran responsable, fecha, saldo anterior, diferencia y saldo resultante. Los maestros consultan existencias y costos, sin administrar cantidades, incluso con permisos administrativos concedidos por error. Se mantienen Spatie y controles del servidor.

Reutilizar las tablas existentes, aritmética decimal, transacciones, bloqueo por material, clave única e idempotencia. Una corrección debe detectar que el saldo cambió desde que se abrió el formulario; no sobrescribir silenciosamente otra operación. Validar enteros/fracciones según la unidad, y no convertir automáticamente cajas en tornillos ni otras unidades. Un reenvío idéntico no crea otro movimiento; reutilizar la clave con datos diferentes se rechaza.

Las operaciones ajustan cantidad física, no son registro de compras o costos reales: no cambian el costo de referencia ni convierten costos desconocidos en cero. Conservar presentaciones, saldos y movimientos históricos, sin borrarlos ni reescribirlos. El módulo antiguo permanece bloqueado y tajibo.inventario_habilitado continúa false; la integración de Materiales tiene su autorización específica.

Registrar, editar o aprobar cotizaciones no descuenta existencias. No implementar todavía pedidos, planificación, fabricación ni descuentos automáticos. No requiere cambios de esquema ni migraciones. Evidencias: docs/EXISTENCIAS-MATERIALES-2026-11-04.md.

## Limpieza definitiva del módulo anterior — 4 de noviembre de 2026

Las existencias permanecen integradas en Materiales. Inventario y Presentaciones no tienen módulos, rutas ni pantallas propias; se elimina su código obsoleto y no se conserva una opción de reactivación. Las rutas retiradas responden 404. Mantener los datos, tablas y relaciones existentes, los permisos efectivos del alcance vigente y los controles transaccionales e idempotentes de ExistenciaMaterialService. No implementar descuentos por cotizaciones ni fases nuevas.

## Módulo sencillo de existencias — decisión vigente del 4 de noviembre de 2026

El usuario aprobó separar el catálogo de las cantidades disponibles. Materiales conserva nombre, categoría, unidad y costo de referencia. Existencias de materiales aparece en el menú y muestra material, categoría, existencia en su unidad, estado y acciones Agregar cantidad/Corregir cantidad. El propietario administra; los maestros consultan sin escribir. Mantener los permisos y controles actuales de MaterialPolicy y ExistenciaMaterialService.

Usar la tabla existente existencias_materiales, sin renombrarla ni crear otra tabla. Materiales puede enlazar a las existencias del material seleccionado, pero no duplica formularios ni acciones. ExistenciaMaterialController conserva index/create/store/edit/update y rutas individuales /admin/existencias con nombres admin.existencias.*. Vistas en admin/existencias: index, create, edit y formulario compartido. Retirar el formulario y rutas anteriores de cantidad bajo Materiales.

No recuperar el antiguo Inventario ni Presentaciones. No añadir umbrales, proveedores, costos de compras, descuentos automáticos ni fases nuevas. Cotizar/editar/aprobar no descuenta existencias. Mantener saldo cero para materiales sin fila física aún, sin crear registros solo al consultar. Reutilizar historial, transacciones, cantidades decimales, bloqueo de correcciones obsoletas e idempotencia. La separación no requiere migraciones ni escrituras en MySQL local. Evidencias: docs/MODULO-EXISTENCIAS-2026-11-04.md.

## Limpieza de migraciones aprobada — 4 de noviembre de 2026

El usuario autorizó retirar migraciones y relaciones sin uso y confirmó que no necesita conservar los datos de prueba anteriores. Se optó por conservar los datos de las tablas activas y retirar solamente caché/cola en base de datos, presentaciones de compra y entradas del módulo abandonado, con respaldo completo, restauración idéntica y ensayo aislado previos.

Las migraciones retiradas son create_cache_table, create_jobs_table, create_presentaciones_compra_table y create_entradas_materiales_table. Su retirada afecta a cache, cache_locks, jobs, job_batches, failed_jobs, presentaciones_compra y entradas_materiales. Se eliminan los modelos PresentacionCompra/EntradaMaterial y sus relaciones; se conserva en el respaldo el registro anterior de presentación. Caché usa file y cola usa sync, sin un almacén de caché/cola SQL ni registro de fallos en failed_jobs.

Mantener las otras 19 migraciones y las tablas activas: operaciones_inventario/movimientos_inventario sostienen idempotencia e historial de Existencias; cambios_materiales sigue siendo usado por CatalogoService, además de auditorias. No retirar tablas activas solo por su nombre o por estar vacías. Mantener existencias_materiales, cotizaciones y sus relaciones. Los auxiliares antiguos de separación/consolidación de migraciones ya aplicadas se archivan en el respaldo privado; no reintroducir instaladores del esquema abandonado.

El esquema final debe coincidir con las 19 migraciones del código. No ejecutar migrate:fresh o db:wipe sobre la base local ni tocar el sistema escolar. Esta autorización es una limpieza puntual; no habilita otras fases. Evidencias: docs/LIMPIEZA-MIGRACIONES-2026-11-04.md.

## Consolidación en dos tablas de existencias — 4 de noviembre de 2026

Por aprobación expresa del usuario, mantener existencias_materiales para el saldo y movimientos_materiales para el historial completo: material, unidad, cantidad, saldos anterior/resultante, usuario, motivo, UUID y huella de solicitud. Operaciones y movimientos anteriores se unen y sus dos tablas, modelos y migraciones se retiran después de verificar una copia de seguridad restaurada y ensayar la conversión.

Usar MovimientoMaterial y una sola creación 2026_10_27_200005_create_movimientos_materiales_table.php, sin operacion_id ni modelo OperacionInventario/MovimientoInventario. Conservar IDs de movimientos, fechas, UUID, huellas, responsables, motivos, cantidades y metadatos históricos. No modificar saldos existentes ni datos de los demás módulos.

Mantener historial inmutable, clave única, bloqueos, transacciones, idempotencia, control de correcciones obsoletas y permisos del servidor. Cotizar no descuenta cantidades. No cambiar rutas, pantallas, permisos o fórmulas por esta simplificación. Código y MySQL comprobados: 18 migraciones y 24 tablas. Esta autorización prevalece sobre las instrucciones anteriores de conservar las dos tablas heredadas. No se habilitan fases nuevas. Informe: docs/FUSION-EXISTENCIAS-2026-11-04.md.

## Consulta de movimientos por material — 4 de noviembre de 2026

El usuario aprobó el botón Ver movimientos en Existencias. La consulta usa GET /admin/existencias/{material}, admin.existencias.show y ExistenciaMaterialController::show, autorizando view sobre Material. Presenta únicamente sus movimientos, paginados de 15 en 15 y del más reciente al más antiguo, con fecha/hora, responsable, aumento/disminución, cantidad, saldos y motivo. Propietario y maestros consultan; no se agregan permisos administrativos, edición ni eliminación del historial. El acceso a la ficha del material se conserva en el enlace de su nombre. No se recupera el historial genérico de cambios en el menú. Sin migraciones ni escrituras en MySQL.

## Ganancia únicamente por cotización — 4 de noviembre de 2026

El usuario aprobó retirar el porcentaje predeterminado y conservar solo el campo Ganancia sobre el costo (%) dentro de cada cotización. Las nuevas cotizaciones empiezan con porcentaje NULL, sin heredar el de otra cotización. Solo el propietario lo ve/modifica. Vacío permite precio manual; cero es un porcentaje explícito válido. Conservar la fórmula de recargo sobre costo: precio sugerido = costo × (1 + porcentaje/100), sin convertirla a margen sobre venta.

Se retiraron ConfiguracionCotizacion, su controlador, vista, rutas, tabla y migración, con respaldo restaurado y ensayo previo. Se preservan los valores existentes de cotizaciones.recargo_porcentaje y los precios aprobados, sin recálculo masivo. El nombre interno del campo no se renombra por cambiar su etiqueta. Código/MySQL: 17 migraciones y 23 tablas. Esta decisión sustituye las instrucciones anteriores del recargo predeterminado configurable. No autoriza otras fases. Informe: docs/GANANCIA-COTIZACIONES-2026-11-04.md.

## Acabado mediante selector — 4 de noviembre de 2026

El usuario aprobó estas cinco opciones: Barnizado natural, Pintado, Melamina, Sin acabado y Otros. Se muestran como selector obligatorio en cada detalle de mueble, incluidos los añadidos dinámicamente. Los colores y detalles se indican en Observaciones del mueble, sin introducir campos adicionales obligatorios.

Las opciones compartidas de formulario y servidor se declaran en config/tajibo.php, clave acabados. Se sigue guardando el texto en detalles_cotizacion.acabado; no crear una tabla, catálogo administrativo o migración adicional. Las nuevas altas solo aceptan las cinco opciones, también al invocar CotizacionService directamente.

Un texto anterior fuera de la lista se muestra como opción de registro anterior exclusivamente en su detalle original. Solo se permite conservarlo con el ID del detalle perteneciente a la cotización editada; no se puede reutilizar en otro detalle/cotización ni en una alta nueva. Cambiarlo por una opción nueva es decisión explícita del usuario. No reclasificar textos anteriores ni modificar cotizaciones aprobadas. Mantener precios, permisos e inmutabilidad.


## Material principal desde el catálogo — 4 de noviembre de 2026

La categoría activa «Material principal» agrupa los materiales genéricos MDF, Melamina y Madera. Se registran desde Materiales eligiendo esa categoría. El selector de cada mueble consulta únicamente materiales activos pertenecientes a esa categoría activa. La lista de componentes y costos conserva todos los materiales activos, incluidos accesorios de otras categorías.

Se conserva material_principal_id como relación a materiales; no se cambia a una relación con categorías ni se crean tablas o migraciones. El servidor comprueba la pertenencia también al invocar CotizacionService directamente. Una selección anterior fuera de la categoría solo puede conservarse en su detalle original; no habilita nuevas altas o detalles. Se mantienen los controles de estado activo y la protección de cotizaciones aprobadas.

El usuario todavía no dispone de unidades ni costos para MDF, Melamina y Madera. No inventarlos, no registrar materiales con costo cero como sustituto de costo desconocido y no reclasificar los materiales existentes automáticamente. Se creó únicamente la categoría, con auditoría de operación del sistema (sin atribuirla a un usuario). Los tres materiales quedan pendientes de datos reales.


## Registro rápido de materiales en cotizaciones — 4 de noviembre de 2026

El usuario aprobó sustituir los bloques extensos por filas compactas: búsqueda y selección de material, cantidad por mueble, unidad automática, costo editable y subtotal automático para todos los muebles solicitados. Al elegir el material principal se incorpora a una fila vacía o nueva; si ya figura en la lista no se duplica ni se sobrescriben otras filas. La búsqueda filtra por nombre sin distinguir mayúsculas o tildes y no cambia la selección existente. Diseño adaptable a escritorio, tableta y móvil.

Componente y referencia del costo dejan de requerir llenado habitual: se conserva General en filas nuevas y el componente histórico en edición. La referencia se completa como catálogo al seleccionar material y manual al editar costo. Se mantienen los campos internos y sus validaciones del servidor, sin migraciones. Costo de catálogo positivo se carga automáticamente; referencia cero/no disponible deja el importe vacío y pendiente. Introducir un importe marca costo conocido; vaciarlo vuelve a pendiente, nunca costo cero implícito. El servidor sigue rechazando costos conocidos no positivos.

Cantidad total ajustada y motivo quedan en «Ajustar cantidad total», desplegable abierto cuando hay motivo guardado o errores de esos campos. La justificación sigue siendo obligatoria cuando el total difiere del nominal. Se conservan cantidades unitarias/nominales, costos históricos, permisos, aprobación y aritmética decimal. No se implementan CBR/k-NN ni nuevas fases. No se modifican MySQL ni el sistema escolar.


## Presentación guiada de cotizaciones — 4 de noviembre de 2026

Por solicitud del usuario, crear/editar cotizaciones presenta una guía breve y tres secciones numeradas por mueble: características, materiales y costos. Los campos habituales siguen visibles; notas/condiciones, color/observaciones, puertas/cajones/divisiones y ajustes de mano de obra/precio propuesto usan desplegables nativos. Los conteos permanecen en 0 cuando no corresponden; se conservan todas las reglas del servidor y los campos existentes. Las secciones con valores relevantes o errores se abren al editar o regresar con validación; la validación nativa abre la sección del campo inválido para permitir enfocarlo. No es un asistente que cambie rutas o guarde por pasos.

El resumen por mueble muestra materiales, mano de obra aplicada, otros costos y total usando los resultados existentes de la vista previa. La revisión final reúne costo total y, solo para propietario autorizado, porcentaje de ganancia y precio sugerido. Se mantiene la ganancia sobre costo, permisos, nominales/ajustes, protección de aprobadas y PDF. No cambia esquema, datos locales, servicios, controladores, rutas, modelos o reglas de cálculo.


## Fase 4B aprobada e implementada — 5 de noviembre de 2026

El usuario aprobó docs/PROPUESTA-FASE-4B.md. Pedidos convierte una cotización aprobada y aceptada en un pedido único. Solo el propietario puede aceptar/registrar pedidos y gestionar el cliente; los maestros consultan todos y actualizan observaciones, planificación e inicio de fabricación según permisos. pedidos.crear es un permiso nuevo exclusivo del propietario; la instalación incremental no restablece otras selecciones.

Cliente y contacto al aceptar se guardan en pedidos, sin modificar la cotización aprobada. Cuatro tablas nuevas: pedidos, detalles_pedido, materiales_pedido y estados_pedido, con una migración por tabla y FK restrictivas. Precio acordado por detalle se copia y suma una vez. Los materiales se copian con su cantidad total aplicada, sin multiplicarla nuevamente. Conservar las cantidades iniciales y el acuerdo; no editar precios/muebles/clientes por el flujo operativo. Las correcciones de contacto del catálogo no reescriben la instantánea del pedido.

Planificación sencilla incorporada a esta entrega: cantidades actuales por detalle, motivo de cambios, materiales añadidos con inicial NULL y cantidades 0 para retirar necesidades sin borrar filas históricas. Disponibilidad informativa agrupada por material/unidad para todo el pedido; no representa reserva ni garantiza disponibilidad entre pedidos. No se modifican existencias/movimientos ni se registran consumos, despachos, compras o devoluciones.

Se habilita Aceptado → En fabricación, con fechas/actor e idempotencia. Terminado y Entregado permanecen protegidos hasta implementar la revisión final de 4D. No hay pagos, rechazo comercial, revisiones de precios, casos históricos, generación de casos ni CBR/k-NN en esta entrega. No avanzar de fase sin autorización. Se mantienen los requisitos futuros y la integración de casos al terminar validado antes de la entrega para su fase correspondiente.

Estado aplicado y verificado: 21 migraciones, 27 tablas; cuatro tablas nuevas vacías en local. Respaldo completo restaurado idéntico, ensayo y concurrencia MySQL aislados previos. Informe: docs/FASE-4B.md. No repetir los auxiliares privados de despliegue.


## Datos bolivianos de ejemplo en la base actual — 5 de noviembre de 2026

El usuario solicitó generar datos ficticios mediante seeders/factories y confirmó que deben cargarse en carpinteria_el_tajibo, con nombres normales y sin identificación visible. No mostrar prefijos SIMULADO/DEMO, avisos, nuevas columnas ni un modo de demostración en la interfaz. Esta decisión sustituye para estos ejemplos operativos la separación en otra base y las etiquetas visibles. No cambia su naturaleza ficticia ni autoriza presentar precios, aceptaciones o aprobaciones generadas como operaciones o validaciones humanas reales.

DatosBolivianosSeeder realiza una carga aditiva, transaccional y repetible. Conserva usuarios, contraseñas, roles, permisos y todas las filas previas. Reutiliza categorías activas; crea materiales nuevos sin modificar costos/unidades/saldos existentes. Si un nombre de material ya existe, emplea una variante normal distinta. Solo carga explícitamente con TAJIBO_DATOS_SISTEMA; DatabaseSeeder general no incluye estos ejemplos.

Una auditoría interna de sistema con accion datos_ejemplo_cargados y lote datos_bolivianos_20261105_v1 guarda origen simulado e IDs de los registros creados. No es una etiqueta de la interfaz. Esa procedencia debe conservarse y consultarse en futuras fases para que estos pedidos no se conviertan automáticamente en casos reales ni contaminen métricas de operaciones reales, aunque sus nombres no tengan prefijos. No borrar ni editar aprobadas para retirar ejemplos: usar un procedimiento de restauración/revisión autorizado.

Carga aplicada: 12 clientes, 12 materiales, 8 cotizaciones, 3 pedidos, 11 saldos y 11 movimientos iniciales; cuatro categorías nuevas, reutilizando Material principal. Sin usuarios nuevos, sin nuevas tablas ni migraciones; continúan 21 migraciones y 27 tablas. Incluye costos pendientes y planificación con faltantes; fabricar no descuenta existencias. No se generaron los 35 casos ni se avanzó a otras fases. Respaldo completo restaurado y ensayo previos; informe en docs/CARGA-DATOS.md.


## Ampliación de ejemplos bolivianos — 5 de noviembre de 2026

Por nueva solicitud del usuario se añadió MasDatosBolivianosSeeder, lote datos_bolivianos_20261105_v2: 24 clientes, 15 materiales, 18 cotizaciones y 8 pedidos adicionales; 15 existencias y movimientos iniciales, sin modificar las cantidades previas. Se mantiene la base actual, nombres normales y ausencia de etiquetas visibles. La primera carga y todas las filas anteriores se conservaron; no se agregaron usuarios, permisos, tablas o migraciones.

Ambos seeders son independientes e idempotentes; la ampliación exige la primera carga pero no vuelve a ejecutarla. Se reutilizan los materiales principales activos de la primera carga sin cambiar sus precios/saldos, y los nuevos movimientos corresponden solo a los materiales nuevos. No reactivar categorías o materiales automáticamente. Las aprobaciones y estados son ejemplos generados, no validaciones comerciales humanas.

Se conserva origen simulado e IDs por lote en la auditoría interna. Las futuras funciones de casos/reportes deben excluir los IDs de **todos** los lotes con accion datos_ejemplo_cargados y origen simulado, no únicamente v1. No se generaron casos históricos, pagos ni estados Terminado/Entregado. Continúan 21 migraciones y 27 tablas. Respaldo restaurado, ensayo, repetición sin cambios y 152 pruebas PHP aprobadas; detalle en docs/CARGA-DATOS.md.


## Seeders organizados por módulo — 5 de noviembre de 2026

Por solicitud del usuario, no crear más archivos con nombres de incrementos como DatosBolivianosSeeder o MasDatosBolivianosSeeder. La carga activa usa CategoriasSeeder, ClientesSeeder, MaterialesSeeder, ExistenciasMaterialesSeeder, CotizacionesSeeder y PedidosSeeder, coordinados por DatosSistemaSeeder. Mantener las llamadas a servicios para operaciones relacionadas; detalles, movimientos y estados se generan con sus entidades de origen, respetando transacciones y permisos.

DatabaseSeeder conserva solo los catálogos base. Los ejemplos siguen siendo una carga explícita, protegida por TAJIBO_DATOS_SISTEMA. Los dos nombres de clase anteriores se retiraron, pero sus claves históricas internas de auditoría no se cambian, para evitar duplicados y conservar procedencia. Esta reorganización no carga datos nuevos, no modifica MySQL y no autoriza otra fase. Pruebas en tests/Feature/SeedersModulosTest.php; estructura e instrucciones vigentes en docs/CARGA-DATOS.md.


## Identificación mediante ID normal — 5 de noviembre de 2026

Por solicitud del usuario, mostrar el valor id real de cada registro sin prefijos PED-/COT- ni ceros de relleno. Pedidos, cotizaciones, planificación y PDF usan id directamente; se retiraron los accessors getFolioAttribute y se adaptaron las etiquetas/búsquedas a ID. No crear códigos visibles alternativos para módulos nuevos sin una solicitud expresa.

No renumerar filas ni confundir el ID con el número de fila de una página. Se conservan autoincrementos, claves foráneas, URLs y los UUID internos de idempotencia, que no son los identificadores visibles. Esta revisión no requiere migraciones ni cambios de MySQL.


## Instalación local mediante migrate --seed — 9 de noviembre de 2026

El usuario solicita que los datos ficticios se carguen con php artisan migrate --seed. Esta decisión sustituye, para la instalación local, la obligación anterior de mantener los ejemplos fuera de DatabaseSeeder o activar una variable temporal. config/datos_sistema.php habilita por defecto los ejemplos solo con APP_ENV=local; una opción explícita TAJIBO_DATOS_SISTEMA=false los omite. DatabaseSeeder prepara catálogos y carga los dos lotes mediante DatosSistemaSeeder; solicita interactivamente el primer propietario si falta, sin contraseñas predeterminadas ni modificación de cuentas existentes. Producción no ejecuta esta carga desde el seeder general. Mantener el destino MySQL autorizado, las transacciones, servicios, procedencia interna e idempotencia; las pruebas conservan su aislamiento y activación explícita. No se generan los 35 casos ni se autoriza otra fase. Véase docs/INSTALACION-CON-SEED.md. Este cambio de archivos no carga ni reinicia la base local existente.


## Limpieza autorizada de archivos — 9 de noviembre de 2026

Se retiraron código sin referencias, ensayos sustituidos y documentación intermedia con respaldo ZIP íntegro verificado. La documentación vigente se consulta desde docs/README.md; informes y rutas antiguas de los apartados históricos se localizan en docs/ARCHIVO-HISTORICO.md. Se preservan los requisitos funcionales acumulados, las 21 migraciones, el código activo, pruebas útiles y respaldos anteriores. La limpieza no modifica MySQL ni autoriza nuevas fases, cambios de permisos, cálculos o descuentos de existencias. El escenario principal de navegador es tests/browser/fase4b.cjs y sus nuevas salidas se guardan en docs/capturas/actual, sin sobrescribir evidencia histórica conservada.


## Fase 4C aprobada e implementada — 9 de noviembre de 2026

El usuario aprobó docs/PROPUESTA-FASE-4C.md. Se implementó el registro sencillo de materiales utilizados durante En fabricación, reutilizando materiales_pedido con cantidad_utilizada, observaciones_uso, uso_registrado_por_id y uso_registrado_at, todos nullable. NULL es pendiente; cero expreso es no utilizado. Copiado de planificación solo explícito sobre vacíos, cantidades totales sin nueva multiplicación, observaciones opcionales y motivo general obligatorio para corregir cantidades ya registradas. Mantener auditoría, versión, transacciones, bloqueo y rechazo de duplicados/datos ajenos. Material adicional con inicial NULL y planificación 0; no reinterpretar filas históricas.

Propietario y maestros registran con cuenta activa y los cuatro permisos pedidos.consultar/modificar y planificacion.consultar/modificar; consulta con ambos permisos de consulta. No se alteraron asignaciones. Dos rutas explícitas admin.fabricacion.edit/update y vistas admin/fabricacion, sin menú adicional. FabricacionController conserva edit/update y delega la operación compleja a FabricacionService y su Form Request.

Conservar las 21 migraciones anteriores más la migración aditiva 2026_11_09_200000_add_uso_to_materiales_pedido_table: MySQL verificado con 22 migraciones y 27 tablas. Respaldo completo restaurado y ensayo aislado antes de aplicar; datos anteriores preservados y 31 filas previas con uso NULL. Suite: 178 pruebas/1902 aserciones; navegador: 82 comprobaciones; restricciones y concurrencia MySQL probadas en clon. Evidencias en docs/FASE-4C.md.

No descontar existencias, modificar precios/PDF ni presentar el registro como validación final. No implementar Terminado/Entregado, costos finales, casos históricos, CBR/k-NN ni pagos. La fase 4D y cualquier fase nueva requieren aprobación expresa. Esta decisión posterior sustituye únicamente las menciones históricas de que 4C o cantidades utilizadas todavía no están autorizadas.


## Fase 4D aprobada e implementada — 9 de noviembre de 2026

El usuario aprobó docs/PROPUESTA-FASE-4D.md. Habilitar Aceptado → En fabricación → Terminado → Entregado según el flujo implementado. Propietario y maestros autorizados validan materiales completos mediante materiales.validar y las capacidades operativas de pedidos/planificación. Todo cambio efectivo de uso o planificación invalida esa revisión; envíos iguales y notas generales no la invalidan. Conservar responsable, fecha, valores anteriores y auditoría.

Solo el propietario ratifica el precio ya acordado y termina; puede validar materiales y terminar en una acción si tiene ambos permisos. Solo el propietario registra entrega, con fecha/actor del servidor y nota opcional. No exigir pago completo mientras no exista pagos. No permitir edición posterior al término, reapertura improvisada, cambios comerciales ni nuevos precios. Revalidar permisos/estado en servidor y conservar transacciones, bloqueos, versión e idempotencia de eventos.

Reutilizar pedidos/estados_pedido: una migración aditiva añade materiales_validados_por_id, materiales_validados_at, precio_confirmado_por_id y precio_confirmado_at, todos nullable; fechas de término/entrega se obtienen de estados_pedido. No crear tablas/modelos duplicados. Las 22 migraciones anteriores permanecen intactas: esquema verificado con 23 migraciones y 27 tablas, respaldo restaurado y ensayo concurrente previos. Los 11 pedidos existentes conservaron sus datos y validaciones NULL.

Conservar CierrePedidoService y el Form Request para estas operaciones complejas, métodos explícitos de controladores y rutas /admin con admin.*. No se añadieron permisos ni se modificaron asignaciones. Pruebas: 192/2033 aserciones, 91 comprobaciones de navegador y cuatro carreras MySQL aisladas. Evidencias en docs/FASE-4D.md.

No descontar existencias ni modificar la cotización/PDF o su acuerdo. No se capturan costos reales, pagos ni ganancias nuevas. Casos históricos y CBR/k-NN siguen en fase 5: su futura integración considerará de forma idempotente trabajos terminados/validados previos, incluidos los ya entregados, conservando origen simulado/real. No atribuir validaciones humanas reales a fixtures. La fase 5 requiere aprobación expresa. Esta decisión posterior sustituye solo las menciones históricas de que 4D y cierre/entrega no estaban autorizados.


## Preparación de fase 5 autorizada — 9 de noviembre de 2026

El usuario solicita pasar a fase 5 y confirma generar ahora 35 escenarios ficticios orientados a Bolivia. Los antecedentes externos quedan fuera: por ahora solo pedidos del sistema y demostración. Se preparó database/seeders/data/casos_historicos.json con origen simulado, revisión pendiente y sin responsables/fechas de revisión inventados; todavía no se ha cargado a MySQL ni conectado a DatabaseSeeder. Distribución: 6 escritorios, 6 mesas, 6 sillas, 6 armarios, 6 estantes y 5 puertas; 172 líneas de materiales verificadas. Cantidades y precios son ilustrativos, no información comercial validada.

El usuario preguntó qué tratamiento de costos se recomienda. La propuesta es conservar los costos de cotización como estimaciones identificadas, junto a precio acordado y cantidades utilizadas, sin nueva captura de gastos reales; esa decisión económica sigue pendiente de aprobación expresa del diseño. No declararla ya confirmada ni presentar estimaciones como gastos reales.

Diseño para revisión en docs/PROPUESTA-FASE-5.md y escenarios en docs/CATALOGO-CASOS-HISTORICOS.md. Se propone entrega 5A de casos/retención/seed, 5B de similitud comprobable y 5C de aplicación en cotización. Las dos tablas propuestas y los parámetros piloto no están implementados ni aprobados definitivamente. config/similitud.php sigue desactivado. No fijar pesos, rangos, k o umbral finales sin revisión; no usar pendientes de revisión como recomendaciones validadas.

Lectura MySQL: 27 tablas/23 migraciones, 4 pedidos aceptados y 7 en fabricación, todos identificados como ejemplos; cero terminados con validaciones finales. No se modificó la base ni el código funcional en esta preparación. Mantener respaldo restaurable/ensayo antes de futuras migraciones y preservar los datos y límites de 4D.


## Fase 5A aprobada e implementada — 9 de noviembre de 2026

El usuario confirmó el siguiente paso tras aprobar dos tablas y la conservación de materiales/cantidades/costos/precios por caso. Conservar costos identificados como estimaciones, precio histórico/acordado separado y cantidades utilizadas del grupo, sin segunda multiplicación. Retención calcula el costo estimado de referencia usando cantidades registradas y costos unitarios capturados en su cotización, conservando aparte el presupuesto original y sin reemplazar faltantes por precios actuales o cero. Mano de obra/otros costos mantienen lo estimado en la cotización; no son gastos reales comprobados.

Se implementaron casos_historicos y materiales_caso, dos migraciones individuales, menú index/show y revisión de materiales por propietario/maestro y precio exclusivamente por propietario. Permisos nuevos casos.validar_materiales/casos.validar_precios y tres asignaciones incrementales auditadas, sin restablecer personalizaciones. Origen y revisión son independientes: nunca convertir simulado en real al revisarlo. No hay captura manual ni Excel de antecedentes externos. Las instantáneas históricas no se editan ni borran; descarte con motivo conserva datos.

Al terminar un pedido validado se retiene un caso por detalle y sus materiales en la misma transacción, con unicidad, bloqueo y auditoría; la entrega no duplica casos. RetencionCasoService y RevisionCasoService mantienen lógica compleja fuera del controlador. tajibo:conciliar-casos consulta; --aplicar retiene faltantes de trabajos terminados/entregados validados anteriores sin cambiar pedidos. En la base actual no había candidatos. Identificar los pedidos simulados de todos los lotes de auditoría; no atribuir revisión humana a sus semillas. Los casos operativos completos reales heredan validaciones de 4D con su fuente, sin certificar costos reales.

CasosHistoricosSeeder y CasoHistoricoFactory están implementados. Los 35 escenarios/172 materiales se cargaron en la base actual como simulados pendientes, sin nuevas cuentas/clientes/pedidos/cotizaciones/saldos; todos los datos anteriores conservados. DatabaseSeeder los incluye en instalación local con migrate --seed y respeta la exclusión en producción/desactivación. Repetición idempotente preserva revisiones.

Estado verificado: 25 migraciones, 29 tablas, 51 FK; las 23 migraciones anteriores intactas. Respaldo completo restaurado y ensayo aislado previos. Pruebas: 211/2187 aserciones, 97 comprobaciones de navegador y concurrencia MySQL en clon. Informe docs/FASE-5A.md. No se descuentan existencias ni se modifican cotizaciones/PDF. El motor sigue desactivado: 5B/5C y parámetros definitivos requieren revisión/aprobación; no implementar recuperación/aplicación ni otros módulos ahora. Esta decisión posterior sustituye las menciones históricas de que costos estimados/tables/carga 5A todavía no estaban aprobados.


## Preparación de 5B — 9 de noviembre de 2026

El usuario solicita la siguiente fase después de 5A. Se preparó docs/PROPUESTA-FASE-5B.md con análisis aritmético independiente de los 35 casos simulados, actualmente 34 pendientes y uno revisado; no hay casos de origen real. MySQL permanece con 29 tablas/25 migraciones y sin cambios durante esta preparación.

Se propone recuperación por tipo con diferencias mixtas normalizadas, pesos piloto documentados, k=3/umbral al menos 80 %, perfiles fijos/versionados, control de cobertura y modo de demostración explícito local. No se aprobó ni activó ese perfil; las 247 comprobaciones del analizador privado acreditan solo aritmética, no funcionalidad Laravel ni precisión comercial. El motor de aplicación sigue desactivado. Consultar el diseño y obtener la revisión de parámetros antes de implementar/activar; no dar por autorizada 5C. La consulta propuesta parte del detalle de cotización guardado para evitar otro formulario largo.

## Fase 5B aprobada e implementada — 9 de noviembre de 2026

El usuario aprobó docs/PROPUESTA-FASE-5B.md. Esta decisión sustituye únicamente las menciones anteriores de que la recuperación 5B y su perfil piloto no estaban autorizados. Consulta desde el detalle guardado en Ver cotización, sin repetir el formulario y sin modificar cotizaciones, precios, estados o existencias. Mantener SimilitudController.show, ruta explícita /admin con admin.similitud.show, ComparadorCasosService y RecuperacionCasosService.

Parámetros experimentales aprobados: mismo tipo, k=3, umbral exacto >=80 % antes de redondear, largo/ancho/alto 15 % cada uno, material principal 25 %, acabado 10 %, puertas/cajones/divisiones/cantidad 5 % cada uno. Diferencias mixtas normalizadas con aritmética racional; rangos fijos por tipo y rechazo de consultas fuera de cobertura. Perfil demostracion-5b-v1 en config/perfiles/similitud-demostracion-v1.json; no recalibrar silenciosamente. Descartar contribuyentes, cambiar descriptores o hacer incompatibles unidades/costos invalida el perfil. La revisión pendiente→revisado no cambia por sí sola los rangos.

config/similitud.php habilita la consulta. Modo normal: casos reales revisados y perfil real aprobado; actualmente no existen casos reales ni perfil normal. Demostración: selección explícita, configuración habilitada y entorno local/testing, nunca producción ni mezcla oculta. Los 35 simulados existentes conservan origen y revisión (34 pendientes y uno revisado al iniciar/cerrar esta entrega); no son recomendaciones comerciales validadas.

Servidor: cuenta activa y cotizaciones.consultar, casos.consultar, costos.consultar; pertenencia del detalle y modo válido. Se rechazan parámetros de cálculo enviados por URL. Mostrar cantidades/costos estimados/precio del grupo original sin escalamiento ni aplicación automática. No transformar costos desconocidos en cero ni conceder permisos nuevos.

Informe docs/FASE-5B.md: 232 pruebas/2275 aserciones; repetición final 21/86; navegador 102 comprobaciones aisladas. MySQL conserva datos/estructura, 29 tablas/25 migraciones/51 FK; cero escrituras, migraciones o cargas. Estas cifras no miden precisión real. Aplicación a cotizaciones (5C), recomendaciones duales, pagos y nuevas fases requieren otra autorización.

## Búsqueda y selección desde el formulario — 9 de noviembre de 2026

Decisión posterior del usuario: buscar casos similares al describir el mueble en registro/edición de cotización, antes de guardar y sin exigir materiales/costos. Mostrar hasta tres elegibles. Ante la consulta sobre una síntesis ponderada, el usuario eligió expresamente **usar el caso más similar como principal**, conservando las alternativas; no generar una cuarta opción combinada ni promediar casos.

Se autoriza aplicar la propuesta principal o una alternativa a los campos editables de la nueva cotización, previa confirmación y sin modificar antecedentes. Conservar características solicitadas, costos estimados y aprobación exclusiva del propietario. Materiales por unidad de mueble; costos actuales de catálogo, desconocidos pendientes, mano de obra histórica por unidad y otros costos como total fijo del detalle. Sin despiece o escalamiento por dimensiones. Revisar cantidades si las medidas difieren y redondear hacia arriba según unidad, con aviso.

No copiar el precio histórico como aprobado, recargo ni margen de un caso. Histórico se muestra con su grupo original; precio sugerido actual conserva el cálculo de la cotización y aprobación por propietario. Material inactivo/unidad incompatible o principal distinto requiere revisión manual; no sustituirlo silenciosamente. Si no hay suficientes vecinos, mostrar solo los disponibles; si no hay ninguno, ofrecer llenado manual.

Esta solicitud autoriza únicamente esta integración y selección editable, sustituyendo el límite anterior de consulta solo después de guardar y la prohibición de aplicar propuestas en este flujo. No autoriza pagos, nuevos módulos, descuentos de existencias u otras fases. Mantener separación normal/demostración, perfil fijo y permisos. Resultados se invalidan al cambiar características; confirmación evita sobrescribir inadvertidamente el trabajo manual. Procedencia firmada se verifica al guardar y se conserva en auditorías existentes, sin nuevas tablas/columnas. Informe docs/FLUJO-COTIZACION-CON-CASOS.md.

## Casos históricos unificados — 9 de noviembre de 2026

El usuario solicita retirar el modo, botones, funciones y archivos separados de demostración, usando solo el nombre Casos históricos. La búsqueda única consulta los antecedentes completos pendientes/revisados del mismo tipo, sin separar por un selector de origen o entorno. Mantener umbral, k, pesos, cobertura fija, revisión, permisos y aprobación del propietario. Se sustituye la regla previa de exigir selección de modo local; no se autovalida ni reclasifica ningún registro.

Perfil único config/perfiles/similitud-casos-historicos-v1.json, config/similitud.php con perfil plano. Se retiran permitir_demostracion, perfiles.normal/demostracion y el flag obsoleto incluir_simulados; servicios/controlador/JS ya no reciben modo. Registro y ficha guardada usan el mismo motor. Principal = caso más similar y alternativas editables según decisión anterior.

Los 35 antecedentes fueron generados: se conserva su procedencia interna y su revisión existente, sin convertirlos en fabricación/ventas realizadas ni afirmar validación humana. La interfaz usa Casos históricos, Registro inicial y Desde pedido; no etiqueta el módulo como demostración o casos reales. Lote/checksum originales y auditorías previas se conservan como identificadores históricos para no duplicar ni reescribir datos; no representan un modo activo. El seeder genérico CasosHistoricosSeeder reconoce tanto la carga anterior como la nueva por lote/checksum; nuevas auditorías usan casos_historicos_cargados y nuevas fuentes registro_inicial/estimacion_inicial.

No modificar MySQL por un cambio de nombres: se preservan tablas/columnas/filas, usuarios, permisos, existencias y revisiones. Las 25 migraciones no cambian. Documentación actual en docs/FLUJO-COTIZACION-CON-CASOS.md; los informes previos conservan el contexto de sus entregas. No implementar módulos nuevos ni pagos/descuentos de existencias.

## Cierre funcional de 5C — 10 de noviembre de 2026

Tras solicitar el usuario el siguiente paso, se completa la comparación económica ya prevista para cotización asistida: dos estimaciones para la misma cantidad solicitada, con costos unitarios históricos capturados y con precios actuales de materiales. Mano de obra por mueble y otros costos como importe fijo se mantienen en ambos escenarios. No se cambian cálculos de guardado/aprobación, algoritmo de similitud ni parámetros del perfil.

Si el mismo material aparece en varias líneas del caso, conservar costo histórico ponderado por cantidad, con racionales exactos y redondeo por subtotal a dos decimales; no promediar simplemente los precios. Cantidades adaptadas idénticas en ambos escenarios, usando las unidades/redondeo ya existentes. No sustituir costos desconocidos por catálogo o cero en la referencia histórica; cada escenario mantiene sus propios pendientes.

Los totales originales del caso y su precio histórico permanecen identificados como datos de su grupo original. Avisar cuando la cantidad solicitada difiera: ahorros por fabricación conjunta requieren revisión, sin nuevo campo obligatorio ni descuento automático. Los precios actuales solo actualizan materiales; mano de obra/otros costos siguen estimados y editables.

Conservar referencia económica inicial en la procedencia firmada/auditoría al guardar, sin añadir tablas/columnas ni campos de llenado. El usuario continúa escogiendo principal (mejor vecino) o alternativa y editando, con autorización y aprobación exclusivas ya establecidas. Sin escrituras MySQL en la entrega ni cambios del sistema escolar. Informe docs/FASE-5C.md. Pagos, reportes completos, captura de gastos y evaluación humana de precisión siguen fuera de este cierre.

## Definición de pagos — 10 de noviembre de 2026

El usuario autoriza preparar el siguiente módulo, Pagos, y confirma: medios efectivo y QR; pagos editables, conservando auditoría de valores anteriores/nuevos; entrega permitida con saldo pendiente y advertencia, sin bloqueo automático. Acceso exclusivo del propietario; conservar restricciones del maestro y precio acordado del pedido. No añadir transferencia bancaria, cobro QR automático o procesos contables por iniciativa.

Propuesta concreta en docs/PROPUESTA-FASE-6A-PAGOS.md: tabla pagos, reutilización de pagos.consultar/pagos.gestionar, registro/edición/consulta y saldos calculados con decimales. El diseño técnico y aplicación del esquema se presentan para revisión; aún no implementados. Verificación MySQL de solo lectura: 29 tablas/25 migraciones, 11 pedidos, sin pagos; permisos existentes solo en propietario. No se modificó MySQL ni se autoriza reinicio, recarga de datos o módulos adicionales. Antes de aplicar, respaldo completo restaurado/verificado y ensayo aislado, preservando registros y permisos.

## Fase 6A aprobada — Pagos, 10 de noviembre de 2026

El usuario aprobó docs/PROPUESTA-FASE-6A-PAGOS.md y su aplicación controlada. Implementar una tabla pagos y reutilizar pagos.consultar/pagos.gestionar, sin crear roles o restablecer asignaciones. Propietario activo con ambos permisos según operación y consulta de pedidos; maestros bloqueados incluso con asignación incompatible. No exponer cobros/saldos en HTML/JSON de maestros.

Registro/edición mediante PagoController CRUD explícito, Pago, PagoPolicy, GuardarPagoRequest y PagoService. Efectivo/QR, importe positivo de dos decimales, fecha de pago no futura, referencia/observaciones opcionales. Editar con motivo y versión; permite corregir pedido asociado, bloqueando ambos pedidos por ID ordenado y recalculando ambos saldos. No borrar o anular pagos. Auditoría conserva antes/después y actor; registrador original intacto.

Saldos derivados del precio acordado menos pagos registrados, con Decimal/Brick Math, sin nuevas columnas de saldo o modificación del precio/estado de pedidos. Lectura actual bloqueada de pagos dentro del bloqueo del pedido, evitando snapshots anteriores a la espera. UUID/huella para reenvíos y devolución del mismo registro sin volver a snapshot obsoleto. Sin sobrepagos, movimientos de dinero automáticos o pagos iniciales inventados.

Entrega con saldo permitida: advertencia visible y confirmación, más mensaje del servidor tras entregar, solo a propietario con acceso a cobros. Editar posteriormente un pago no revierte estados de fabricación/entrega. Mantener todas las validaciones finales anteriores de materiales y precio.

Aplicar únicamente la migración 2026_11_10_200000_create_pagos_table después de respaldo íntegro restaurado/idéntico y ensayo aislado con concurrencia aprobado. Conservar las 25 migraciones originales y todos los usuarios, contraseñas, permisos, datos y auditorías previas; no ejecutar recargas o reinicios. No implementar reportes, caja, gastos, transferencias, facturación fiscal, devoluciones o nuevas fases. Estado/resultados en docs/FASE-6A.md.

## Fase 6A aplicada y verificada — 10 de noviembre de 2026

Pagos implementado y migración individual aprobada aplicada solo a carpinteria_el_tajibo, tras respaldo completo restaurado y ensayo MySQL concurrente. Estado final 30 tablas/26 migraciones/53 FK, pagos vacía. Todos los datos/estructuras anteriores preservados, sin cambios de permisos/usuarios o recarga de ejemplos. Aplicación fuera de mantenimiento.

Suite completa 259 pruebas/2463 aserciones; 117 comprobaciones Edge/Playwright y 95 capturas acumuladas, en SQLite aislado. Ensayo de registro/edición/reasignación, UUID simultáneo, sobrepago simultáneo, versión concurrente y CHECK MySQL aprobado. Verificación operativa de controlador/Blade/servicio de solo lectura con propietario/maestro; sin sesión HTTP autenticada sobre MySQL. Detalles/respaldo/capturas en docs/FASE-6A.md.

Conservar arquitectura y reglas de pagos aprobadas. No ejecutar scripts privados de despliegue como instaladores. En instalación nueva migrate --seed incluye tabla pagos vacía, sin inventar cobros de pedidos anteriores. No avanzar a reportes, caja, gastos, ganancias, devoluciones o fases posteriores sin instrucción del usuario.

## Fase 6B — Reportes autorizados, 10 de noviembre de 2026

El usuario solicita continuar y confirma PDF imprimible y **todos los registros del sistema**, incluidos los cargados por seeders. Esta decisión sustituye para estos reportes operativos la exclusión previa de registros iniciales. No altera procedencia, revisión ni calidad de los datos, ni los presenta como ventas o ganancias verificadas.

Reportes implementados de cotizaciones, pedidos, pagos recibidos y saldos actuales. Solo lectura y propietario activo con reportes.administrativos, panel.consultar y permiso del módulo de origen; pagos/saldos requieren también pedidos.consultar y pagos.consultar. Maestro bloqueado aun con asignación incompatible. Costos estimados únicamente con costos.consultar. Servidor comprueba rutas HTML, PDF y servicio.

Fechas inclusivas: cotización.fecha, pedido.fecha_aceptacion y pago.fecha respectivamente. Saldos considera todos los pagos de cada pedido y no admite fechas: es saldo actual, no reconstrucción histórica. Sumas con BigDecimal; precios aprobados/acordados no se cuentan como cobros, costos desconocidos siguen pendientes. Fechas/estados/medios y parámetros admitidos validados en servidor.

Pantalla paginada de 20 filas con totales de todo el filtro. PDF A4 horizontal, mismo filtro completo, máximo 500 filas con rechazo explícito si se supera; nunca truncar silenciosamente. Se reutilizan Dompdf, AdminLTE/Blade, rutas explícitas /admin y admin.*. Sin nuevas entidades, tablas, migraciones, permisos, dependencias ni escrituras MySQL. No ejecutar seeders/migraciones para habilitar Reportes.

Estado y evidencia en docs/FASE-6B.md. No implementar caja, gastos, ganancias reales, facturación fiscal, Excel, evaluación humana de similitud ni otras fases sin nueva autorización. Mantener intacto el sistema escolar.

Cierre 6B verificado: suite completa 269 pruebas/2581 aserciones y específicas 10/118 aprobadas; Edge/Playwright 133 comprobaciones/111 capturas acumuladas, cero errores JS. MySQL 30 tablas/26 migraciones/53 FK sin diferencias antes/después, con 26 cotizaciones, 11 pedidos y cero pagos. PDF multipágina revisado. Evidencia e informe en docs/FASE-6B.md.

## Búsqueda en Categorías — 11 de noviembre de 2026

Por solicitud del usuario, el listado permite buscar por parte del nombre mediante campo de búsqueda y botones Buscar/Limpiar con iconos. Propietario y maestros con permiso de consulta pueden usarla; no habilita administración al maestro. Conservar filtro al paginar, mostrar mensaje sin coincidencias y recuperar el listado completo al limpiar. Mantener el diseño actual.

## Calendario de la barra superior — 11 de noviembre de 2026

El usuario solicita que la fecha permita consultar un calendario. Se muestran los días de cada mes, navegación entre meses, regreso a Hoy y cierre; su uso es solo de consulta, sin registrar eventos ni cambiar fechas de pedidos o de otros módulos. Disponible para ambos roles con sesión, sin permisos administrativos nuevos. También solicita botones de la barra con el estilo relleno y redondeado de Mi perfil.

## Identificación editable desde Mi perfil — 11 de noviembre de 2026

Decisión posterior del usuario: propietario y maestros pueden completar o modificar su propio tipo y número de identificación desde Mi perfil. Esta autorización sustituye la restricción anterior de edición exclusiva por el propietario. Mantener tipos CI/pasaporte, normalización, formato y unicidad por tipo y número en personal, validación del servidor y auditoría transaccional. Conservar la compatibilidad de cuentas antiguas sin identificación: si se informa un dato, se exige el otro. Cambiar la identificación no cambia la contraseña ni el acceso; rol, estado y permisos continúan restringidos al propietario desde Usuarios. No se modifica el esquema ni se actualizan registros automáticamente.

## Selección del propietario — 11 de noviembre de 2026

Por decisión del usuario, ocultar Propietario al crear una cuenta cuando ya existe un usuario con ese rol (también si está inactivo). Al editar maestros no permitir ascenderlos a propietario mientras exista otro; la cuenta que ya es propietaria puede conservar su rol. Validar la regla en controlador y servicio, además de la vista. No eliminar ni cambiar propietarios preexistentes automáticamente. Mantener protección del acceso del propietario y las reglas de contraseña inicial.

## Delegación individual de tareas operativas — 11 de noviembre de 2026

El usuario autoriza permisos adicionales por maestro, administrados solo por el propietario desde Usuarios. Alcance inicial: registrar/editar clientes y registrar/editar materiales, usando los cuatro permisos existentes clientes.crear, clientes.modificar, materiales.crear y materiales.modificar. Se suman a los del rol; retirar un adicional no elimina permisos heredados ni altera a otros usuarios. No cambia el rol maestro por defecto ni se recargan sus permisos. Registrar materiales es independiente de editarlos. Categorías, eliminación, desactivación de clientes, cantidades de existencias, aprobación/precios finales, pagos, ganancias, márgenes, reportes administrativos, configuración y usuarios siguen con sus restricciones anteriores. Conservar Form Requests/políticas/Spatie, servidor, transacción y auditoría. No hay nuevas tablas/migraciones ni asignaciones automáticas a usuarios existentes. Esta decisión sustituye la prohibición absoluta anterior de delegar registro/edición de clientes y materiales.

## Alcance confirmado de permisos — 13 de noviembre de 2026

El usuario confirma que el propietario puede conceder o retirar cualquier permiso, incluidos pagos, ganancias, aprobación de precios y administración de usuarios, y que una decisión individual debe prevalecer sobre el rol. Sustituye el límite funcional anterior de cuatro operaciones delegables y las exclusiones por rol. Implementación aplicada y verificada el 13 de noviembre de 2026, tras aprobación del usuario, respaldo completo restaurado y ensayo aislado. Permisos generales editables para todos los módulos; excepciones individuales Según su rol / Permitir / Denegar con prioridad sobre Spatie. El propietario conserva las cuatro capacidades de recuperación documentadas. Base carpinteria_el_tajibo: 31 tablas, 27 migraciones, 55 FK y 48 permisos; propietario 48 y maestro sus mismos 15. Sin delegaciones automáticas al maestro, cambios de contraseñas ni pérdidas de registros. Informe: docs/INFORME-PERMISOS-GENERALES-INDIVIDUALES.md. Esta decisión sustituye las restricciones históricas por rol; conservar las validaciones del negocio y no avanzar a fases nuevas.

## Resta de materiales al iniciar fabricación — decisión del 13/11/2026

El usuario acepta incorporar la resta de existencias y concreta que ocurra al comenzar la fabricación. Se propone usar las cantidades planificadas al pulsar Iniciar fabricación, sin restas en cotización/aceptación ni retroactivas a pedidos ya iniciados. Las compensaciones por uso real confirmado, las dos ampliaciones de esquema y sus pruebas se detallan en docs/PLAN-CONSUMO-AL-INICIAR-FABRICACION.md, pendientes de revisión. No afirmar que el descuento esté implementado ni ejecutar los borradores privados sobre MySQL. Mantener el flujo actual hasta aprobar el diseño y completar respaldo restaurado, ensayo aislado y aplicación controlada. Esta decisión sustituye la prohibición funcional anterior de incorporar descuentos, pero no autoriza aplicar un esquema no revisado.

## Usuarios: permisos fijos y bloqueados — 14/11/2026

Decisión posterior del usuario: el grupo Usuarios se bloquea tanto en permisos generales como individuales. usuarios.consultar, usuarios.crear, usuarios.modificar, usuarios.gestionar y usuarios.asignar_roles quedan activos únicamente para el propietario y desactivados para maestros, sin posibilidad de delegarlos ni de retirarlos al propietario desde esos formularios. La restricción se aplica antes de las excepciones individuales en User/Acceso/Gate y los servicios rechazan solicitudes manipuladas. Las asignaciones antiguas no pueden dar acceso al maestro. El resto de los permisos conserva su personalización y prioridad individual. No modificar tablas, datos o asignaciones existentes por el despliegue de esta regla. Esta decisión sustituye solo la delegación de administración de usuarios/roles/permisos autorizada el 13/11; no restablece las antiguas exclusiones de pagos, precios o márgenes.


## Tipos de muebles — decisión del 14/11/2026

Permitir ampliar el catálogo de tipos desde el sistema, registrando nombre y estado activo/inactivo. Listar, buscar, consultar y editar; desactivar conserva sus relaciones e instantáneas y evita ofrecerlo en nuevas cotizaciones. No incorporar eliminación física. Los seis tipos iniciales continúan como valores de partida, no una lista cerrada. Mantener el ID normal de la tabla y el código técnico existente interno. Registrar responsable y cambios mediante auditoría. La consulta utiliza el permiso de consultar cotizaciones y la administración requiere también administrar Configuración, respetando la delegación existente. No crear nuevos permisos ni cargar datos automáticamente por esta entrega. La recuperación sigue limitada al mismo tipo y al perfil vigente: un tipo nuevo puede cotizarse manualmente; no obtiene recomendaciones sin casos y cobertura suficiente.


## Simplificación del formulario de cotización — 15/11/2026

Por aprobación del usuario, no solicitar Mano de obra total ajustada, Justificación del ahorro o ajuste de mano de obra ni Precio total propuesto. Registrar únicamente la mano de obra por mueble y multiplicarla por la cantidad; los ahorros de fabricación conjunta pueden reflejarse ajustando ese importe unitario antes de aprobar. Mantener materiales, otros costos totales por detalle, ganancia sobre costo, precio sugerido y posterior aprobación final. No retirar el ajuste opcional de cantidades de materiales.

Preservar valores anteriores y cotizaciones aprobadas. Una edición de estimación antigua que no cambie cantidad/mano unitaria/propuesta conserva su ajuste anterior con un aviso de solo lectura. Al revisar esos valores se recalcula y el ajuste previo permanece en la auditoría. Los precios propuestos antiguos y la compatibilidad histórica no deben borrarse por retirar sus campos. No modificar el esquema ni los registros existentes de forma masiva.


## Restitución del apartado opcional de cotización — 15/11/2026

El usuario revoca la retirada anterior y solicita dejar el apartado como estaba. Restituir el ajuste de mano de obra total con motivo obligatorio si difiere del nominal, conservando valores nominales/aplicados y responsable, y el precio total propuesto opcional por detalle de mueble. El propuesto es una sugerencia comercial editable anterior a la aprobación, no un costo ni un precio final aprobado; no se multiplica por la cantidad otra vez. Se conserva el precio sugerido calculado por ganancia y la aprobación posterior de los precios por detalle. Esta decisión sustituye la simplificación documentada antes el mismo día; no autoriza cambios de esquema ni datos.


## Cotizaciones: unificar notas en Observaciones — 15/11/2026

Por solicitud del usuario, retirar Condiciones para el cliente de Crear/Editar y dejar únicamente Observaciones en Datos generales. Renombrar el desplegable a Observaciones y permitir que el texto use el ancho de su recuadro. No borrar ni fusionar automáticamente condiciones anteriores: al omitirse el campo, CotizacionService conserva su valor guardado. Se mantiene la validación compatible con datos anteriores y su consulta/PDF cuando exista texto histórico. Las nuevas cotizaciones desde el formulario no generan condiciones. No modificar tablas, migraciones ni registros existentes por este cambio.


## Pedidos en la ficha del cliente — 16/11/2026

Por solicitud del usuario, Ver cliente incluye sus pedidos asociados por cliente_id, del más reciente al más antiguo, con ID, fecha, entrega prevista, estado y acceso Ver al detalle. Paginar de ocho en ocho y mostrar un mensaje cuando no existan pedidos. La sección requiere el permiso efectivo de consultar pedidos; si está denegado, no consultar ni exponer sus datos en la ficha. Conservar acceso, datos y auditoría del cliente, navegación y permisos de Pedidos. Consulta de solo lectura, sin nuevas tablas ni modificaciones de registros.


## Detalle del pedido simplificado — 16/11/2026

El usuario aprueba simplificar esta vista manteniendo funciones y permisos: un solo resumen de total acordado, pagado y saldo, con los datos de pagos sujetos a sus permisos; cliente, entrega, responsable, muebles y materiales siguen visibles. Renombrar Editar a Editar datos del pedido. Mantener destacada la cotización pendiente de confirmar. Ocultar dirección y observaciones vacías. Seguimiento, datos del registro y casos disponibles pasan a desplegables cerrados inicialmente. Responsables/fechas del consumo se consultan en Datos del registro; Planificación mantiene su tabla original.

Evitar duplicar validación y cierre: quien tiene ambos permisos confirma materiales y precio en una sola casilla al Terminar fabricación, enviando los campos que ya valida el servidor. Quien solo puede validar conserva Validar materiales. Quien solo puede terminar debe esperar una validación previa de alguien autorizado; no recibe el campo para validar. Conservar SweetAlert2, las rutas, validaciones y controles de permisos del servidor. No cambiar cálculos, modelos, servicios, base de datos ni migraciones. Estilos desplegables limitados a Detalle del pedido, accesibles por teclado y compatibles con claro/oscuro.

## Edición directa de la cotización del pedido — 16/11/2026

Decisión vigente aprobada: retirar completamente el mecanismo de cotización original, revisiones comerciales y cambios de pedido. Se edita la misma cotización y sus líneas actuales, conservando sus IDs. No crear otra cabecera ni archivar versiones de detalles; no mantener ciclos, mapas JSON, tablas de cambios ni referencias originales.

Editar muebles y precios abre el formulario mediante GET sin escribir datos. Guardar usa CotizacionService con transacción, bloqueo del pedido/cotización, permisos efectivos y comprobación de versión. Restablece la aprobación y recalcula costos y precio sugerido. Conservar el diseño de los muebles aceptados y permitir aumentar su cantidad y añadir muebles; no reducir muebles aceptados desde este flujo.

Registrar materiales utilizados sincroniza exactamente cantidades explícitas, incluidos aumentos, reducciones, cero y materiales nuevos, sin multiplicarlas nuevamente. Vacío sigue pendiente. Conservar costos unitarios anteriores; los nuevos usan una referencia positiva del catálogo como estimación. Costo desconocido permanece NULL y bloquea la aprobación; nunca se convierte automáticamente en gratuito. Si falta autorización de cotizaciones, guardar uso y advertir que alguien autorizado debe actualizar costos. Los errores de una sincronización autorizada revierten la operación completa.

El precio_total_acordado del pedido y precio_acordado de cada detalle contienen el acuerdo actual. Guardar o aprobar una cotización no modifica pagos ni saldo. Actualizar pedido exige aprobación de todos los detalles, aceptación del cliente, permisos de precios/planificación, versiones actuales y total no inferior a lo pagado. PedidoService::aceptarCotizacion actualiza el acuerdo y planificación, conservando consumo, autores y fechas. cotizacion_aceptada_version es un marcador técnico de aceptación, sin historial ni copias. Reenvío idéntico es idempotente.

Cierre exige la cotización actual aprobada y aceptada, importes/cantidades coherentes y materiales validados. Casos usan costos actuales de esa cotización y las cantidades utilizadas. Conservar auditoría general de responsables, permisos, pagos y existencias; no realizar descuentos automáticos de materiales por esta limpieza.

Se retiran las dos tablas de cambios, cinco columnas del mecanismo anterior y sus cinco migraciones de modificación/creación. La migración original de pedidos incorpora el marcador de aceptación para instalaciones nuevas. El usuario autoriza consolidar los datos iniciales y omitir respaldos adicionales en esta operación. Ensayar primero en MySQL aislado y verificar precios, pagos, saldos, integridad y datos de otros módulos. No modificar el sistema escolar.



## Planificación: consulta sin edición duplicada — 16/11/2026

Por aprobación del usuario, retirar el botón Editar materiales de la vista de planificación. Presentar materiales necesarios, existencias de referencia y cantidades por comprar. Los cambios presupuestados se realizan desde Editar muebles y precios del pedido, y el consumo desde Registrar materiales utilizados. Mantener cantidades, cálculos, permisos y datos existentes; el ajuste aplicado se limita a la presentación y conserva los servicios, rutas y tablas existentes.


## Quitar materiales y recalcular costos — 16/11/2026

El usuario aprueba Quitar material tanto en Registrar materiales utilizados como en Editar muebles y precios. En filas nuevas sin guardar se retira la fila; en materiales vinculados a un pedido se conserva la referencia y se registra cantidad utilizada/presupuestada cero, con subtotal cero. No se elimina el material del catálogo ni se pierde su trazabilidad. Deshacer retiro permite restaurar los valores antes de guardar. Corregir un consumo positivo exige motivo y permiso efectivo de registrar uso, también si se solicita desde la cotización; el servidor comprueba pertenencia al pedido/mueble y rechaza referencias ajenas. Las solicitudes sin permiso no pueden corregir consumos desde otro formulario.

Se recalculan costos y precio sugerido con el porcentaje configurado, sin inventar uno. Se mantienen costos unitarios capturados y desconocidos NULL; no se convierten en costo real cero. Guardar deja pendiente la aprobación, invalida las validaciones correspondientes y mantiene acuerdo, pagos y saldo hasta aprobar y confirmar aceptación. Operaciones transaccionales con auditoría y control de versiones. Sin nuevas tablas/migraciones, descuentos de existencias ni actualizaciones masivas de registros existentes.


## Retornos contextuales de materiales y cotizaciones — 16/11/2026

Al abrir Registrar materiales utilizados desde Planificación, Cancelar vuelve a esa planificación; si ésta provenía del pedido, conservar su Volver al pedido. Desde Detalle del pedido, Cancelar vuelve a ese detalle, también al abrir directamente el formulario. Conservar el origen al volver de validaciones fallidas. Cotizaciones conserva el retorno al pedido durante Ver → Editar → Guardar/Aprobar; desde su módulo, vuelve al listado de cotizaciones. Usar únicamente rutas internas conocidas y verificar los permisos; nunca redirigir a una URL recibida del cliente. Los parámetros de navegación se excluyen de los datos de negocio validados. No cambiar el destino del guardado de materiales, que sigue mostrando el pedido con sus resultados.


## Pagos organizados por pedido — 16/11/2026

El usuario solicita que Pagos liste pedidos con cliente, total acordado, pagado, saldo pendiente y estado de cobro; incluir pedidos sin pagos. Buscar por ID de pedido o cliente y Limpiar restablece el listado. Ver abre admin.pagos.pedido, con datos del cliente, resumen y pagos anteriores exclusivos de ese pedido. Desde esa ficha, Registrar pago abre el formulario existente con el pedido seleccionado; al guardar/cancelar o corregir un pago, volver a la ficha. Conservar consulta/edición del registro individual y los retornos explícitos al detalle del pedido. Ocultar Registrar pago cuando no hay saldo o falta permiso; mantener también la validación de sobrepago del servidor.

Reutilizar pedidos, pagos y PagoService::resumen con importes decimales exactos. No crear tablas/migraciones, cambiar precios acordados ni conceder permisos. Mantener autorización efectiva de consulta/gestión, auditoría, validaciones e idempotencia. El listado de pedidos sustituye la anterior entrada principal por movimientos; sus enlaces internos no deben reabrir un listado filtrado automáticamente.


## Cotizaciones rechazadas y eliminación segura — 16/11/2026

Por aprobación del usuario se incorpora Rechazada para registrar que el cliente no acepta una cotización sin pedido asociado. Exigir motivo, confirmación, versión vigente y permiso cotizaciones.rechazar. Conservar importes y datos de aprobación anteriores, registrar responsable/fecha/motivo en auditorias y bloquear edición, nueva aprobación, PDF comercial y creación de pedido desde ese estado. La transición de una aprobada a rechazada solo puede cambiar estado, versión y fecha de actualización, sin alterar sus precios o detalles. No implementar reapertura automática. Mostrar/filtar el nuevo estado en listado, ficha, panel y reportes; las rechazadas no suman precios aprobados vigentes ni pendientes de aprobación.

Permitir Eliminar únicamente cotizaciones preliminares en borrador o pendiente_costos, sin aprobación ni pedido, con permiso cotizaciones.eliminar y control de versión. Borrar sus detalles/materiales en una transacción, conservar auditoría y no tocar catálogo, existencias, movimientos, pedidos ni pagos. Claves foráneas siguen activas; cualquier referencia inesperada revierte toda la operación.

Clientes, categorías y materiales comparten EliminacionService::motivo para explicar por qué no se pueden eliminar. El botón queda deshabilitado con explicación accesible cuando existen relaciones, existencias o historial; volver a comprobar en servidor bajo bloqueo y mantener la protección de las FK. La desactivación continúa como alternativa en estos catálogos. No añadir Eliminar a usuarios, tipos de muebles, pedidos, pagos, casos ni movimientos.

Se añaden solamente cotizaciones.eliminar y cotizaciones.rechazar al catálogo de Spatie (50 permisos). Disponibles inicialmente para el propietario y delegables/denegables mediante los permisos generales o individuales existentes; no concederlos automáticamente al maestro. La actualización local agrega solo esas capacidades y no restablece asignaciones anteriores. Sin tablas, columnas ni migraciones nuevas; el motivo del rechazo utiliza la auditoría existente.


## MDF retirado — 16/11/2026

El usuario solicitó expresamente eliminar MDF y sus registros relacionados. Se retiraron 1 material, 4 cotizaciones, 1 pedido y 10 casos históricos de MDF, sus 54 líneas de materiales de casos, 17 de cotizaciones, 4 de pedido, 4 detalles de cotización, 1 detalle de pedido, 2 estados, 1 existencia y 1 movimiento. No se eliminaron pagos: el pedido afectado no tenía ninguno. Los registros ajenos a la retirada, incluidos cuentas, contraseñas, roles, permisos, clientes, ajustes, pagos y otros materiales/estados/consumos, se comprobaron idénticos antes/después. Auditoría histórica conservada; dos eventos de sistema documentan la retirada y la actualización del catálogo, sin atribuir la operación a un usuario que no la ejecutó por interfaz.

Materiales principales actuales: Madera y Melamina. Seeders y JSON ya no generan MDF. El catálogo conserva 25 posiciones originales de lote y la carga completa 27 casos (25 de catálogo y 2 desde pedidos). Se registra una nueva huella del catálogo mediante evento adicional; no se reescriben las auditorías anteriores ni se renumeran los registros. DatosSistemaSeeder cuenta los IDs que aún existen para su resumen, manteniendo el reconocimiento de los lotes y evitando recarga/duplicación tras eliminaciones autorizadas.

El perfil activo de similitud se actualizó a casos-historicos-v1-sin-mdf en el mismo archivo de configuración: 25 referencias, rangos recalculados a partir de ellas y nueva huella SHA-256. Distancia, pesos, k=3 y umbral=80 permanecen iguales. Algunas coberturas se reducen al faltar sus referencias extremas de MDF; no se amplían ni se inventan antecedentes. Los IDs esperados en pruebas se resuelven por posición de lote para conservar la identidad del caso aunque una instalación nueva tenga otra numeración.

Estado local: 3 usuarios, 5 categorías, 36 clientes, 26 materiales, 22 cotizaciones, 10 pedidos, 4 pagos y 27 casos históricos; 31 tablas, 27 migraciones y 50 permisos. No se modificó el esquema, el sistema escolar ni sus datos.


## Casos vinculados a pedidos — 16/11/2026

Decisión expresa del usuario: retirar los 25 casos sin pedido y completar 87 casos, todos con detalle de pedido. Se conservan los 2 casos vinculados anteriores y se generan 85 mediante 37 pedidos adicionales, con distintas líneas de muebles. Distribución final: 12 pedidos con 1 caso, 12 con 2, 9 con 3 y 6 con 4; 39 pedidos aportan los 87 casos. Un caso representa una línea de muebles iguales, aunque su cantidad física sea mayor que uno.

CasosHistoricosSeeder utiliza los servicios de cotización, aprobación, pedidos, fabricación y retención; FabricacionSeeder comparte el cierre validado. Se conservan materiales/cantidades, costos estimados y precio del grupo, sin multiplicar los totales dos veces. Las 25 entradas JSON son plantillas de referencia reutilizadas; no son 25 casos adicionales ni acreditan 87 diseños únicos. Se mantienen 25 vínculos técnicos con el perfil fijo de similitud; no se cambian sus pesos, rangos, k ni umbral. La procedencia sigue siendo simulada y la revisión humana pendiente.

No se reinician cuentas, permisos, existencias, pagos ni registros ajenos a los 25 casos retirados y sus líneas. La repetición del seeder conserva las ediciones y no duplica pedidos/casos; la cifra de 87 corresponde a esta carga, no impide retener nuevos trabajos posteriormente. Sin nuevas tablas ni migraciones. Estado y comprobaciones en docs/INFORME-CASOS-PEDIDOS-87.md.


## Propuesta descartada: cabecera compacta — 17/11/2026

El usuario eligió el bloque «Tu taller, al día»: bienvenida azul oscuro con remate diagonal menta y acceso Nueva cotización a la izquierda; cuatro indicadores en cuadrícula 2×2 a la derecha: Cotizaciones (total general), Pedidos (total general), Casos históricos y Clientes. Sustituye las ocho tarjetas anteriores, sin duplicarlas debajo. Cada total y enlace se consulta/muestra solo con el permiso correspondiente; no usar cifras fijas de la maqueta. Mantener el menú lateral, la navegación superior, el gráfico, las cotizaciones recientes y el tablero de pedidos existentes. En pantallas pequeñas, bienvenida sobre los indicadores. Sin cambios de MySQL, permisos ni esquema.

Verificación: npm run build y Pint aprobados; DashboardTest: 6 pruebas y 49 aserciones en SQLite aislado. Edge/Playwright: propietario y maestro, 1440/1024/390 px, tema claro/oscuro, enlaces y totales, sin desbordamiento horizontal ni errores JavaScript. Capturas y resultado: docs/capturas/dashboard-resumen-2026-11-17/. Los números de las capturas corresponden exclusivamente a la base temporal de pruebas.


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

Excel Registros-El-Tajibo.xlsx generado desde una consulta de la base actual: 47 pedidos, 620 líneas de costos, 82 pagos y 36 clientes, en cuatro hojas. Es una exportación editable y no una conexión en vivo. Conserva precios/cantidades de la base; mano de obra/otros costos mantienen su carácter estimado. Detalle en docs/HISTORIAL-CARPINTERIA.md.


## Hora boliviana en guardados automáticos — 17/11/2026

Laravel/PHP usan APP_TIMEZONE=America/La_Paz. MySQL y MariaDB configuran ahora cada conexión con DB_TIMEZONE=-04:00, también como valor predeterminado, evitando depender de la zona SYSTEM del servidor. Mantener ambas variables en .env.example. La configuración se aplica de forma central a los timestamps y a las vistas existentes; no introducir restas manuales de cuatro horas en controladores o Blade. No cambiar globalmente la hora de Windows/MySQL ni afectar el sistema escolar.

El diagnóstico de la sesión actual (PHP web 8.4.15, consola 8.3/8.4 y MySQL) ya coincidía con Bolivia antes del cambio; no se reprodujo un adelanto en nuevos guardados. Se fijó explícitamente la conexión para mantener esa coherencia. No se desplazaron timestamps existentes ni se recalcularon las horas fijas del historial. No hay evidencia suficiente para tratarlos todos como UTC. El diagnóstico local temporal se eliminó inmediatamente.

Verificación: copia MySQL restaurada, 13 módulos y 63 campos de fecha/hora guardados mediante servicios, incluyendo usuarios/personal, catálogo, cotizaciones/aprobación, pedidos, estados, materiales utilizados, existencias/movimientos, casos, pagos y auditoría. Todos coinciden con el instante local; la base operativa quedó idéntica. Copia temporal retirada. FaseCuatroATest: 30 pruebas y 285 aserciones aprobadas; incluye cruce de medianoche UTC (02:30 UTC equivale a 22:30 del día anterior en Bolivia), persistencia y vista. Edge/Playwright con PHP 8.4: hora de actualización contrastada con la cabecera HTTP Date convertida a America/La_Paz; alta/edición/aprobación/PDF, roles, móvil y oscuro aprobados. Pint aprobado. Capturas: docs/capturas/cotizaciones-hora-bolivia-2026-11-17/. Evidencia de MySQL: storage/app/private/hora-bolivia-actual.json.


## Listados de más recientes a antiguos — 17/11/2026

Decisión del usuario: ordenar los listados por fecha descendente, con ID descendente para desempatar. Cotizaciones usa fecha; Pedidos, Planificación y el listado de pedidos de Pagos usan fecha_aceptacion; Casos históricos usa fecha_referencia; pagos de un pedido mantienen fecha. Clientes, Categorías, Materiales, Existencias (listado de materiales), Usuarios, Tipos de muebles, Roles, Movimientos y Auditoría usan created_at. No usar únicamente ID como sustituto de fecha para los registros históricos.

Aplicar también a los pedidos dentro de Ver cliente, los materiales de Ver categoría y el reporte de saldos. Conservar filtros, paginación y permisos. Los selectores de formularios y los grupos de permisos conservan su organización; el tablero del dashboard mantiene sus prioridades de entrega. No ordenar por updated_at ni promover un registro antiguo por una edición. Sin cambios en datos, esquema o MySQL.


## Orden vigente por ID descendente — 17/11/2026

El usuario corrige expresamente la decisión anterior: los listados deben ordenarse por ID de mayor a menor, sin priorizar fechas ni nombres. Aplicar a los listados principales y relacionados de clientes, categorías, materiales, existencias/movimientos, usuarios, roles, tipos de muebles, cotizaciones, pedidos, planificación, casos, pagos y auditoría, y a las filas de reportes. Se conservan los filtros por fecha y la paginación. Los selectores, grupos de permisos y prioridades del tablero del dashboard no se alteran. Esta decisión sustituye el orden cronológico solicitado anteriormente. Sin cambios en los datos almacenados.
