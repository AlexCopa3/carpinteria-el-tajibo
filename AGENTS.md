# Instrucciones vigentes para Codex — Carpintería El Tajibo

Consolidadas el 3 de noviembre de 2026. Este documento expresa decisiones actuales; los informes de fases son históricos. Consultar docs/INFORME-ESTADO-ACTUAL.md para el estado comprobado del código y del entorno.

## 1. Misión
Desarrollar un **sistema web de asistencia para la cotización de pedidos de muebles y planificación de materiales para su fabricación**, aplicado a la Carpintería El Tajibo (La Paz y El Alto, Bolivia), mediante **razonamiento basado en casos** y **k-nearest neighbors** para recuperar antecedentes similares.

Lee íntegramente `REQUISITOS.md` antes de proponer cambios. En caso de discrepancia, presenta la contradicción y solicita una decisión; **no la resuelvas suponiendo requisitos**.

## 2. Proyecto independiente y referencia escolar

Trabajar en C:/wamp64/www/carpinteria-el-tajibo. El proyecto C:/wamp64/www/sisgestionescolarlaravel es únicamente referencia: nunca modificar sus archivos ni su base.

Mantener las estructuras familiares comprobadas en archivos reales: controladores por entidad, métodos CRUD, modelos Eloquent, migraciones individuales, rutas explícitas /admin con nombres admin.*, Blade por módulo y SweetAlert2. Leer docs/CONVENCIONES-CODIGO.md. Reutilizar el estilo, sin copiar entidades, datos, credenciales, consultas o errores escolares.

Antes de una refactorización importante, presentar ejemplos, archivos afectados y alcance y obtener aprobación. No reconstruir o reorganizar automáticamente código que funciona.

## 3. Tecnologías y presentación
- Backend: Laravel / PHP; frontend: Blade y Bootstrap; plantilla administrativa: AdminLTE; base de datos: MySQL/MariaDB.
- Stack aprobado: Laravel 12, PHP ^8.3, Blade, JeroenNoten/AdminLTE 3 y Bootstrap 4. Versiones instaladas en docs/ARQUITECTURA.md y archivos lock. No mezclar paquetes AdminLTE ni introducir nuevas tecnologías sin necesidad.
- Diseño visual de referencia: `referencias/dashboard-referencia.png` (menú azul oscuro, tarjetas con degradados de colores, tablas limpias, gráficos, iconos y fondos claros). La imagen expresa el **estilo**, no las entidades de negocio del sistema escolar.
- Separar layout, componentes Blade reutilizables y vistas por módulo; estilos propios en `resources/`, nunca editar `vendor/` o `node_modules/`.
- Interfaz en español, moneda boliviano (Bs), fechas y medidas comprensibles en el entorno boliviano. Mantener accesibilidad y visualización en distintos tamaños de pantalla.

## 4. Roles y autorización

**Decisión vigente del 13/11/2026:** cualquier permiso puede delegarse o retirarse por rol y por persona. La decisión individual prevalece; las restricciones por rol de apartados/entregas anteriores expresan la configuración de partida y quedan sustituidas por esta decisión. Véase docs/PERMISOS.md y el alcance confirmado al final.
- **Propietario:** cotizaciones y aprobación del precio final; margen de ganancia y pagos; administración del catálogo y usuarios; revisión de materiales e historial.
- **Maestros carpinteros (dos inicialmente):** consultar todos los pedidos y cotizaciones; consultar costos de materiales y mano de obra; crear cotizaciones preliminares y modificar sus costos estimados antes de la aprobación; planificar, revisar, actualizar y validar materiales y cantidades utilizados durante la fabricación; registrar cambios de fabricación. **Configuración inicial: sin aprobación final, pagos, ganancias, márgenes administrativos ni gestión de usuarios. El propietario puede conceder o retirar cualquiera de estas capacidades por rol o por persona; una excepción individual prevalece.**
- Implementar permisos diferenciados con Spatie Permission para `cotizaciones.consultar`, `cotizaciones.crear`, `cotizaciones.modificar` y `cotizaciones.aprobar`. Propietario: los cuatro; maestro: los tres primeros, con modificación de estimaciones únicamente antes de aprobar. Validar importes y bloquear campos de aprobación o administrativos en solicitudes preliminares; no confiar en valores de rol, estado o aprobador enviados por el cliente.
- La autorización se aplica en servidor mediante políticas/middleware y validación HTTP, no solo ocultando botones. Los CRUD simples validan en controlador; las operaciones complejas conservan Form Requests. Proteger los datos en consultas, endpoints y archivos PDF.
- No permitir registro público de usuarios en producción; definir flujo de alta por propietario.

## 5. Módulos requeridos
1. Autenticación y gestión de usuarios/roles.
2. Clientes.
3. Catálogo de materiales (unidades y precios actualizables).
4. Módulo sencillo de Existencias de materiales: consultar, agregar y corregir cantidades, separado del catálogo y sin recuperar el Inventario anterior.
5. Cotizaciones: especificaciones, costos, recomendaciones, edición y aprobación del propietario; PDF imprimible.
6. Pedidos y seguimiento por estados.
7. Planificación de materiales por pedido y su posterior corrección.
8. Casos históricos y motor de similitud.
9. Pagos (anticipo y saldo), solo para roles autorizados.
10. Reportes simples y panel principal.

Sin fotografías, planos ni adjuntos de diseños de muebles en la primera versión.

## 6. Reglas funcionales que no se deben vulnerar
- Tipos iniciales: escritorios, mesas, sillas, armarios, estantes y puertas. Para pedidos con tipos diferentes, registrar cada tipo como una línea/unidad independiente de planificación y caso.
- Características del mueble: tipo, dimensiones (largo/ancho/alto), puertas, cajones, divisiones, material principal, materiales secundarios, acabado, cantidad y observaciones.
- **k-nearest neighbors compara únicamente casos del mismo tipo de mueble**. Normaliza las variables cuantitativas; documenta la distancia, pesos, similitud, valor de k y umbral en configuraciones comprobables. No fijar arbitrariamente parámetros definitivos sin propuesta y validación.
- Presentar **hasta tres casos elegibles más similares y sus porcentajes de similitud**, junto con precio y materiales/cantidades recomendados. Los costos separan materiales, mano de obra, otros costos y margen.
- Mostrar **dos estimaciones**: la basada en antecedentes y la actualizada utilizando precios vigentes del catálogo. El propietario decide el precio final.
- Mano de obra sugerida a partir de casos comparables, editable por propietario y maestros antes de la aprobación; margen predeterminado configurable y editable por cotización exclusivamente por el propietario.
- Si no se supera el umbral mínimo, mostrar **«No hay antecedentes suficientemente similares»** y permitir cotización/planificación manual. No presentar recomendaciones de similitud baja como confiables.
- Permitir editar cantidades y materiales. **No realizar despiece o escalamiento geométrico automático** por diferencia de dimensiones; las recomendaciones son referencias editables.
- Cotizaciones rechazadas: estado `Rechazada`, no entran en la base de casos. Pedido aceptado avanza por `Aceptado` → `En fabricación` → `Terminado` → `Entregado`.
- **Caso real nuevo**: al marcar `Terminado`, tras completar y validar datos finales, incorporarlo automáticamente a la base de casos, antes de la entrega. Registrar la fecha y el responsable de la validación; evitar inserciones duplicadas.
- No descontar inventario al fabricar ni generar movimientos por cambios de planificación. En la futura fase autorizada, revisar materiales necesarios, disponibles y por comprar de forma sencilla. Conservar las cantidades planificadas y realmente utilizadas para casos.
- Generar un PDF de cotización con folio, características, cantidad, precio aprobado y desglose visible permitido; nunca mostrar márgenes de ganancia en el PDF del cliente.

## 7. Datos sintéticos, evaluación y honestidad académica
**No existen 30–40 pedidos históricos completos reales verificados.** La cifra previa era ficticia. Se planea crear **35 casos sintéticos de demostración** plausibles para Bolivia y someterlos a revisión del propietario y maestros carpinteros; **la revisión aún no está realizada**.

- Identificar explícitamente `origen = simulado | real`, y registrar un estado de revisión (`pendiente`, `revisado`, etc.) separado del origen.
- No describir casos sintéticos como ventas o fabricación real, ni simular evidencia de entrevistas, validación, tiempos observados, precisión real o comparación antes/después.
- Mantener separados los reportes experimentales de pruebas sintéticas y la evaluación sobre casos reales posteriores. Toda métrica debe indicar procedencia y metodología.
- Construir generadores/seeders reproducibles con datos sintéticos **etiquetados**. Antes de proponer precios bolivianos como realistas, indicar su carácter ilustrativo y solicitar validación local; no inventar cotizaciones de proveedores como si fueran fuentes reales.
- No contaminar la base de casos real con pruebas sin una separación explícita y configurable. Si se utilizan casos sintéticos en el motor durante desarrollo, señalarlo en la interfaz y resultados.

## 8. Arquitectura y calidad
- Mantener lógica compleja fuera de controladores/vistas y conservar los servicios activos. Los futuros CotizacionService y RecuperacionCasosService y sus políticas se diseñarán al autorizar sus módulos.
- Usar migraciones versionadas, validación de entradas, transacciones en inventario, autorización, auditoría mínima y controles de integridad.
- Guardar importes con `decimal` (no `float`); manejar unidades correctamente. Definir reglas de redondeo y registrar el costo usado al aprobar una cotización para que el historial no cambie cuando cambie el catálogo.
- Priorizar pruebas de permisos, flujos de estados, movimientos de inventario e idempotencia, generación del PDF y selección de casos similares.
- Ejecutar pruebas y señalar limitaciones; no declarar funcionalidad comprobada sin evidencias de ejecución.
- Commits/cambios pequeños, descriptivos y reversibles. No sobrescribir código o datos no relacionados.

## 9. Orden de entrega
1. Informe de análisis del proyecto escolar (cuando esté disponible), inventario de reutilización y compatibilidad.
2. Propuesta de arquitectura, migraciones/entidades, roles y plan por módulos.
3. Diseño base AdminLTE según imagen y autenticación/permisos.
4. Clientes, catálogo e inventario.
5. Cotizaciones, plan de materiales, pedidos y PDF.
6. Casos sintéticos claramente separados, recuperación k-nearest neighbors y recomendaciones duales.
7. Pagos, reportes, pruebas y documentación de instalación.

**No implementar todo de golpe sin revisión**: realizar entregas incrementales y advertir decisiones pendientes de `REQUISITOS.md`.

## 10. Alcance y estado actual

Las fases 1–3 y los cambios puntuales autorizados dieron lugar al código de clientes, categorías, materiales/presentaciones, inventario, cuentas/permisos, auditoría y ajustes. No empezar cotizaciones persistentes, pedidos, fabricación, pagos, PDF, motor de casos o los 35 casos sintéticos sin autorización.

No dar por aplicado un esquema por existir su migración. La revisión del 3 de noviembre encontró 16 archivos de migración y solo 13 registrados en MySQL; faltan tablas/columnas y el JavaScript publicado de SweetAlert2. Verificar el estado efectivo antes de cualquier trabajo. La revisión documental no autoriza reparar esos problemas.

## 11. Convenciones de código confirmadas

- Controladores sencillos: Request, authorize, request->validate, new Modelo, asignaciones campo por campo y save. Clientes y categorías conservan transacción y auditoría dentro del controlador. Diferenciar campo omitido de null.
- Materiales, presentaciones y usuarios validan en sus controladores; CatalogoService y UsuarioService conservan operaciones relacionadas y garantías. Inventario y permisos mantienen servicios y Form Requests. No reunir toda la lógica compleja en controladores.
- UsuarioController está junto a los controladores de negocio en app/Http/Controllers. Mantener nombres descriptivos, comentarios útiles y formato legible; evitar capas genéricas innecesarias.
- Rutas individuales /admin y admin.*, POST create y PUT/PATCH update cuando corresponda; conservar nombres, URLs, modelos enlazados, middleware y organización por módulo. No usar Route::resource para sustituirlas.
- Vistas admin/<modulo> con index/create/edit/show según la operación; campos explícitos form-group/label/input/select/textarea, old, errores, CSRF y @method. Inventario conserva resumen/confirmación y el historial inmutable, sin forzarlo a CRUD.
- Una migración de creación por tabla de negocio. Los grupos internos originales de Laravel/Spatie son excepciones. Durante desarrollo integrar cambios en la creación según decisión del usuario; no generar add/remove automáticamente.
- Si la base ya está aplicada: antes de adaptar esquema/historial se requieren respaldo, comprobación de dependencias y preservación de datos. Consultar cuando haya que apartarse de la organización acordada. No ejecutar migrate:fresh, db:wipe ni reiniciar bases reales.

## 12. Catálogo, costos e inventario actuales

Categorías se selecciona desde tabla categorias mediante categoria_id. El maestro puede consultar, pero no administrar categorías ni catálogo, aun con un permiso administrativo concedido por error. La categoría inactiva no puede asignarse a materiales nuevos; puede conservarse en un material que ya la tenga.

No hay código de material en interfaz ni en el esquema final definido por las migraciones; conservar el ID interno y los códigos de unidad. No reescribir instantáneas JSON históricas. La base local desalineada no cambia esta decisión.

Entrada: cantidad siempre mayor que cero. No admitir nuevas entradas con costo cero ni la opción de material gratuito, según decisión del 4 de noviembre de 2026. Los costos conocidos deben ser positivos. Costo desconocido: importes NULL y estado pendiente, nunca convertirlo automáticamente en costo real cero para cotizar. Conservar factor, cantidades y costos históricos de la presentación. No descontar por pedidos/fabricación todavía.

Mantener aritmética decimal, transacciones, revalidación del servidor, bloqueos, unicidad de operación e idempotencia. Detalles de precisión en docs/DISENO-DATOS.md. Las reglas de entregas/devoluciones futuras siguen sujetas a los requisitos y decisiones pendientes.

## 13. Auditoría, eliminación y acceso

Registrar actor real, fecha y datos anteriores/nuevos en altas/modificaciones de clientes, materiales, categorías, presentaciones y usuarios; también cambios de permisos y ajustes. No inventar responsables anteriores. Nunca auditar contraseñas, hashes ni tokens. Creación inicial por consola como proceso del sistema y recuperación de contraseña sin guardar el secreto.

Mostrar responsables y primera/última modificación en fichas Ver, fuera de edit. No mostrar Historial de cambios en el menú ni enlazarlo desde las fichas; conservar su auditoría interna y la ruta protegida.

Solo propietario puede eliminar físicamente clientes, categorías, materiales y presentaciones sin relaciones impeditivas. Confirmar con SweetAlert2 y revalidar transaccionalmente. Material requiere saldo cero y ausencia de presentaciones/operaciones/movimientos/cambios. Conservar el evento de eliminación. Usuarios se desactivan; movimientos se compensan, no se editan ni borran.

Usuarios y categorías tienen estado activo/inactivo. Cuenta inactiva no inicia sesión ni conserva acceso administrativo. El propietario no puede desactivarse, quitarse su rol ni dejar el sistema sin propietario activo.

Permisos por casillas para el rol completo desde Usuarios → Ver permisos, sin opción lateral separada. Rechazar permisos incompatibles y retirada de los obligatorios. Mantener las capacidades operativas confirmadas del maestro y sus restricciones administrativas. El seeder respeta personalizaciones auditadas.

## 14. Presentación y avisos

- Formularios con secciones, títulos/iconos y dos o tres columnas según el tamaño del dato; campos largos con más espacio y apilado móvil. No inventar campos para completar filas.
- Etiquetar los campos obligatorios según las reglas reales; ayudas y errores accesibles con aria-describedby/aria-invalid y nombres comprensibles.
- Tablas paginadas, desplazamiento horizontal interno y acciones table-actions en una fila de altura uniforme. Estado activo verde con check e inactivo gris con pausa.
- Botones con icono junto al texto, degradado, esquinas redondeadas, bordes suaves sin contorno oscuro y foco visible. Misma familia/tamaño/peso; inline-flex y centrado. Registrar azul; editar/actualizar turquesa; eliminar coral; Ver celeste.
- Volver y Cancelar plomo; Limpiar rosa; Movimientos índigo; Categorías ocre; Buscar azul petróleo; Salir ciruela. Cabecera azul al registrar/revisar/confirmar, verde al actualizar/guardar cambios.
- No mostrar «Próximamente» en el menú. Las opciones pendientes permanecen sin rutas funcionales.
- Mantener avisos mensaje/icono y Swal.fire centralizado con @json; AjusteController conserva with('swal', [...]). Recursos locales, sin editar vendor ni node_modules.

## 15. Ajustes del negocio

AjusteController conserva el flujo del ejemplo del usuario: index lee Ajuste; store valida, consulta la primera ficha con bloqueo, asigna propiedades desde request, guarda logo si corresponde, save y redirect con swal. Autorización, transacción auditada y manejo seguro del logo permanecen integrados. No reintroducir GuardarAjustes/LogoAjusteController ni capas adicionales sin solicitud.

Una única ficha de aplicación con ID autoincremental mediante table->id y Eloquent normal; no asignar id=1, aceptar ID del formulario ni renumerar registros. El bloqueo se conserva en el guardado. No afirmar que exista una restricción SQL de ficha única: no la hay.

Campos del formulario: nombre, descripción, dirección, teléfono, email, web y logo. La moneda no se solicita; config/tajibo.php fija BOB y los importes se muestran en Bs, sin incorporar márgenes u otras funciones. Solo propietario administra; los maestros pueden ver el logo con sesión. Nombre/logo en menú y nombre en pie; login conserva su diseño.

Logos JPG/PNG y SVG gráficos hasta 2 MB. LogoSeguro rechaza contenido activo y referencias externas. Retirar logo anterior después de confirmar; eliminar nuevo si falla la transacción. Servir por ruta protegida, sin requerir storage:link.

## 16. Documentación y verificación

REQUISITOS.md conserva los requisitos funcionales. ARQUITECTURA, DISENO-DATOS, CONVENCIONES-CODIGO y PERMISOS describen las referencias vigentes. VERIFICACION e INFORME-ESTADO-ACTUAL registran la revisión actual; informes de fases y cambios son antecedentes.

No modificar requisitos para justificar diferencias del código. Señalar discrepancias, no resolverlas silenciosamente. No borrar Markdown automáticamente ni presentar pruebas anteriores como recién ejecutadas. Ejecutar pruebas únicamente en entornos aislados; MySQL real solo se consulta en esta revisión documental. Entregas para compartir deben excluir .env, secretos, credenciales, datos personales, dependencias y bases de datos.

## Estado después de la corrección aprobada — 3 de noviembre de 2026

La desalineación diagnosticada se corrigió con autorización expresa: 26 tablas InnoDB y 16 migraciones registradas, con respaldo nuevo restaurado y datos previos conservados. SweetAlert2 está publicado y verificado. Consultar docs/INFORME-CORRECCION-BD.md para el estado final y las pruebas; los diagnósticos anteriores describen el estado previo. No repetir auxiliares de corrección ni inferir autorización para nuevos cambios de base o fases.
## Preparación autorizada de fase 4 — 3 de noviembre de 2026

El usuario autorizó únicamente el análisis y la preparación de cotizaciones, pedidos y planificación en cuatro entregas (4A/4B/4C/4D). Alcance original en docs/ALCANCE-FASE-4.md; propuesta pendiente de revisión en docs/DISENO-FASE-4.md y estado en docs/FASE-4.md. Antes de migraciones nuevas o cambios grandes, presentar el diseño y esperar aprobación. La preparación no autoriza implementar las cuatro entregas de una vez.

Decisiones expresamente confirmadas: recargo sobre costo, dimensiones en centímetros y materiales/mano de obra por unidad de mueble multiplicados por la cantidad. Permitir ajustes justificados por ahorros de fabricación conjunta, conservando valores unitarios y totales nominales/aplicados, motivo y responsable. El precio final y el recargo siguen exclusivos del propietario; los maestros modifican estimaciones antes de aprobar. No asumir un porcentaje predeterminado concreto.

Para fase 4 no modificar las migraciones anteriores ya aplicadas. Crear únicamente las migraciones nuevas aprobadas por entrega, conservando una tabla por archivo, FK, decimales, transacciones, idempotencia y permisos. Esta instrucción específica prevalece sobre la consolidación histórica de fases previas.

No implementar casos históricos, datos sintéticos, pagos ni k-nearest neighbors en fase 4. En 4D se conservarán datos finales y validaciones por detalle para integrar la retención al terminar/validar en fase 5, antes de la entrega. El objetivo funcional final permanece vigente; su implementación se difiere por la instrucción expresa del usuario.
## Autorización y entrega de fase 4A — 3 de noviembre de 2026

El usuario aprobó e implementó exclusivamente cotizaciones manuales, cálculo de costos, validación/aprobación del propietario y PDF. Estado comprobado: docs/FASE-4.md; MySQL 31 tablas InnoDB/21 migraciones, cinco nuevas del lote 5, anteriores intactas y respaldo restaurado. La autorización de 4A no habilita 4B ni fases posteriores.

Otros costos: total por detalle, sin multiplicación por muebles. Precio final aprobado por detalle; total como suma. Recargo predeterminado configurable exclusivamente por propietario, inicialmente sin valor; no inventar 30 % ni otro porcentaje. Mantener nominales/unitarios y ajustes conjuntos justificados. Desconocido NULL nunca equivale a costo cero.

Validar y aprobar es una sola acción del propietario con ambos permisos, versión y costos revisados. Una aprobada no se modifica; no agregar revisión comercial sin procedimiento aprobado. Conservar Form Requests/servicio en este flujo complejo, CRUD explícitos, rutas /admin y nombres admin.*, vistas create/edit/show/index, transacciones, auditoría y límites administrativos para maestros. Cotizar/aprobar no descuenta inventario.

La base local no contiene cotizaciones de prueba; las fixtures y los porcentajes de ejemplo viven solo en SQLite/MySQL aislados. Mantener datos reales separados. No repetir auxiliares privados de aplicación como instaladores. No implementar pedidos, planificación, fabricación, casos, pagos, datos sintéticos ni k-nearest neighbors en 4A. Esperar revisión del usuario antes de 4B.

## Ajuste autorizado de 4A — 4 de noviembre de 2026

El usuario retiró la opción de materiales gratuitos de inventario y cotizaciones. Quitar opciones, motivos específicos y lógica de alta; el servidor acepta costo conocido positivo o costo pendiente NULL. No transformar costos desconocidos en cero ni reescribir historial previo. Los campos/restricciones anteriores se conservan exclusivamente por compatibilidad histórica; no habilitan altas desde la aplicación.

La cotización nueva no pide cliente ni datos personales al registrar, editar, validar, aprobar o emitir PDF. Los datos se solicitarán al aceptar el pedido, en la futura fase autorizada correspondiente. No implementar aceptación ni pedidos ahora. Conservar el módulo Clientes y relaciones previas; cliente_id de cotizaciones admite NULL mediante migración nueva no destructiva, sin modificar los 21 archivos anteriores. Las nuevas solicitudes no aceptan cliente_id.

Conservar permisos, recargo sobre costo, nominales/ajustes justificados, otros costos por detalle, aprobación del propietario e inmutabilidad. Ver docs/AJUSTES-COTIZACIONES-2026-11-04.md para resultados y respaldo. No avanzar a 4B.


## Simplificación de Ajustes — 4 de noviembre de 2026

Ajustes solo contiene los datos del negocio. BOB/Bs está fijo en config/tajibo.php y no se solicita ni persiste como un ajuste. Por confirmación expresa del usuario, la creación de ajustes se consolidó en una única migración limpia y se retiró el archivo auxiliar junto con su entrada exacta del historial, con respaldo restaurado. Esta es una excepción autorizada a la regla de no editar migraciones anteriores: se modifica únicamente la creación de ajustes, conservando las demás. Código y MySQL quedan con 22 migraciones; no se renumeran IDs ni se reescriben auditorías o datos del negocio. Mantener autorizaciones, auditoría y manejo seguro de logos.


## Usuarios y perfil propio — 4 de noviembre de 2026

El usuario aprobó añadir tipo/número de identificación obligatorios al registrar desde Usuarios, teléfono y dirección opcionales. Tipos admitidos: CI y pasaporte. Se normaliza el tipo a minúsculas y el número a mayúsculas sin quitar ceros iniciales; no se admiten espacios/puntos internos y se conserva el complemento con guion. Unicidad por tipo y número.

La contraseña inicial se genera exclusivamente en servidor como tj. + número normalizado de identificación y se almacena mediante el cast hashed. El prefijo tj. abrevia El Tajibo, no el tipo ni la expedición del documento. No solicitar contraseña ni estado en alta: la cuenta empieza activa y con password_temporal=true. Bloquear otros módulos hasta que cambie esa clave en Mi perfil. Mantener las reglas existentes para la nueva contraseña (12 caracteres, mayúscula/minúscula/número/símbolo y máximo 72 bytes), confirmación y verificación de la actual. Nunca auditar claves, hashes o tokens. Rotar sesión/token y retirar otras sesiones del mismo usuario al cambiar la contraseña.

Mi perfil permite modificar únicamente nombre, correo, teléfono, dirección y contraseña del usuario autenticado. Rol, estado e identificación se administran por propietario desde Usuarios. El propietario tampoco pierde las protecciones contra desactivar su cuenta o dejar al sistema sin propietario activo. Usuarios inactivos pierden acceso al perfil y a los demás módulos.

La estructura vigente separa acceso en users y datos personales en personal, mediante User::personal() hasOne y Personal::user() belongsTo. users conserva nombre, correo, contraseña, estado y campos técnicos de autenticación, incluido password_temporal. personal contiene user_id único/FK, tipo/número de identificación, teléfono y dirección. Una creación por tabla; no duplicar estos cuatro datos en users. La separación se aplicó con respaldo restaurado, traslado y comparación exacta antes de retirar las columnas antiguas. Los IDs, contraseñas, roles, permisos y auditorías previos se conservan.

No restablecer automáticamente la contraseña cuando el propietario corrija una identificación. Estado y documento se muestran en Ver/Editar; el usuario puede ver su documento en Mi perfil, sin editarlo. Mantener controladores explícitos, rutas /admin y admin.*, transacciones y UsuarioService. Este ajuste de Usuarios no autoriza pedidos ni fases nuevas. Ver docs/USUARIOS-PERFIL-2026-11-04.md.



## Separación users/personal autorizada — 4 de noviembre de 2026

El usuario solicitó mantener nombre/email/password/estado en users y trasladar identificación/contacto a una tabla llamada exactamente personal. Mantener id, email_verified_at, remember_token, timestamps y password_temporal como datos técnicos de autenticación en users. No duplicar nombre/correo/contraseña en personal. Relación uno a uno por personal.user_id único, con FK restrictiva hacia users.id. Los IDs de personal son autoincrementales independientes; no asumir que coinciden con users.id. La unicidad de documento pertenece a personal y al editar se ignora el ID de la ficha, no el ID del usuario.

Una sola operación de Usuarios guarda ambas tablas en la misma transacción y conserva la auditoría agregada del usuario, sin secretos. Mi perfil obtiene el usuario de la sesión y su ficha relacionada; no recibe el user_id del formulario. Mantener el mismo formulario y menú, contraseña inicial tj. + número, cambio obligatorio, permisos e inmutabilidad de contraseñas al corregir documento. La copia de registros previos no inventa identificaciones ni responsables, y conserva las fechas heredadas. La tabla nueva se crea mediante 2026_11_04_150000_create_personal_table.php; la creación de users se mantiene limpia sin los cuatro campos trasladados. No crear migraciones adicionales de add/remove para repetir esta adaptación ya aplicada. Estado: 32 tablas y 23 migraciones. Evidencias: docs/SEPARACION-USERS-PERSONAL-2026-11-04.md. No autoriza nuevos módulos ni fases.

## Ajuste visual aprobado — 4 de noviembre de 2026

El usuario prefiere mantener el estilo actual y el menú lateral. Mejorar únicamente espacios, alineación, legibilidad de formularios, tablas, botones y encabezados. Conservar los colores de las acciones y el diseño del menú; no alterar campos, validaciones, cálculos, permisos ni funcionalidades por esta revisión. Evidencias: docs/AJUSTE-VISUAL-2026-11-04.md.

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

## Limpieza autorizada — 4 de noviembre de 2026

Retirar vistas y componentes sin rutas ni consumidores y archivos de ejemplo redundantes, verificando dependencias y regresión. Los contratos preparatorios de cotizaciones utilizados exclusivamente por pruebas están en tests/Fixtures/Cotizaciones; no reintroducirlos en app. Mantener GuardarCotizacionRequest y AprobacionCotizacionRequest vigentes, modelos, migraciones, dependencias de existencias y compatibilidad histórica comprobada. No confundir archivos antiguos todavía usados por pruebas o integridad con archivos huérfanos. Conservar respaldos y evidencia documental; no limpiar MySQL por esta instrucción. Detalle y verificación: docs/LIMPIEZA-CODIGO-2026-11-04.md.

## Retirada definitiva del módulo antiguo — 4 de noviembre de 2026

Por corrección expresa del usuario, eliminar el módulo antiguo de Inventario y Presentaciones de las rutas y del árbol de aplicación: controladores, políticas, Form Requests, vistas, servicio anterior, configuración de reactivación y JavaScript exclusivo. No conservar ese código por sus pruebas: retirar las pruebas de operaciones abandonadas y mantener las del alcance vigente y la preservación del historial. ExistenciaMaterialService contiene solo las operaciones de cantidad de Materiales. No reintroducir InventarioService ni el interruptor inventario_habilitado.

Los modelos OperacionInventario/MovimientoInventario, EntradaMaterial/PresentacionCompra y las migraciones permanecen porque representan las tablas existentes, las existencias actuales y sus relaciones históricas. No borrar ni renombrar tablas, columnas o registros por una limpieza de archivos. Las rutas antiguas responden 404. Esta decisión prevalece sobre instrucciones anteriores de conservar el flujo ejecutable histórico. Informe: docs/RETIRADA-INVENTARIO-2026-11-04.md.

## Módulo sencillo de existencias — decisión vigente del 4 de noviembre de 2026

El usuario aprobó separar el catálogo de las cantidades disponibles. Materiales conserva nombre, categoría, unidad y costo de referencia. Existencias de materiales aparece en el menú y muestra material, categoría, existencia en su unidad, estado y acciones Agregar cantidad/Corregir cantidad. El propietario administra; los maestros consultan sin escribir. Mantener los permisos y controles actuales de MaterialPolicy y ExistenciaMaterialService.

Usar la tabla existente existencias_materiales, sin renombrarla ni crear otra tabla. Materiales puede enlazar a las existencias del material seleccionado, pero no duplica formularios ni acciones. ExistenciaMaterialController conserva index/create/store/edit/update y rutas individuales /admin/existencias con nombres admin.existencias.*. Vistas en admin/existencias: index, create, edit y formulario compartido. Retirar el formulario y rutas anteriores de cantidad bajo Materiales.

No recuperar el antiguo Inventario ni Presentaciones. No añadir umbrales, proveedores, costos de compras, descuentos automáticos ni fases nuevas. Cotizar/editar/aprobar no descuenta existencias. Mantener saldo cero para materiales sin fila física aún, sin crear registros solo al consultar. Reutilizar historial, transacciones, cantidades decimales, bloqueo de correcciones obsoletas e idempotencia. La separación no requiere migraciones ni escrituras en MySQL local. Evidencias: docs/MODULO-EXISTENCIAS-2026-11-04.md.

## Limpieza de migraciones aprobada — 4 de noviembre de 2026

El usuario autorizó retirar migraciones y relaciones sin uso y confirmó que no necesita conservar los datos de prueba anteriores. Se optó por conservar los datos de las tablas activas y retirar solamente caché/cola en base de datos, presentaciones de compra y entradas del módulo abandonado, con respaldo completo, restauración idéntica y ensayo aislado previos.

Las migraciones retiradas son create_cache_table, create_jobs_table, create_presentaciones_compra_table y create_entradas_materiales_table. Su retirada afecta a cache, cache_locks, jobs, job_batches, failed_jobs, presentaciones_compra y entradas_materiales. Se eliminan los modelos PresentacionCompra/EntradaMaterial y sus relaciones; se conserva en el respaldo el registro anterior de presentación. Caché usa file y cola usa sync, sin un almacén de caché/cola SQL ni registro de fallos en failed_jobs.

Mantener las otras 19 migraciones y las tablas activas: operaciones_inventario/movimientos_inventario sostienen idempotencia e historial de Existencias; cambios_materiales sigue siendo usado por CatalogoService, además de auditorias. No retirar tablas activas solo por su nombre o por estar vacías. Mantener existencias_materiales, cotizaciones y sus relaciones. Los auxiliares antiguos de separación/consolidación de migraciones ya aplicadas se archivan en el respaldo privado; no reintroducir instaladores del esquema abandonado.

El esquema final debe coincidir con las 19 migraciones del código. No ejecutar migrate:fresh o db:wipe sobre la base local ni tocar el sistema escolar. Esta autorización es una limpieza puntual; no habilita otras fases. Evidencias: docs/LIMPIEZA-MIGRACIONES-2026-11-04.md.

Estado aplicado de esta limpieza: 19 migraciones de código/19 registradas y 25 tablas. Las cuatro migraciones y siete tablas retiradas están respaldadas; los datos activos se conservaron. No repetir los auxiliares privados de aplicación.

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


## Corte general comprobado — 5 de noviembre de 2026

Consultar docs/INFORME-GENERAL-2026-11-05.md y docs/DICCIONARIO-BD-ACTUAL-2026-11-05.md para el mapa de módulos, 27 tablas, 21 migraciones y límites vigentes. La revisión fue documental y de lectura de MySQL; se ejecutaron 158 pruebas PHP con 1.731 aserciones en SQLite aislado. La evidencia de navegador de la entrega anterior registra 75 comprobaciones; no se repitió en esta revisión. Las cifras antiguas en informes históricos no describen el estado actual. No inferir autorización para limpieza, nuevas migraciones, fabricación final ni fase 4C/4D/5 a partir del informe.


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

## Ancho de formularios — 10 de noviembre de 2026

El usuario aclara que desea conservar el diseño anterior y evitar que un formulario con pocos campos ocupe toda la fila. Ajustar su ancho según la cantidad de datos mediante la cuadrícula Bootstrap; en móvil usar el ancho disponible. Crear/Editar categoría usa col-12 col-lg-10 col-xl-8. No rediseñar tablas, fichas, encabezados o colores por esta preferencia ni añadir campos para llenar espacio. Revisar los demás módulos de forma incremental.

## Fichas Ver aprobadas — versión 1 Tarjetas, 10/11/2026

El usuario eligió la primera variante para Ver categoría y Ver material. En escritorio información principal ocupa dos tercios y responsables un tercio, con tarjetas suaves e iconos. Nombre/Descripción ocupa la fila completa; campos cortos en dos columnas. Categoría conserva materiales relacionados bajo su información; no eliminar ese contenido para imitar la imagen. Mantener actores/fechas y permisos reales, y apilado en pantallas menores de xl. Aplicar a otros módulos solo durante su revisión individual autorizada. No confundir esta preferencia con la variante 2 de listados, que fue retirada.

## Barra superior aprobada — Perfil protagonista, 11/11/2026

El usuario eligió la variante 06: Mi perfil violeta con rol de la cuenta debajo, Tema oscuro/claro delineado y Salir delineado rojo. Mantener rutas, logout POST/CSRF y cambio de tema persistente existentes. El rol se muestra desde Spatie, sin campos ni permisos nuevos. En móvil reducir a icono el tema y ocultar fecha; etiqueta Tienda & taller se conserva en escritorio amplio. No modificar el menú lateral por esta preferencia. Estilos delimitados a navbar-profile-feature.

## Botones rellenos y calendario de consulta — 11/11/2026

Por solicitud posterior, los demás botones de la barra superior adoptan el estilo relleno, redondeado y con sombra de Mi perfil, con colores diferenciados; esto sustituye sus bordes delineados anteriores. No extender a botones de otros módulos por esta autorización. La fecha abre un calendario de consulta con meses anterior/siguiente, Hoy y cierre; sin selección de fechas, eventos, formularios, peticiones de escritura o persistencia. El día actual procede de la fecha local de Laravel. El calendario también está disponible en móvil con icono. Mantener temas, perfil, rol real y salida POST/CSRF.

## Revisión visual individual de Clientes — 11/11/2026

El usuario autorizó pasar a Clientes y aplicar los criterios aprobados individualmente. Listado con nombre azul/negrita y cabecera clara; Crear/Editar conserva campos y validaciones con ancho col-12 col-xl-10; Ver usa tarjetas compactas con iconos, datos en dos columnas y responsables a la derecha (8/4 desde xl), más Volver. Mantener los opcionales y ausencia de identificación/NIT requeridos. No modificar lógica, permisos o módulos restantes por esta revisión. Evidencia en docs/DISENO-INTERFAZ.md.

## Identificación editable desde Mi perfil — 11 de noviembre de 2026

Decisión posterior del usuario: propietario y maestros pueden completar o modificar su propio tipo y número de identificación desde Mi perfil. Esta autorización sustituye la restricción anterior de edición exclusiva por el propietario. Mantener tipos CI/pasaporte, normalización, formato y unicidad por tipo y número en personal, validación del servidor y auditoría transaccional. Conservar la compatibilidad de cuentas antiguas sin identificación: si se informa un dato, se exige el otro. Cambiar la identificación no cambia la contraseña ni el acceso; rol, estado y permisos continúan restringidos al propietario desde Usuarios. No se modifica el esquema ni se actualizan registros automáticamente.

## Selección del propietario — 11 de noviembre de 2026

Por decisión del usuario, ocultar Propietario al crear una cuenta cuando ya existe un usuario con ese rol (también si está inactivo). Al editar maestros no permitir ascenderlos a propietario mientras exista otro; la cuenta que ya es propietaria puede conservar su rol. Validar la regla en controlador y servicio, además de la vista. No eliminar ni cambiar propietarios preexistentes automáticamente. Mantener protección del acceso del propietario y las reglas de contraseña inicial.

## Delegación individual de tareas operativas — 11 de noviembre de 2026

El usuario autoriza permisos adicionales por maestro, administrados solo por el propietario desde Usuarios. Alcance inicial: registrar/editar clientes y registrar/editar materiales, usando los cuatro permisos existentes clientes.crear, clientes.modificar, materiales.crear y materiales.modificar. Se suman a los del rol; retirar un adicional no elimina permisos heredados ni altera a otros usuarios. No cambia el rol maestro por defecto ni se recargan sus permisos. Registrar materiales es independiente de editarlos. Categorías, eliminación, desactivación de clientes, cantidades de existencias, aprobación/precios finales, pagos, ganancias, márgenes, reportes administrativos, configuración y usuarios siguen con sus restricciones anteriores. Conservar Form Requests/políticas/Spatie, servidor, transacción y auditoría. No hay nuevas tablas/migraciones ni asignaciones automáticas a usuarios existentes. Esta decisión sustituye la prohibición absoluta anterior de delegar registro/edición de clientes y materiales.

## Acciones de cuenta en el lateral — 11 de noviembre de 2026

Por solicitud del usuario, el recuadro con nombre/rol sube debajo del logo y reúne Mi perfil y Salir. La navegación de módulos se desplaza debajo de él; tema y calendario permanecen arriba. Sustituye las preferencias anteriores de mantener Perfil y Salir en la barra superior. Conservar logout POST/CSRF, rol auténtico, accesibilidad por menú móvil y comportamiento del menú colapsado.

Aclaración posterior de cuenta: el usuario desea el recuadro con nombre y rol en la barra superior junto al tema, con Mi perfil/Salir dentro de un desplegable al hacer clic. Se retira el recuadro lateral. Esta instrucción sustituye la ubicación bajo el logo descrita anteriormente. Mantener Bootstrap 4 y logout POST/CSRF.

Barra superior, ajuste vigente: el usuario retira el botón de fecha y su calendario de consulta. Mantener únicamente tema y cuenta desplegable, sin alterar fechas de negocio ni formularios. Sustituye la instrucción anterior de conservar ese calendario.

## Alcance confirmado de permisos — 13 de noviembre de 2026

El usuario confirma que el propietario puede conceder o retirar cualquier permiso, incluidos pagos, ganancias, aprobación de precios y administración de usuarios, y que una decisión individual debe prevalecer sobre el rol. Sustituye el límite funcional anterior de cuatro operaciones delegables y las exclusiones por rol. Implementación aplicada y verificada el 13 de noviembre de 2026, tras aprobación del usuario, respaldo completo restaurado y ensayo aislado. Permisos generales editables para todos los módulos; excepciones individuales Según su rol / Permitir / Denegar con prioridad sobre Spatie. El propietario conserva las cuatro capacidades de recuperación documentadas. Base carpinteria_el_tajibo: 31 tablas, 27 migraciones, 55 FK y 48 permisos; propietario 48 y maestro sus mismos 15. Sin delegaciones automáticas al maestro, cambios de contraseñas ni pérdidas de registros. Informe: docs/INFORME-PERMISOS-GENERALES-INDIVIDUALES.md. Esta decisión sustituye las restricciones históricas por rol; conservar las validaciones del negocio y no avanzar a fases nuevas.

## Vista compacta de permisos individuales — 13/11/2026

Por solicitud del usuario, usar la misma estructura de casillas y cuadros por módulo de los permisos generales. Marcar permite y desmarcar deniega solo a esa persona; retirar los selectores y los textos repetidos de acceso actual/heredado. Conservar iconos, protección del propietario y autorización del servidor. Los permisos que no se tocan conservan su regla anterior, incluida la herencia; no convertir todo el formulario en excepciones al guardar. El botón Usar permisos del rol prepara el restablecimiento de todas las excepciones y requiere Guardar para aplicarlo. Sin cambios de esquema ni asignaciones automáticas.

## Revisión visual de Existencias de materiales — 13/11/2026

Revisión individual autorizada al continuar después de Usuarios/Permisos. Aplicar los encabezados refinados y tamaños legibles ya aprobados al listado, agregar, corregir y movimientos. Listado con nombre azul/negrita; formularios de dos campos en col-12 col-lg-10 col-xl-8, reutilizando el parcial sin cambiar datos ni validaciones. Movimientos muestra una ficha compacta con iconos, existencia actual y unidad en dos columnas; historial completo debajo con encabezado de ficha y navegación conservada. No modificar controladores, servicios, cálculos, permisos, rutas ni MySQL por esta revisión.

## Resta de materiales al iniciar fabricación — decisión del 13/11/2026

El usuario acepta incorporar la resta de existencias y concreta que ocurra al comenzar la fabricación. Se propone usar las cantidades planificadas al pulsar Iniciar fabricación, sin restas en cotización/aceptación ni retroactivas a pedidos ya iniciados. Las compensaciones por uso real confirmado, las dos ampliaciones de esquema y sus pruebas se detallan en docs/PLAN-CONSUMO-AL-INICIAR-FABRICACION.md, pendientes de revisión. No afirmar que el descuento esté implementado ni ejecutar los borradores privados sobre MySQL. Mantener el flujo actual hasta aprobar el diseño y completar respaldo restaurado, ensayo aislado y aplicación controlada. Esta decisión sustituye la prohibición funcional anterior de incorporar descuentos, pero no autoriza aplicar un esquema no revisado.

## Ancho de listados con pocos datos — 13/11/2026

Por solicitud del usuario, los listados no deben ocupar toda la fila si sus datos permiten un ancho menor. Categorías, Usuarios, Cotizaciones, Pedidos y Pagos usan col-12 col-xl-10; Planificación, con cuatro columnas cortas, usa col-12 col-xl-8. Encabezado, acciones superiores, filtros, tabla y paginación comparten el ancho. Debajo de xl se usa todo el espacio disponible y se conserva el desplazamiento interno cuando es necesario. Roles ya conserva su ancho reducido; las tablas con descripciones extensas o muchas acciones mantienen espacio suficiente. No quitar columnas, campos o funciones para reducir el ancho.

Ampliación solicitada a Materiales y Existencias: ambos listados también tienen ancho contenido, con sus encabezados y filtros alineados. Materiales usa col-12 col-xl-10; Existencias también usa col-12 col-xl-10, con menos relleno horizontal y separación entre icono y texto en sus acciones para que no se partan a 1440 px. En móvil conservan el ancho disponible y el desplazamiento interno cuando corresponda. Columnas, botones, rutas, permisos y cálculos se mantienen.

Existencias, posición del botón superior: por solicitud del usuario, el encabezado del listado utiliza todo el ancho para situar Ver materiales al extremo derecho. La tabla y su buscador conservan col-xl-10. Esta excepción sustituye únicamente la alineación del encabezado de Existencias con su tabla; no cambia rutas, permisos ni otros módulos.

## Botones superiores al extremo derecho — 13/11/2026

El usuario extiende a los módulos revisados la ubicación aprobada en Existencias: los encabezados con acciones superiores aprovechan todo el ancho y sitúan sus botones a la derecha. Se amplían únicamente los contenedores del encabezado de Categorías, Materiales, Usuarios, Cotizaciones, Pagos y Movimientos de existencias. Clientes, fichas Ver y Permisos ya usaban el ancho completo. Tablas, buscadores, formularios y sus anchos reducidos se conservan; también iconos, textos, rutas y permisos. Esta decisión sustituye la alineación anterior del encabezado al ancho de la tabla, únicamente donde existen botones superiores.

Clientes, ajuste de ancho confirmado: el listado, buscador y paginación usan col-12 col-xl-10, igual que Materiales. El encabezado conserva su ancho completo para mantener Nuevo cliente al extremo derecho. Se conservan las seis columnas, textos, funciones y controles de acceso.

## Clientes: ancho ajustado al contenido — 13/11/2026

Por aclaración del usuario sobre el espacio sobrante tras las acciones, el listado de Clientes sustituye la fracción fija col-xl-10 por col-auto (máximo disponible de Bootstrap) y la tabla utiliza w-auto. Tarjeta y columnas adoptan el ancho necesario del contenido, sin estirar Acciones para llenar un porcentaje de la pantalla. Se conserva el relleno normal del borde, el encabezado con Nuevo cliente a la derecha y los mismos datos/acciones. En móvil se mantiene desplazamiento interno. Esta decisión sustituye para Clientes el ancho fijo anterior.

Existencias, ancho al contenido: por solicitud del usuario, el listado adopta col-auto y la tabla w-auto, igual que Clientes. Sustituye su fracción fija col-xl-10 y evita estirar la columna de acciones. Se conserva el relleno normal, los tres botones alineados, el buscador y Ver materiales al extremo derecho del encabezado. Sin cambios de cantidades, permisos, rutas ni base de datos.

Existencias, navegación desde movimientos: el botón Volver regresa a admin.existencias.index sin material_id, mostrando el listado completo. Sustituye el regreso filtrado anterior. Se conserva la posibilidad de filtrar al abrir desde la ficha de un material y el resto de las rutas. Verificado en navegador: tras volver aparecen los tres materiales de la fixture aislada, no únicamente el consultado; 23 grupos de comprobación aprobados.

## Existencias: acceso al listado general — aclaración vigente 13/11/2026

Después de agregar o editar una cantidad, conservar la redirección filtrada por material_id para mostrar el material actualizado. En esa pantalla filtrada debe aparecer Listado general, enlazando a admin.existencias.index sin filtros. Esta es la ubicación que pidió el usuario; no añadir ese botón a los formularios ni redirigir automáticamente a todos tras guardar. Cancelar regresa al listado completo, según aclaración del 14/11/2026; Volver desde movimientos conserva el listado completo por decisión anterior. Cálculos, permisos, auditoría y movimientos sin cambios.

## Usuarios: permisos fijos y bloqueados — 14/11/2026

Decisión posterior del usuario: el grupo Usuarios se bloquea tanto en permisos generales como individuales. usuarios.consultar, usuarios.crear, usuarios.modificar, usuarios.gestionar y usuarios.asignar_roles quedan activos únicamente para el propietario y desactivados para maestros, sin posibilidad de delegarlos ni de retirarlos al propietario desde esos formularios. La restricción se aplica antes de las excepciones individuales en User/Acceso/Gate y los servicios rechazan solicitudes manipuladas. Las asignaciones antiguas no pueden dar acceso al maestro. El resto de los permisos conserva su personalización y prioridad individual. No modificar tablas, datos o asignaciones existentes por el despliegue de esta regla. Esta decisión sustituye solo la delegación de administración de usuarios/roles/permisos autorizada el 13/11; no restablece las antiguas exclusiones de pagos, precios o márgenes.


Cotizaciones, acciones compactas (14/11/2026): aplicar también al listado col-auto y tabla w-auto, como Clientes y Existencias, para no estirar Acciones ni dejar espacio tras el último botón. Conservar encabezado completo con Nueva cotización a la derecha, filtros, columnas y desplazamiento interno móvil. Sustituye el ancho col-xl-10 anterior únicamente en su listado.


## Tipos de muebles administrables — 14/11/2026

El usuario autoriza añadir la administración de los tipos para cotizar. Reutilizar tipos_mueble, sin migraciones ni tablas o permisos nuevos. Rutas explícitas /admin/tipos-mueble y admin.tipos_mueble.*; controlador TipoMuebleController con Request, validación y asignaciones explícitas, transacción auditada y política. Pantallas index/create/edit/show: nombre y estado, búsqueda/paginación, tabla al contenido, iconos y estados menta/plomo. No eliminar tipos: desactivarlos. Consultar exige cotizaciones.consultar; registrar/editar exige además configuracion.gestionar, por ser parte de la configuración del catálogo. Se respeta la delegación/denegación individual de esos permisos. El código técnico existente es interno, automático en altas y se conserva al editar; no se solicita ni se muestra ni cambia el ID autoincremental. Los tipos activos se ofrecen al cotizar con el flujo existente; no reescribir instantáneas aprobadas ni casos. Nuevos tipos requieren casos y cobertura explícita del perfil antes de ofrecer recomendaciones; no configurar parámetros ni inventar antecedentes automáticamente. No modificar MySQL por el despliegue.


## Cotización simplificada: mano de obra y precio propuesto — 15/11/2026

El usuario aprueba retirar de Crear/Editar el apartado Ajuste de mano de obra y precio propuesto, incluidos mano_obra_total_aplicada, motivo_ajuste_mano_obra y precio_propuesto como campos de llenado. Para cotizaciones nuevas usar mano de obra por mueble × cantidad; cualquier ahorro cotidiano se refleja en el importe por mueble antes de aprobar. Se conserva ganancia sobre costo, precio sugerido y aprobación final; no introducir otro precio preliminar en el formulario. Mantener los ajustes de cantidades de materiales, no incluidos en esta retirada.

No borrar columnas ni modificar registros existentes. Compatibilidad de estimaciones antiguas: si al editar se omiten los campos retirados y no cambian cantidad, mano de obra por mueble ni referencia de caso, conservar total aplicado, justificación, actor y fecha del ajuste; mostrar un aviso de solo lectura cuando exista. Si cambian esos valores o se aplica otra propuesta de caso, recalcular con el flujo simple y conservar el estado anterior en la auditoría transaccional. El precio propuesto anterior se conserva si se omite, salvo al aplicar una nueva referencia; su estado anterior queda auditado. Cotizaciones aprobadas permanecen inmutables. Conservar la validación de formatos antiguos en el servicio por compatibilidad; no reintroducir esos campos ni como entradas ocultas en los formularios. No ejecutar migraciones ni escrituras en MySQL por este cambio. Esta decisión sustituye la solicitud de ajustes de mano de obra conjuntos en el llenado habitual.


## Restaurar ajuste opcional y precio propuesto — 15/11/2026

Decisión posterior del usuario: volver al comportamiento anterior a la simplificación. Se restablece el apartado opcional Ajuste de mano de obra y precio propuesto en Crear/Editar, con mano de obra total ajustada, justificación y precio total propuesto. Se restauran su validación/cálculo/guardado originales y los controles JavaScript al aplicar un caso; se retira el aviso de compatibilidad añadido solo para el formulario simplificado. No mantener dos modos ni controles ocultos de sustitución. Esto sustituye la sección anterior de simplificación del 15/11. Materiales, otros costos, ganancia sobre costo y aprobación permanecen como antes. El precio propuesto es por todos los muebles de ese detalle, no por unidad, y no cambia el costo ni constituye aprobación. No modificar MySQL, migraciones ni registros existentes.


## Números editables sin ceros sobrantes — 15/11/2026

Por solicitud del usuario, los campos numéricos de Cotizaciones muestran 20.0000 como 20 y 20.5000 como 20.5, incluyendo porcentaje, dimensiones, cantidades, costos, ajustes y precios de aprobación. Usar Decimal::entrada para valores Blade y el formato equivalente de texto en JavaScript al cargar catálogo/casos. No usar float, redondear, agregar separadores de miles ni cambiar el punto decimal de entrada. Conservar precisión significativa, vacío para costo desconocido, validaciones y valores guardados. Los importes de tablas/PDF conservan el formato monetario existente. Esta entrega se limita al módulo revisado; no modificar cálculos, esquema ni datos de MySQL.


## Cotizaciones: unificar notas en Observaciones — 15/11/2026

Por solicitud del usuario, retirar Condiciones para el cliente de Crear/Editar y dejar únicamente Observaciones en Datos generales. Renombrar el desplegable a Observaciones y permitir que el texto use el ancho de su recuadro. No borrar ni fusionar automáticamente condiciones anteriores: al omitirse el campo, CotizacionService conserva su valor guardado. Se mantiene la validación compatible con datos anteriores y su consulta/PDF cuando exista texto histórico. Las nuevas cotizaciones desde el formulario no generan condiciones. No modificar tablas, migraciones ni registros existentes por este cambio.


## Menta intensa — 15/11/2026

El usuario prefiere la menta intensa #84dfc3 del sistema en lugar de fondos menta suaves, incluyendo el recuadro Precio final estimado. Unificar resúmenes, indicadores verdes, avisos de éxito y acentos equivalentes; mantener texto oscuro legible y consistencia en ambos temas. No modificar colores de acciones de otras familias ni cálculos, datos o permisos.


## Vistas revisadas de Pagos, Casos y Planificación — 16/11/2026

Mantener también en estos módulos cabeceras refinadas, fichas con iconos y campos cortos en dos columnas, paneles proporcionados al contenido y acciones alineadas. Pagos conserva su formulario compartido de ancho moderado; Casos separa características/costos, materiales y revisión; Planificación agrupa materiales por mueble y organiza las filas editables en recuadros. Listados al ancho del contenido y tablas con desplazamiento interno en móvil. Conservar estilos claro/oscuro, colores actuales, permisos, campos, confirmaciones y retornos contextuales. Las cantidades editables usan Decimal::entrada para omitir ceros finales, sin cambiar precisión ni valores persistidos. Sin cambios de backend, esquema o datos.


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


## Tablero de pedidos en el dashboard — 16/11/2026

La sección de pedidos del panel se presenta como tablero de consulta con cuatro columnas: Aceptado, En fabricación, Terminado y Entregado. Total completo por estado y hasta cinco tarjetas por columna; enlace al listado filtrado cuando hay más. Abiertos por entrega próxima y cerrados por actualización reciente. Cada tarjeta permite Ver pedido; no arrastrar ni cambiar estados desde el tablero. Conservar transiciones, validaciones y permisos del detalle. Consultar solo con pedidos.consultar; no cargar importes ni datos personales para estas tarjetas. Sin tablas, migraciones ni cambios de datos existentes.


## Dashboard según referencia original — 16/11/2026

Por solicitud del usuario, adaptar el panel al archivo referencias/dashboard-referencia.png: ocho indicadores con degradados (cotizaciones del mes/por revisar, pedidos aceptados/en fabricación/terminados/entregados, clientes y materiales), gráfico circular de estados junto a cotizaciones recientes y tablero compacto con líneas de color debajo. Retirar el bloque grande de bienvenida y sus estilos exclusivos. Conservar menú y menta en lugar del amarillo. Mostrar únicamente indicadores autorizados; reutilizar totales completos existentes de tableroPedidos para el gráfico, sin nuevas consultas ni cifras ficticias. Datos de pedidos sin importes ni contactos. Estados, transiciones, permisos y límites permanecen vigentes.


Bienvenida restaurada (16/11/2026): el usuario solicita incorporar de nuevo el bloque azul oscuro y menta «Todo listo para seguir creando», con ilustración CSS y accesos Nueva cotización/Ir a pedidos sujetos a sus permisos. Se coloca sobre los ocho indicadores; se conserva el gráfico y el tablero compacto. Nueva cotización aparece solo en el bloque para evitar duplicación. Esta decisión sustituye únicamente la retirada anterior de la bienvenida. Compilación y dashboard-diseno.cjs aprobados para ambos roles, 1440/1024/390 px y tema oscuro, sin errores JavaScript ni desbordamientos. Sin cambios de backend o MySQL.


## Revisión de redundancias del dashboard — 16/11/2026

Conservar la bienvenida y los ocho indicadores aprobados. Retirar Ir a pedidos del bloque de bienvenida porque duplica Ver todos del tablero; Nueva cotización permanece una sola vez. Retirar el subtítulo general que repite la descripción de bienvenida. Gráfico: total general en el centro y porcentajes por estado en la leyenda, en lugar de volver a repetir las cantidades de las tarjetas/columnas. Mantener totales de columnas porque expresan cuántos pedidos existen aunque solo se muestran cinco. Cotizaciones recientes conserva su función de acceso a registros concretos. Eliminar iconos ocultos de flechas, estilos de acción ya retirada, selector CSS repetido y condicional consecutivo redundante. No cambiar consultas, datos, permisos ni rutas.


## Acentos coherentes en los paneles del dashboard — 16/11/2026

Se colorean encabezados e iconos: menta en Estado de los pedidos, azul en Cotizaciones recientes y violeta en Tablero de pedidos. Usar los mismos acentos para cada estado en indicadores, gráfico, leyenda y columnas: cobre para Aceptado, menta para En fabricación, violeta para Terminado y azul petróleo para Entregado. Variables CSS compartidas --order-* para gráfico y tablero; fondos de encabezado suaves y contadores de color, conservando filas compactas. Cambio exclusivamente visual: sin consultas, datos, rutas ni permisos nuevos.


Intensidad de color (16/11/2026): por solicitud del usuario, sustituir los encabezados suaves por color pleno en menta, azul y violeta; cabeceras de estados con cobre, menta, violeta y azul petróleo y texto de contraste. Intensificar también leyenda y cabecera de tabla. Mantener la paleta, distribución, datos y permisos. Vite y dashboard-diseno.cjs aprobados en SQLite temporal para escritorio/tableta/móvil, ambos roles y tema oscuro, sin errores JavaScript ni desbordamiento. Capturas actualizadas en docs/capturas/dashboard-referencia-2026-11-16.


## Recarga integral autorizada — 16/11/2026

El usuario autoriza reiniciar todos los registros de carpinteria_el_tajibo, incluidos usuarios y permisos, y decide crear el propietario manualmente con tajibo:crear-propietario. El esquema ya coincide: conservar 31 tablas y 27 migraciones, sin retirar columnas solo por no aparecer en formularios. Actualizar seeders por módulo con los campos vigentes; no copiar contraseñas ni asignaciones anteriores. DatabaseSeeder incluye UsuariosSeeder, FabricacionSeeder, PagosSeeder y AjustesSeeder además de los módulos existentes. Alta del propietario sin contraseña predeterminada; maestros con contraseña inicial temporal según UsuarioService. Los ejemplos conservan procedencia ficticia interna y no acreditan validación humana. Respaldo restaurado y ensayo aislado comprobados antes del reinicio local. Estado y pasos pendientes en docs/INFORME-RECARGA-DATOS.md; no afirmar carga local completa antes del alta manual y la verificación final.


## Cierre de recarga integral — 16/11/2026

El usuario confirmó el alta manual del propietario. DatabaseSeeder se ejecutó en carpinteria_el_tajibo y se verificó la cuenta manual sin cambiar sus credenciales. Carga final: 3 usuarios (propietario y 2 maestros), 5 categorías, 36 clientes, 27 materiales, 26 cotizaciones, 11 pedidos, 4 pagos y 37 casos; 31 tablas, 27 migraciones, 2 roles y 50 permisos. Relaciones, aceptación/versiones, precios y saldos correctos; campos retirados de formularios sin valores en los ejemplos nuevos. Repetición idempotente comprobada. 34 respuestas HTTP 200 locales, cuatro PDF válidos y dos redirecciones obligatorias de maestros al perfil (302), sin cambios de datos por las lecturas ni sesiones reales. Aplicación fuera de mantenimiento. Respaldos privados verificados conservados y bases temporales retiradas. No quedan pasos pendientes; estado actualizado en docs/INFORME-RECARGA-DATOS.md.


## Nombres de archivos por función — 16/11/2026

La carga coordinada se denomina DatosSistemaSeeder (database/seeders/DatosSistemaSeeder.php) y su configuración config/datos_sistema.php, clave datos_sistema.habilitado. La opción de entorno preferida es TAJIBO_DATOS_SISTEMA; se admite TAJIBO_DATOS_EJEMPLO como alias de compatibilidad con instalaciones existentes. Documentación: docs/CARGA-DATOS.md y docs/CATALOGO-CASOS-HISTORICOS.md. Actualizar referencias PHP, pruebas y enlaces al cambiar estos nombres. Conservar lotes, IDs, accion de auditoría datos_ejemplo_cargados y procedencia; no renombrar esas claves persistidas porque sostienen idempotencia y reconocimiento de pedidos. No se reclasifican datos ni se cambia su procedencia por renombrar archivos. Respaldos y capturas históricas conservan sus nombres originales.


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

## Numeración cronológica del historial — 17/11/2026

El usuario aprobó corregir la carga para que los IDs bajos correspondan a trabajos antiguos y los altos a recientes. HistorialCarpinteriaSeeder ejecuta esa conciliación una sola vez mediante historial_ids_cronologicos_v1, después de organizar fechas. Cotizaciones, pedidos, casos y pagos se ordenan por sus fechas y se concilian todas las referencias de detalles/materiales/estados y los metadatos de cargas. Preservar el conjunto de IDs y huecos de cada tabla: no reutilizar IDs de registros eliminados. Las altas normales conservan ID automático y los listados ID descendente. Esta autorización puntual no habilita renumerar datos futuros ni otros catálogos.

Aplicado en carpinteria_el_tajibo con respaldo nuevo restaurado, ensayo MySQL aislado, claves foráneas activas y transacción. Conservados cuentas/contraseñas, permisos, clientes, catálogo, existencias, movimientos, importes, cantidades, fechas y estados. Auditorías históricas conservan sus instantáneas; el nuevo evento guarda mapas y metadatos previos para explicar los enlaces conciliados. No cambiar perfil, pesos, k ni umbral de similitud. Sin migraciones. Repetir DatabaseSeeder no modifica datos. Pedido 1: 29/01/2020; pedido 48: 15/11/2026. Excel actualizado desde la base. Evidencias y pruebas en docs/HISTORIAL-CARPINTERIA.md.

## Corte vigente del historial — 17/11/2026

El usuario solicita fechas solo hasta principios de octubre. Se adoptó 05/10/2026 como corte del historial comercial y del Excel. HistorialCarpinteriaSeeder aplica una sola vez historial_hasta_octubre_2026_v1 después de organizar los IDs: conserva 2020–2025, comprime 2026 cronológicamente hasta el corte y limita también las entregas previstas, conservando el orden de los eventos. Pedido 48 ahora tiene fecha 03/10/2026; casos/cierres llegan al 05/10/2026. Sustituye las fechas de noviembre de las notas anteriores, no los importes o cantidades.

No restringir las nuevas altas a octubre: conservan la fecha real automática. No cambiar fechas técnicas de cuentas/autenticación ni falsificar la auditoría del mantenimiento. Sin cambios de esquema o registros fuera de la carga reconocida. Conservados IDs/relaciones, 87 casos, cantidades, precios, cuentas, permisos, catálogo, existencias y movimientos. Nuevo respaldo restaurado, ensayo MySQL aislado, navegador y 8 pruebas/793 aserciones aprobados; repetición sin cambios. Evidencias en docs/HISTORIAL-CARPINTERIA.md y docs/capturas/historial-octubre/.

## Ampliación vigente a 139 casos y tres tipos — 17/11/2026

El usuario solicita 139 casos con fechas coherentes y solo tres tipos adicionales. Se añadieron Velador, Cómoda y Vitrina, 12 casos por tipo, y 16 casos de los tipos anteriores: 52 nuevos mediante 21 pedidos. Total: 139 casos en 60 pedidos, distribución 16 pedidos con uno, 18 con dos, 17 con tres y nueve con cuatro. Totales locales: 80 cotizaciones, 68 pedidos, 124 pagos, 27 materiales y nueve tipos; tres usuarios, permisos, clientes, catálogo anterior y existencias conservados.

CasosHistoricosSeeder::ampliar se ejecuta después de HistorialCarpinteriaSeeder en DatabaseSeeder, una sola vez por casos_historicos_139_v1. JSON adicional por módulo y TiposMuebleSeeder actualizado; no borrar los seeders vigentes ni reiniciar la cuenta para esta ampliación. Los casos anteriores conservan íntegros sus datos; IDs y relaciones se concilian cronológicamente con historial_ordenado. Se mantiene el corte 05/10/2026; nuevas altas normales usan fecha real. Sin cambios de esquema: 31 tablas, 27 migraciones.

Perfil activo versionado: config/perfiles/similitud-casos-historicos-v2.json, 25 referencias anteriores y 36 nuevas. Rangos anteriores intactos; se añaden únicamente los rangos de los tres tipos. Distancia, pesos, k=3 y umbral=80 conservados. Procedencia simulada y revisión pendiente: no acreditar validación humana. Vidrio para vitrina se registra por pieza con referencia ilustrativa de Bs 90, no como precio verificado de proveedor.

Respaldo nuevo restaurado, ensayo aislado, conservación por mapa inverso de IDs e idempotencia comprobados. Navegador: paginación, fichas, PDF y búsqueda de propuestas de los tres tipos; PHP local: 230 respuestas 200, cuatro PDF y nueve consultas de recomendación correctas, sin escrituras. Pruebas funcionales verificadas en tandas; última tanda 14/1236 aprobada y Pint aprobado. Excel actualizado: 68 pedidos, 988 líneas de costos, 124 pagos y 36 clientes. Informe vigente: docs/AMPLIACION-CASOS-HISTORICOS.md.

## Cantidad vigente: 132 casos — 17/11/2026

El usuario corrigió la cantidad deseada de 139 a 132. Se retiraron siete casos adicionales sin referencia del perfil, de tipos anteriores, junto con sus cuatro pedidos completos, cotizaciones y pagos relacionados. Se conservan los 87 casos base y los 12 casos de cada nuevo tipo: Velador, Cómoda y Vitrina. No modificar los 61 descriptores/rangos del perfil v2 por este ajuste.

Estado local: 132 casos en 56 pedidos cerrados, más ocho abiertos; 64 pedidos, 76 cotizaciones, 116 pagos, 27 materiales, nueve tipos y tres usuarios. Distribución de casos: 14 pedidos con uno, 17 con dos, 16 con tres y nueve con cuatro. Los registros conservados permanecen idénticos, con sus fechas y IDs; corte 05/10/2026, listado ID descendente. Cuentas, permisos, catálogo y existencias íntegros.

DatabaseSeeder ejecuta CasosHistoricosSeeder::ajustarCantidad después de ampliar, una sola vez por casos_historicos_132_v1; la instalación nueva termina en 132 y la repetición no cambia datos. Mantener las claves de lotes anteriores para reconocer instalaciones ya cargadas. Auditoría conserva datos retirados y metadatos previos; sin cambios de esquema.

Respaldo nuevo restaurado, ensayo aislado, conservación y relaciones comprobados; InstalacionSeedTest 4/1069 y Pint aprobados. Navegador con paginación y búsqueda de los tres tipos aprobado; base local 219 respuestas 200 y cuatro PDF sin escrituras. Excel: 64 pedidos, 941 líneas de costos, 116 pagos y 36 clientes. Detalle vigente en docs/AMPLIACION-CASOS-HISTORICOS.md, sección Ajuste vigente a 132 casos.

## Calendario equilibrado desde 16/03/2020 — 17/11/2026

El usuario confirmó comenzar a mediados de marzo de 2020 y distribuir los 132 casos sin concentraciones o contradicciones. Se adoptó 16/03/2020 como primera cotización y 18/03/2020 como primera aceptación. Corte: 05/10/2026. Distribución vigente: 2020: 16 casos; 2021–2025: 20 por año; 2026: 16. Ocho pedidos cerrados por año, repartidos entre meses; ocho abiertos distribuidos entre julio y septiembre de 2026. Máximo cuatro casos y cuatro aceptaciones por mes.

No repetir fechas entre cotizaciones distintas, aceptaciones distintas, pagos distintos o entregas previstas distintas dentro de esta carga. Los casos del mismo pedido conservan su fecha común de cierre; hay 56 cierres distintos. Mantener cotización/aprobación antes de aceptar, estados estrictamente ordenados y coherencia de uso/validación de materiales, casos y pagos. Las fechas técnicas de auditoría mantienen el instante real de mantenimiento.

HistorialCarpinteriaSeeder::redistribuirFechas se ejecuta al final de DatabaseSeeder una sola vez por historial_distribuido_marzo_2020_octubre_2026_v1, con conciliación de IDs y relaciones. Conservar los identificadores de lotes anteriores y el orden por ID descendente; no alterar precios, materiales, cantidades, estados, procedencia/revisión ni las 61 referencias y parámetros del perfil v2. Las operaciones normales nuevas mantienen fecha real automática.

Aplicado con respaldo nuevo restaurado, ensayo aislado y conservación por mapa inverso de IDs. InstalacionSeedTest 4/1782, sintaxis y Pint aprobados; navegador sin errores, base local 219 respuestas 200 y cuatro PDF, diagnóstico cronológico sin contradicciones y repetición local sin cambios. Excel sincronizado con el calendario, sin conexión en vivo. Detalle vigente en docs/AMPLIACION-CASOS-HISTORICOS.md, sección Calendario vigente desde mediados de marzo de 2020.

## Todos entregados; clientes locales sin correo opcional — 17/11/2026

El usuario solicitó todos los pedidos en Entregado, ubicaciones solo La Paz/El Alto y correo opcional vacío. Ante la generación automática de nueve casos al cerrar ocho pedidos, confirmó conservar 132 ajustando antecedentes adicionales. Se sustituyeron nueve casos adicionales sin referencia del perfil por los nueve generados mediante cierre/entrega. Un pedido adicional sin detalles se retiró; cinco pedidos con otros muebles conservaron sus detalles, recalculando totales y sus dos pagos. Las 61 referencias del perfil y los 12 casos de cada nuevo tipo se conservan.

Estado vigente: 63 pedidos, todos entregados, y 132 casos; 75 cotizaciones, 114 pagos, 36 clientes, 27 materiales, nueve tipos y tres usuarios. Distribución de casos por pedido: 22 con uno, 19 con dos, 16 con tres y seis con cuatro. Nueve pedidos por año con 16 casos en 2020, 20 por año de 2021 a 2025 y 16 en 2026; desde 16/03/2020 hasta 05/10/2026, con último cierre 03/10/2026. Mantener límites mensuales, fechas únicas y secuencia de estados. No registrar pagos inexistentes: el saldo global anterior de Bs 7.642,55 se conserva.

ClientesSeeder, ClienteFactory y el historial usan email NULL para clientes y direcciones locales; pedidos/cotizaciones y contexto de casos se concilian con esos datos. Conservar los correos de usuarios y Configuración: son obligatorios. No modificar cuentas/contraseñas ni inventar revisión humana; casos siguen de origen simulado y revisión pendiente.

DatabaseSeeder ejecuta CasosHistoricosSeeder::entregarHistorial al final, una sola vez por historial_entregado_local_sin_correo_v1. redistribuirFechas admite el calendario final de todos entregados sin romper las cargas anteriores. Mantener identificadores anteriores para reconocer instalaciones aplicadas. Sin cambios de esquema ni del algoritmo de similitud.

Aplicado con respaldo nuevo restaurado, ensayo aislado y transacción. 45 pruebas/2718 aserciones, sintaxis y Pint aprobados; navegador correcto, base local 218 respuestas 200 y cuatro PDF, nueve consultas de recomendación e idempotencia verificadas. Excel actualizado: 63 pedidos, 880 líneas de costos, 114 pagos y 36 clientes. Detalle vigente en docs/AMPLIACION-CASOS-HISTORICOS.md, sección Estado vigente: todos entregados y contactos locales.

## Cantidad vigente: 87 casos — 17/11/2026

El usuario solicita solo 87 casos. Se comprobaron 132 filas; 183 era el ID máximo, no la cantidad. Se retiraron 45 casos sin referencia del perfil junto con 16 pedidos completos pagados y sus dependencias. Estado vigente: 87 casos en 47 pedidos entregados, 59 cotizaciones, 82 pagos, 36 clientes, 27 materiales y nueve tipos. Distribución: 21 pedidos con un caso, 14 con dos, 10 con tres y dos con cuatro. Se conservan los 61 descriptores y 12 casos de cada tipo nuevo; no modificar parámetros del recomendador.

DatabaseSeeder termina con CasosHistoricosSeeder::limitarA87, guardado por historial_87_casos_v1. Mantener lotes anteriores para compatibilidad. La instalación final produce 87 y la repetición no cambia datos; las altas normales futuras pueden aumentar el total. El listado muestra cantidad global y cantidad filtrada, con IDs normales descendentes y huecos.

Calendario final: 10 casos en 2020, 13 por año de 2021 a 2025 y 12 en 2026; desde 16/03/2020 hasta 05/10/2026, cierre máximo 03/10/2026, máximo cuatro por mes. Solo La Paz/El Alto y clientes sin email opcional. Cuentas/permisos/existencias/catálogos intactos, saldo Bs 7642.55 conservado. Sin cambios de esquema o revisión humana inventada.

Respaldo nuevo restaurado, ensayo aislado, conservación por mapas inversos, idempotencia y navegador aprobados. 27 pruebas/2353 aserciones, Pint aprobado, 157 respuestas locales 200 y cuatro PDF, nueve consultas de control correctas. Excel sincronizado: 47 pedidos, 579 costos, 82 pagos, 36 clientes. Detalle en docs/AMPLIACION-CASOS-HISTORICOS.md, sección Cantidad vigente: 87 casos.

## Recarga completa con IDs consecutivos — 17/11/2026

El usuario autorizó reconstruir todos los datos con migraciones/seeders y confirmó conservar su acceso actual de propietario. Se ejecutó migrate:fresh SOLO en carpinteria_el_tajibo, con respaldo restaurado y ensayo aislado. Se restauró privadamente la cuenta del propietario (hash, correo, nombre/estado y personal si existe), sin contraseñas en texto; demás cuentas, permisos y registros recargados. Se reinician sesiones. No tocar el proyecto escolar.

Estado vigente: 87 casos con IDs 1–87 y AUTO_INCREMENT=88; 47 pedidos entregados, 59 cotizaciones y 82 pagos, todos con IDs consecutivos. 31 tablas, 27 migraciones, 36 clientes, 27 materiales y nueve tipos. Se mantienen fechas/contactos/perfil/balance de la carga anterior. El orden físico de users.activo y de índices de materiales coincide ahora con migraciones, sin cambios de definiciones.

DatabaseSeeder termina con HistorialCarpinteriaSeeder::finalizarIds, por historial_ids_correlativos_v1. Usa mapas de relaciones y prepara autoincrementos una sola vez. No renumerar nuevas altas al repetir seeds; prueba del caso 88 y repetición aprobadas. Nunca ejecutar la preparación de contadores dentro de una transacción MySQL. Los lotes anteriores se mantienen para compatibilidad.

8 pruebas/1658 aserciones, Pint y navegador aprobados; MySQL verifica FK, CHECK TABLE, IDs y contadores, calendario, totales, credenciales e idempotencia. Informe vigente: docs/INFORME-RECARGA-DATOS.md, última sección. Las notas previas sobre huecos de IDs quedan sustituidas por esta recarga.

Comprobación posterior: 157 HTTP 200, cuatro PDF válidos y nueve consultas de control aprobadas; Excel actualizado con IDs 1–47/1–82, fórmulas y archivo guardado verificados. Ambas copias de ensayo retiradas y respaldos conservados. Aplicación fuera de mantenimiento.

## Presentación de casos recomendados — 17/11/2026

Se mejoró admin/similitud/_resultados, compartido por la búsqueda del formulario y la consulta de similitud. Tarjetas hasta tres columnas, recomendación principal en menta fuerte y alternativas con borde azul; similitud y costo actual para la cantidad solicitada visibles, referencia económica histórica separada. Materiales/costos del grupo y características/diferencias en desplegables; datos pendientes y advertencias se conservan. La selección aplicada se marca visualmente y puede sustituirse por otra alternativa; no modifica el caso original.

Estilos limitados a similar-case/similar-results en app.css, compatibles con tema oscuro y móvil. No cambiar cálculos, perfil, permisos o base de datos por esta mejora. Vite compilado; dos pruebas de SimilitudTest/22 aserciones aprobadas. Navegador en copia aislada: tres tipos, principal/alternativas aplicables, materiales completados, selección, desplegables, tema oscuro y móvil sin errores ni desbordamiento. Evidencia: docs/capturas/casos-recomendados/. Copia temporal retirada; datos operativos intactos.

## Consumos coherentes de referencia — 17/11/2026

El usuario pidió coherencia de cantidades y confirmó usar medidas de referencia para los registros. Se añadieron especificaciones a Madera (240×20×5 cm), Melamina (275×183 cm,18 mm), juego de correderas, canto PVC y vidrio. ConsumosMaterialesSeeder aplica una vez consumos_muebles_referencia_v1 a los 87 casos generados/pending, después de limitarA87 y antes de finalizarIds. Estima los nueve tipos mediante componentes, volumen/área, reservas, accesorios por puerta/cajón/repisa y consumibles por acabado. Son estimaciones de suministro con recortes, no planos de corte validados.

Se sincronizan líneas de cotización/pedido/caso y sus costos/precios, manteniendo recargo efectivo por detalle y proporción pagada de cada pedido. Mano de obra y otros costos conservados. Sin nuevos pagos ni cambios de fechas. Casos 1–87, descriptores, estados/revisión y fechas intactos; versión del caso incrementada. Se conservan usuarios/permisos/clientes/existencias/movimientos y el perfil v2/61 referencias. No modificar stock por estos antecedentes ni introducir este cálculo como estimador automático de nuevas cotizaciones. Controladores/modelos/rutas sin cambios.

Estado: 87 casos, 47 pedidos entregados, 59 cotizaciones, 82 pagos; 635 materiales de casos/pedidos y 666 de cotizaciones, 27 materiales de catálogo. Totales nuevos: Bs 134767.92 acordados, Bs 126379.49 pagados, Bs 8388.43 pendientes. Sustituye importes y consumos anteriores. Mesas 62/82/86: cuatro piezas de referencia por mueble con sellador/barniz/lija presentes y litros según superficie.

Respaldo restaurado, ensayo aislado y navegador aprobados antes de aplicar. 8 pruebas/2098 aserciones, Pint, integridad/coherencia/idempotencia, 157 HTTP200, cuatro PDF y nueve consultas de control correctos. Detalle y límites en docs/CONSUMOS-MATERIALES.md. Nuevos datos no acreditan revisión humana real.

Cierre de consumos: Excel sincronizado (47 pedidos,809 costos,82 pagos,36 clientes), fórmulas/archivo guardado y totales exactos verificados. Copia temporal retirada y respaldo conservado. Base disponible; casos 1–87 y siguiente ID88.

## Tornillos según montaje, consumos v2 — 17/11/2026

El usuario solicitó adecuar los tornillos de todos los tipos. ConsumosMaterialesSeeder::LOTE es ahora consumos_muebles_referencia_v2, aplicado una vez; mantener el evento v1 histórico. Se reemplazó base uniforme24 por montaje: Mesa12, Silla4, Puerta6 más bisagras, Escritorio8 madera/12 melamina más cajonera, cajas8 madera/12 melamina. Cajones suman12 madera/16 melamina, bisagras4, repisas fijas de mesas/escritorios4; soportes regulables no duplican fijaciones. Son cantidades de referencia, no universal de fabricación. Desglose en montaje_tornillos del plan.

Mesas62/82/86 tienen12 por unidad; otros materiales/cantidades/costos unitarios/subtotales idénticos. Se conservan87 casos/IDs1–87, fechas/descriptores/estados, mano de obra y otros costos; no cambia perfil de similitud. Recalculados precios y pagos proporcionales. Totales nuevos133514.17 acordado,125221.02 pagado,8293.15 saldo. Sin cambios de cuentas, permisos, stock, esquema o controladores.

Respaldo restaurado, ensayo aislado y navegador aprobados; 9 pruebas/2106 aserciones, Pint, conservación no-tornillos, integridad/idempotencia y11HTTP200/PDF local aprobados. Criterios e informe vigente en docs/CONSUMOS-MATERIALES.md, última sección. Evidencia visual docs/capturas/tornillos-materiales/.

Cierre tornillos v2: Excel actualizado y validado con los nuevos totales; copia de ensayo retirada, respaldo conservado y aplicación disponible. Casos1–87 y próximoID88 permanecen intactos.

## Umbral de similitud vigente: 70 % — 17/11/2026

Por solicitud explícita del usuario se reduce el umbral de80 a70 (inclusive). config/similitud.php usa perfil versionado config/perfiles/similitud-casos-historicos-v3.json con SHA256 compatible. Se conservan íntegros pesos, distancia, rangos,61 referencias y máximo/k3; perfilesv1/v2 quedan como historial. No modificar datos, cálculos de costos ni semilla por este cambio.

Se limpió solo la caché de configuración y se comprobó en lectura que una consulta incluya78.93%, manteniendo tres resultados. Pruebas de frontera:70 exacto admitido,69.999985 rechazado aunque redondee a70.00; casos de75 admitidos y límite3 respetado. Los escenarios históricos de SimilitudTest conservan explícitamente perfilv2/80%; un escenario separado valida el perfil activo70. El preparador de navegador y CasosPerfil usan el perfil activo por configuración.

## Pesos vigentes: dimensiones 30 % — 18/11/2026

El usuario aprobó reducir dimensiones a30% (largo/ancho/alto10% cada uno), material principal30%, acabado15%, puertas7%, cajones7%, divisiones6% (grupo20%) y cantidad5%. Total100%. Umbral70% inclusive, k/máximo3. Perfil activo config/perfiles/similitud-casos-historicos-v4.json con SHA256 compatible; v1/v2/v3 conservados como historial. Rangos,61 referencias, fórmula y cobertura estricta intactos.

La explicación desplegable de admin/similitud/_resultados lee los pesos de configuración, sin porcentajes fijos. No modificar costos, consumos, seeders ni datos por este ajuste; se comprobó la base intacta tras consultas. El ejemplo anterior Mesa150×60×50 ahora devuelve85.94%,83.44%,81.11% por la menor penalización dimensional.

SimilitudTest conserva pesos v2/80% en escenarios históricos y valida v3/70% y v4/pesos nuevos por separado. Cuatro pruebas/40 aserciones y Pint aprobados; validado97.75% para diferencias dimensionales conocidas,70% inclusive ante diferencia de material30% y máximo3. Caché de configuración limpiada. Búsqueda real sin escrituras aprobada.
