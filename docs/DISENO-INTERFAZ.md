# Diseño de interfaz vigente

Consolidación del 9 de noviembre de 2026 de las decisiones visuales ya implementadas. No introduce funcionalidades nuevas.

## Apariencia

AdminLTE 3 y Bootstrap 4; menú azul oscuro, fondos claros u oscuros y acento menta #84dfc3. Logo predeterminado y menú activo en menta; no recolorear logos subidos. Tarjetas antes amarillas usan el degradado menta #73d9bd–#a3ebd4 con texto oscuro #153f37. Volver/Cancelar conservan plomo; registrar azul, actualizar turquesa, eliminar coral y consultar celeste. Tipografía, esquinas y separación uniformes en botones.

Dashboard con conteos de registros, cotizaciones recientes y pedidos activos, sin gráfico vacío ni importes administrativos. La ganancia y demás datos restringidos no se muestran al maestro.

## Formularios y tablas

Dos o tres columnas según longitud del dato, apiladas en móvil. Etiquetas con iconos locales de Font Awesome, texto visible y asociación label/for; iconos decorativos aria-hidden=true. Se mantienen ayudas, obligatoriedad y errores del servidor.

Cotización guiada por mueble: características, materiales y costos; opciones secundarias desplegables. Material principal automático en su lista de costos, sin duplicación. Los valores unitarios/nominales y justificaciones permanecen; no se oculta un campo inválido sin abrir su sección.

Tablas con separación uniforme, bordes suaves, acciones en fila y desplazamiento horizontal interno cuando no caben. Activo/inactivo se distingue mediante texto, color e icono. Pedidos separa identidad, estado y acciones en su cabecera. La ficha Ver reúne datos/relaciones y responsables; la edición no muestra el historial de auditoría.

Volver y Cancelar en edición de Pedidos/Planificación regresan al listado propio. Ver pedido permite acceder expresamente a la ficha. No se usan history.back ni destinos externos derivados de cabeceras.

## Navegación y avisos

Menú lateral fijo con desplazamiento independiente de opciones. Barra superior sticky para conservar el botón de apertura en móvil. Se respetan apertura/cierre, teclado y menú según permisos. Las opciones futuras con ruta nula no se presentan como módulos funcionales.

SweetAlert2 local para avisos y confirmaciones, con valores codificados; validación y autorización permanecen en servidor. No editar vendor ni duplicar scripts por fila.

## Tema claro/oscuro

x-theme-toggle está en barra superior y acceso. tajibo.tema se guarda en localStorage con light/dark, tema claro inicial sin elección. El parcial tema-inicial aplica la preferencia antes del contenido y resources/js/tema.js actualiza botón, aria-pressed, nombre accesible, icono y color-scheme.

El cambio no recarga ni envía formularios. Persiste al navegar/cerrar sesión y sincroniza pestañas; almacenamiento bloqueado no impide cambiar el tema en la página. La preferencia pertenece al navegador, no a una cuenta ni a una columna de MySQL. Tablas, campos, fichas, ayudas, SweetAlert2 y paginación tienen estilos oscuros. El PDF de servidor conserva su presentación imprimible.

## Recursos y evidencia

CSS compartido en resources/css/app.css; JavaScript propio en resources/js; overrides en resources/views/vendor/adminlte. Los assets se compilan mediante npm run build; los botones no mezclan Bootstrap 5 ni otra plantilla.

Galería de la entrega visual conservada: [panel claro](capturas/tema-claro-oscuro/dashboard-propietario-1440.png), [panel oscuro](capturas/tema-claro-oscuro/tema-oscuro-dashboard-1440.png), [formulario móvil oscuro](capturas/tema-claro-oscuro/tema-oscuro-cotizacion-390.png). Estas capturas son evidencia del 5 de noviembre, no imágenes nuevas de esta limpieza. Resultados nuevos en [verificación](VERIFICACION.md).

## Formularios compactos de Categorías — 10 de noviembre de 2026

Se conserva el diseño previo. Único ajuste visual: Crear y Editar categoría quedan dentro de row y col-12 col-lg-10 col-xl-8, para que sus dos campos no estiren la tarjeta por toda la fila en escritorio; en móvil se usa todo el ancho disponible. Listado, Ver, colores, campos, permisos y controladores sin cambios. Sin cambios de CSS ni necesidad de recompilar recursos. Pruebas CategoriasTest: 2 aprobadas/27 aserciones en SQLite aislado.

Categorías, ajustes posteriores del 10/11/2026: la cabecera del listado conserva su fondo claro original; los nombres de categoría usan el azul del catálogo con negrita, según la última solicitud, sin aplicar la variante 2. La ficha Ver conserva su diseño y distribuye Información de la categoría y Materiales de la categoría en una fila con dos columnas col-12 col-lg-6, según la aclaración posterior del usuario. En pantallas pequeñas se apilan; responsables permanece debajo, sin quitar información. El encabezado recupera el ancho normal.

## Revisión individual de Materiales — 10 de noviembre de 2026

Se conserva el listado y su cabecera clara: los nombres ya eran enlaces azules en negrita. Crear/Editar usa col-12 col-xl-10, campos habituales en dos columnas y Unidad/Costo/Estado en tres columnas desde xl, dos en tableta y apilados en móvil. Ver usa record-fields en dos columnas en lugar de la variante de una sola columna. No cambian campos, ayudas, importes, validaciones, consultas, rutas, políticas, auditoría, existencias ni MySQL. Responsables solo en Ver. No se aplicó el rediseño rechazado.

Verificación: 18 pruebas existentes de CategoriasTest, CatalogoSimplificadoTest y AuditoriaGestionTest, 189 aserciones, aprobadas en SQLite. Navegador específico tests/browser/materiales.cjs: 20 comprobaciones/17 capturas, sin errores JS; 1440/1024/390 y claro/oscuro, alta/edición/filtros/costos del maestro sin administración y distribución de campos. Todos los registros de prueba son aislados en SQLite temporal. [Capturas](capturas/materiales-2026-11-10/resultado-browser.json). No fue necesaria compilación de recursos: solo cambiaron clases Bootstrap de las vistas.

Aclaración posterior de Materiales: Ver presenta dos tarjetas en una fila (col-12 col-lg-6): detalle del material a la izquierda y responsables del registro a la derecha. En la tarjeta derecha los responsables se apilan para facilitar la lectura; en móvil ambas tarjetas se apilan. Los estilos están delimitados a material-detail-columns y no modifican el componente de auditoría de otros módulos. Se conservan datos, permisos y enlace a existencias.

## Campos de consulta con recuadros — 10 de noviembre de 2026

Por referencia visual del usuario, los datos de Ver categoría y Ver material usan recuadros con fondo suave, borde fino y esquinas redondeadas, icono en un pequeño fondo a la izquierda, etiqueta arriba y valor debajo. Las fichas conservan su disposición de tarjetas en dos columnas; los campos se adaptan al espacio, con una columna interna entre 992 y 1199 px para evitar palabras cortadas y apilado móvil. El estilo record-fields-boxed se activa solo en estas dos vistas. Se mantiene el texto accesible y aria-hidden en los iconos; valores, permisos, rutas, enlaces y responsables no cambian. Estilos oscuros incluidos.

Compilación Vite aprobada y 18 pruebas/189 aserciones de categorías, catálogo y auditoría aprobadas en SQLite. Capturas y resultados específicos en docs/capturas/fichas-recuadros-2026-11-10; todas las operaciones de navegador utilizan SQLite temporal. No hay cambios MySQL ni nuevas dependencias.

## Versión 1 Tarjetas elegida — 10 de noviembre de 2026

Adaptada a Ver categoría y Ver material: col-xl-8 para información y col-xl-4 para responsables; ambas áreas se apilan antes de xl. Nombre de categoría y descripción del material ocupan toda la fila; datos cortos en tarjetas de dos columnas. Materiales: categoría/unidad y especificaciones/costo comparten sus filas. Categorías conserva además su tabla de materiales bajo la ficha. Actores y fechas siguen procediendo del componente existente, sin sustituirlos por textos de la imagen. Botones, permisos y datos se conservan, con temas claro/oscuro. No cambia el listado ni Crear/Editar.

Compilación aprobada; 18 pruebas/189 aserciones aprobadas en SQLite; 46 comprobaciones de navegador entre ambos módulos, sin errores JS. Capturas en docs/capturas/fichas-version1-2026-11-10. Sin escrituras MySQL ni dependencias nuevas.

## Buscador de Categorías — 11 de noviembre de 2026

Se reutiliza x-buscador con placeholder específico «Buscar categoría por nombre». El componente conserva el texto anterior por defecto para los demás módulos. CategoriaController.index valida q como cadena de hasta 200 caracteres, recorta espacios para la consulta y filtra por nombre; mantiene 12 resultados por página y withQueryString. Buscar/Limpiar conservan sus iconos y colores existentes. Mensaje específico cuando no hay coincidencias. No se cambian permisos ni se modifica MySQL.

Verificación: 20 pruebas funcionales/217 aserciones aprobadas en SQLite, incluidas búsquedas, filtro paginado, limpieza, consulta vacía, entradas inválidas y maestro sin administración. Navegadores específicos de Categorías y Materiales aprobados sobre SQLite temporal, sin errores JS; evidencia en docs/capturas/busqueda-categorias-2026-11-11. No fue necesario recompilar estilos o JavaScript.

Botones Limpiar, 11/11/2026: por solicitud del usuario usan el mismo degradado ocre de Categorías (#aa652c → #ba783e), sustituyendo el rosado. Iconos, enlaces y funciones sin cambios.

Accesos entre listados, 11/11/2026: se retiran Categorías del listado de Materiales y Volver a materiales del listado de Categorías por solicitud del usuario. Las opciones del menú, formularios y botones Volver de las fichas se conservan.

Color definitivo de Limpiar, 11/11/2026: el usuario eligió Ciruela (#874F70) de la comparativa visual. Sustituye el ocre anterior en btn-clear, con texto blanco e icono de goma de borrar; función sin cambios.

## Barra superior Perfil protagonista — 11 de noviembre de 2026

Aplicada la variante 06 seleccionada por el usuario. Mi perfil usa una tarjeta violeta con icono y el rol propio (Propietario/Maestro carpintero); el rol tiene descripción accesible y procede de la cuenta autenticada. Tema oscuro/claro conserva su implementación y persistencia, con presentación delineada solo en la barra; el login no cambia. Salir usa borde rojo y conserva formulario POST con CSRF. Fecha local con calendario visible desde lg; identificación Tienda & taller visible desde xl para no comprimir las acciones en tableta. Controles accesibles por teclado, adaptación hasta 320 px y colores oscuros específicos.

Archivos: override resources/views/vendor/adminlte/partials/navbar/navbar.blade.php y estilos propios resources/css/app.css; vendor no se modifica. Sin cambios de autenticación, rutas, roles, permisos o MySQL. Recursos compilados y caché Blade limpiada. Verificación: 11 pruebas de UsuariosPerfilTest/118 aserciones aprobadas en SQLite; 27 comprobaciones Edge/Playwright, 24 capturas, propietario/maestro en 1440/1024/768/576/390/320 px y ambos temas, persistencia al recargar, teclado, perfil y salida efectiva, sin errores JS. Evidencia en docs/capturas/barra-perfil-2026-11-11 y pruebas en tests/browser/barra-superior.cjs.

## Barra con botones rellenos y calendario — 11 de noviembre de 2026

Por petición posterior, Tema, Calendario y Salir usan el estilo relleno de Mi perfil con colores diferenciados, radios y altura coherentes. Mi perfil conserva su tarjeta violeta y rol real. La fecha se convierte en botón que abre un calendario de solo consulta: semana de lunes a domingo, meses anterior/siguiente, años bisiestos, indicador Hoy, regreso al mes actual y cierre. Fechas no seleccionables, sin eventos o escrituras. Se usa la fecha local emitida por Laravel y aritmética UTC para los meses, evitando cambios por zona horaria del navegador. Límite de representación 1–9999, sin restricciones de negocio sobre fechas.

Componente resources/views/components/calendario.blade.php y módulo resources/js/calendario.js, importado por app.js; sin dependencias nuevas ni cambios de base. Panel accesible con foco, Escape, cierre externo, controles etiquetados y adaptación de posición al ancho disponible. También se cierra al desplazar la página o cambiar su tamaño. En móvil, fecha/tema usan iconos y el texto de Salir se oculta solo por debajo de 360 px, conservando su nombre accesible. Temas claro y oscuro.

Compilación aprobada; 15 pruebas/173 aserciones de perfil y categorías aprobadas en SQLite; 52 comprobaciones Edge/Playwright, 48 capturas, propietario y maestro de 320 a 1440 px. Verifica persistencia de tema, perfil, salida, foco, Escape, febrero de 2028 (29 días)/2027 (28), inicio de semana, cambio de año, Hoy, fechas sin selección y ausencia de solicitudes de escritura desde el calendario. Cero errores JS. Evidencia en docs/capturas/barra-calendario-2026-11-11 y pruebas en tests/browser/barra-superior.cjs. No se ejecutaron migraciones ni cargas sobre MySQL.

Identificación izquierda de la barra, 11/11/2026: aplicada la opción 01 Clásico elegida por el usuario, con «Tienda & taller» y «La Paz · El Alto» en negrita, separados por una línea vertical fina. Icono del menú y comportamiento responsive conservados; sin cambios de datos o navegación.

Negrita en las fichas Ver de Categorías y Materiales, 11/11/2026: etiquetas de campos, valores principales (nombre, estado, cantidades, categoría, unidad y costo), nombres de materiales relacionados y responsables resaltados. Descripción, especificaciones, ayudas y fechas conservan su peso anterior. Sin cambios de distribución, colores, datos o permisos.

Iconos de la barra, 11/11/2026: Tema, Calendario y Salir igualan el tamaño de Mi perfil (23 px en escritorio y 20 px en móvil); se ajusta el relleno de los controles de icono para conservar su encaje a 320 px. Compilación y recorrido de barra/calendario aprobados para ambos roles, claro/oscuro y 320–1440 px. Evidencia en docs/capturas/barra-iconos-2026-11-11.

## Revisión individual de Clientes — 11 de noviembre de 2026

Listado con nombres azules en negrita y cabecera clara, manteniendo buscador por nombre/teléfono/correo y paginación existente. Crear/Editar conserva sus siete campos y todos los atributos de los controles; solo se limita el ancho a col-12 col-xl-10. Nombre/tipo, contacto y observaciones/estado mantienen sus filas y columnas actuales.

Ver adopta la versión Tarjetas aprobada: información en col-xl-8 y responsables en col-xl-4, apilados en pantallas menores. Nombre y observaciones ocupan toda la fila; tipo, teléfono, correo y dirección usan dos columnas. Se agregan iconos y negrita selectiva y se conserva Volver a clientes. El nombre, ya visible en el encabezado, también se muestra dentro de la ficha; no es un campo nuevo de registro. Opcionales vacíos conservan «—». Sin cambios de controladores, políticas, rutas, validaciones, auditoría, esquema o registros MySQL.

Verificación: 20 pruebas/190 aserciones de FaseTresTest, AuditoriaGestionTest, AdaptacionEscolarTest y EstructuraEscolarTest aprobadas en SQLite. Navegador específico de Clientes: 20 comprobaciones/19 capturas, registro, errores del servidor con datos conservados, edición/estado/tipo, búsqueda por contacto, limpiar, paginación, cancelación de borrado, volver y maestro sin administración; 1440/1024/390 px y claro/oscuro. Recorrido previo de regresión: 133 comprobaciones aprobadas, sin errores JS. Los selectores de tabla de los escenarios se delimitan a content-wrapper para excluir la tabla oculta del calendario incorporado anteriormente; no cambian las funcionalidades. Las escrituras usan SQLite temporal.

Capturas: docs/capturas/clientes-2026-11-11; regresión en docs/capturas/revision-clientes-regresion-2026-11-11. Copias y logs en storage/app/private/revision-clientes-2026-11-11. No fue necesario compilar recursos: se reutilizan Bootstrap y los estilos ya aprobados. No se avanzó a otro módulo.

Botones globales en negrita, 11/11/2026: por solicitud del usuario, peso 700 en las etiquetas de botones de todos los módulos, acciones nativas, Hoy del calendario y confirmaciones SweetAlert2. Se conservan colores, iconos, tamaños y funciones; las fuentes de iconos y los textos secundarios del rol no se alteran.

Las cabeceras de las tablas utilizan negrita (700), conservando sus colores, espaciado y distribución actuales.

Los subtítulos de los encabezados de página utilizan negrita (700), junto con los títulos y las etiquetas superiores que ya estaban resaltados. Se conservan tamaños y colores.

Mi perfil se organiza en dos columnas desde pantallas grandes: datos personales (nombre, identificación, teléfono y dirección) y cuenta (correo, rol y estado de consulta, cambio de contraseña). En pantallas pequeñas se apilan. Se mantienen el formulario único de actualización del perfil, las validaciones y el cambio obligatorio de contraseña inicial; rol, estado e identificación no son editables desde el perfil.

Actualización del perfil: la identificación deja de ser de consulta y se presenta como selector de tipo y campo de número en la columna de datos personales, editables por cada usuario. Solo rol y estado permanecen de consulta en Mi perfil.

Mi perfil: datos personales y datos de usuario comparten la primera fila en dos columnas. Cambiar contraseña ocupa una segunda fila independiente, con sus campos distribuidos en hasta tres columnas según el ancho de pantalla. En móviles se apilan. Se conservan las asociaciones de formularios y sus validaciones.

Distribución vigente de Mi perfil: datos personales y datos de usuario agrupados en la columna izquierda; cambio de contraseña en la derecha. En móviles se apilan. Esta distribución sustituye la separación anterior de datos personales y cuenta en columnas diferentes y la contraseña en una fila inferior. Campos, validaciones y acciones permanecen iguales.

Divisiones de Mi perfil: tarjetas redondeadas con bordes y sombra suaves, encabezados verdes con iconos en recuadros y descripciones breves, separación uniforme y divisores sobre las acciones. Estilos limitados al perfil; se conservan sus dos columnas y formularios.

Mi perfil: datos personales y datos de usuario comparten una sola tarjeta y formulario en la columna izquierda, con un separador interno para la cuenta. Se retira el segundo cuadro de usuario. La contraseña conserva su tarjeta independiente a la derecha.

Barra superior compacta: botones de 40 px de alto mínimo (antes 46 px), iconos uniformes de 18 px, menor espacio interior y separación de 8 px. Se conservan colores, negrita, etiquetas y funciones de tema, calendario, perfil y salida.

## Revisión individual de Ajustes — 11 de noviembre de 2026

Datos de la carpintería a la izquierda (col-lg-8) y tarjeta de logo a la derecha (col-lg-4); en móvil se apilan. Nombre/teléfono y correo/web comparten filas de dos campos; dirección y descripción usan el ancho de la columna de datos. Un solo formulario multipart y CSRF conserva campos, valores, errores y acciones. Vista previa y selector de logo agrupados. Sin cambios de controlador, validaciones, permisos, rutas ni MySQL.

Encabezado de Configuración: se aplica únicamente a este módulo la opción 04 Oscuro elegante seleccionada: fondo azul carbón, detalle geométrico suave, texto claro y negrita, línea menta e icono de ajustes delineado. Mantiene contenido y adaptación móvil; no cambia el componente compartido ni los encabezados de otros módulos.

Configuración, ajuste posterior: encabezado sin fondo, borde de tarjeta, sombra ni decoración geométrica; ocupa solo el ancho de su contenido. Mantiene título, descripción, línea menta e icono delineado, con texto adaptado al tema claro/oscuro. Sustituye el fondo oscuro de la opción 04 anterior.

Configuración, selección vigente: opción 04 Oscuro elegante con fondo azul carbón, texto claro y acento menta. Icono de ajustes situado primero, a la izquierda del título; cuadro ajustado al contenido, sin ocupar toda la fila. Sustituye la variante anterior sin fondo y no modifica otros módulos.

Configuración, integración visual: se retira el fondo oscuro y el contorno exterior del encabezado para evitar un bloque aislado. Se conserva el ancho del contenido, icono primero a la izquierda, título en negrita y línea menta. Los textos se adaptan al tema claro/oscuro.

Configuración, ajuste tipográfico vigente: se retira el icono del encabezado y su CSS sin uso. Título de 30 px en negrita (26 px en móvil), etiqueta superior de peso 500 y descripción de 15 px con peso normal (14 px en móvil). Se conserva la línea menta y el ancho ajustado al contenido; cambios limitados a este encabezado.

## Encabezados unificados de módulos revisados — 11 de noviembre de 2026

Categorías, Materiales, Clientes, Mi perfil y Ajustes adoptan el encabezado aprobado sin fondo ni icono: título de 30 px en negrita, contexto con peso 500 y línea menta, descripción de 15 px con peso normal; tamaños de 26/14 px en móvil. Aplicado a listado, registro, edición y consulta donde existen. La variante refined del componente page-heading permite limitar el cambio a estos módulos; los demás conservan su presentación. Se mantienen botones, textos existentes, permisos y formularios.

Legibilidad de los módulos revisados: aumento moderado de un píxel en etiquetas (14), campos/tablas (15), botones/valores de ficha (14), cabeceras de tabla (13) y ayudas (12). Se delimita al contenido de Categorías, Materiales, Clientes, Mi perfil y Ajustes mediante su encabezado revisado. Se conservan familia Segoe UI, pesos, colores y encabezados ya aprobados; menú y barra superior mantienen sus tamaños compactos. Sin cambios funcionales.

Jerarquía de negritas en Ver Categoría, Material y Cliente: títulos y nombres principales en 700; costo unitario y cantidad de materiales relacionados conservan énfasis por ser datos clave. Etiquetas en 600, valores secundarios en 400 y responsables en 500. Se retira strong de estado textual, categoría/unidad del material y tipo/teléfono/correo del cliente. Descripciones, direcciones y observaciones quedan en texto normal. Se mantienen tamaños ampliados, iconos, colores, datos y acciones.

Ver categoría: Materiales de la categoría reutiliza el encabezado de ficha de Información de la categoría, con icono, fondo suave y subtítulo. El estado es opcional en el componente para omitirlo en esta lista; las fichas que lo reciben conservan sus distintivos. Tabla, datos, paginación y acciones sin cambios.

## Revisión individual de Usuarios — 11 de noviembre de 2026

Encabezados refinados y tamaños de lectura aprobados en listado, alta, edición y consulta. Nombres azules y en negrita en la tabla; sus estados conservan el diseño actual pendiente de elección. Crear/Editar usa col-xl-10 y dos campos por fila desde md. Ver adopta recuadros con iconos, nombre destacado y datos secundarios normales; información a la izquierda (8/12), responsables a la derecha (4/12) desde xl, apilados en pantallas menores. Mismos campos, validaciones, roles, rutas, contraseña inicial, auditoría y acciones; sin cambios del servidor ni MySQL.

Verificación de Usuarios: 20 pruebas funcionales/220 aserciones aprobadas en SQLite; 31 comprobaciones de navegador y 22 capturas en docs/capturas/usuarios-revision-2026-11-11. Incluye cuatro vistas a 1440/1024/390 px, tema oscuro móvil, alta y edición, primer acceso/cambio obligatorio y protección de roles/estado. Sin escrituras sobre MySQL.

Estado de registros: Activo e Inactivo quedan solo con texto, retirando iconos de los listados de Categorías, Materiales, Clientes y Usuarios y del encabezado de ficha. Inactivo usa fondo menta #84dfc3 y texto verde oscuro #174c40; Activo conserva su color actual. Se delimita con estado-registro para no cambiar los estados comerciales, similitudes ni revisiones de casos. Sin cambios de valores, controles o permisos.

Estado de registros, decisión vigente: etiquetas solo con la palabra Activo o Inactivo, sin iconos. Activo: fondo menta #84dfc3 y texto verde oscuro #174c40; Inactivo: fondo plomo oscuro #59636f y texto blanco. Se aplica mediante estado-registro a los listados y encabezados de ficha, incluido el listado de existencias. Sustituye la asignación anterior de menta a Inactivo. No cambia estados de cotizaciones, fabricación, casos ni sus valores/permisos.

Estado de registros, ajuste posterior: se restauran los iconos junto al texto (confirmación para Activo, círculo con menos para Inactivo), manteniendo Activo menta e Inactivo plomo oscuro. Iconos decorativos aria-hidden; colores, valores y funciones sin cambios.

Etiquetas de roles: colores sólidos más intensos, verde #087b68 para Propietario y azul #2d66cb para Maestro carpintero, ambos con texto blanco. Se conservan forma, tamaño, roles y permisos existentes.

Roles, paleta de carpintería vigente: Propietario usa nogal oscuro #60412f y Maestro carpintero cedro #99572e, ambos con texto blanco. Sustituye los colores verde/azul anteriores; dimensiones y permisos no cambian.

Nueva cuenta: se retira el aviso visual de contraseña inicial por solicitud del usuario. La generación automática y el cambio obligatorio en el primer acceso permanecen iguales.

Usuarios: el controlador calcula si ya existe una cuenta propietaria y oculta la opción Propietario al crear y al editar maestros. La cuenta propietaria conserva su opción. Controlador y servicio rechazan crear/promover otro propietario, sin modificar cuentas existentes. Verificación: 35 pruebas/358 aserciones y 32 comprobaciones de navegador aprobadas con SQLite aislada; evidencia en docs/capturas/usuarios-propietario-unico-2026-11-11. No se ejecutan migraciones ni escrituras MySQL.

## Revisión individual de Roles y permisos — 11 de noviembre de 2026

Listado compacto (col-lg-10/col-xl-8), encabezado refinado con línea menta, roles con los tonos nogal/cedro ya aprobados y cantidad de permisos destacada. Edición mantiene sus grupos de módulos y casillas originales; tarjetas con iconos, opciones separadas y notas legibles de Obligatorio/Exclusivo del propietario. Permisos obligatorios tienen acento menta y restringidos fondo neutro; diseño oscuro incluido. Campos, hidden obligatorios, checked/disabled, valores, servidor, roles y permisos no cambian.

Verificación de Permisos: 19 pruebas funcionales/163 aserciones aprobadas (PermisosRolesTest y PermisosCotizacionTest), 17 comprobaciones de navegador y 12 capturas en docs/capturas/permisos-revision-2026-11-11. Incluye listado y edición de ambos roles a 1440/1024/390 px, oscuro móvil, guardado/restauración de permiso opcional, hidden obligatorios y bloqueo del maestro. Todas las escrituras de prueba usan SQLite temporal; no se modifica MySQL.

Delegación individual: botón Permisos en la fila de cada maestro, visible solo para el propietario autorizado; pantalla con dos grupos (Clientes y Materiales), casillas Registrar/Modificar y leyenda de permisos heredados. Los adicionales no modifican los permisos compartidos del rol. Las opciones operativas en la pantalla del rol indican Asignable por usuario, conservando bloqueados los permisos exclusivos.

Permisos generales e individuales: cuadros más compactos, con encabezados de menor alto, iconos de 26 px, cuerpos de 12 px de relleno y opciones con 7 px de relleno vertical/6 px de separación. Se mantiene la letra legible. La delegación individual utiliza el mismo ancho por módulo (col-xl-4) que los permisos generales, evitando extender sus dos tarjetas por toda la fila. No cambian casillas, valores, validaciones ni funciones.

Encabezados de los cuadros de permisos generales e individuales: adoptan el estilo claro de las fichas Ver, con fondo suave, título oscuro e icono azul en recuadro, conservando las dimensiones compactas. El tema oscuro adapta el fondo y el contraste; botones, casillas y funciones sin cambios.

Iconos de permisos: cada encabezado de módulo usa su icono correspondiente, coherente con config/navegacion.php (clientes/personas, materiales/cubos, pedidos/martillo, cotizaciones/documento, planificación/tareas, pagos/cartera, usuarios/equipo, etc.). Mapa compartido en gestion_permisos.iconos para permisos generales e individuales; iconos verificados en Font Awesome instalado, sin recursos externos nuevos.

Acciones de permisos generales e individuales: botones Volver y Guardar alineados a la izquierda; se conserva su orden y adaptación móvil.

Cuenta en menú lateral: el recuadro de usuario sube debajo del logo y contiene Mi perfil y Salir, retirados de la barra superior. Identidad y acciones quedan fuera del área desplazable de navegación; en móvil se accede al abrir el menú. Se conservan rol real, colores violeta/coral, rutas de perfil y salida POST/CSRF. Tema y calendario permanecen arriba.

Verificación de cuenta lateral: 112 comprobaciones de navegador aprobadas entre barra/calendario, Usuarios/perfil y Clientes. Incluye ambos roles, temas claro/oscuro, anchos de 1440 a 320 px, acceso a última opción del menú conservando fija la cuenta, apertura móvil, teclado, perfil y salida efectiva POST/CSRF. Datos aislados en SQLite; evidencia en docs/capturas/cuenta-lateral-2026-11-11.

Corrección de ubicación de cuenta: el recuadro de usuario está en la barra superior, inmediatamente junto al tema, y despliega Mi perfil y Salir al pulsarlo. Se retira del lateral. Desplegable nativo de Bootstrap 4, con cierre externo/Escape y salida POST/CSRF. La barra mantiene calendario y adaptación móvil; sustituye la disposición lateral anterior.

Verificación del desplegable de cuenta: 112 comprobaciones de navegador aprobadas (barra/calendario, Usuarios/perfil y Clientes), ambos roles y temas, anchos de 1440 a 320 px, posición junto al tema, menú sin recortes, cierre con Escape/clic externo, acceso a perfil y logout POST/CSRF. Evidencia en docs/capturas/cuenta-desplegable-2026-11-11; SQLite aislada.

Barra superior: se retira el botón de fecha y el calendario de consulta por solicitud del usuario. Permanecen tema claro/oscuro y cuenta desplegable. Se retiran componente, módulo JavaScript e importación/estilos exclusivos del calendario sin uso; los campos de fecha de los módulos conservan su funcionamiento.

## Vista compacta de permisos individuales — 13/11/2026

Por solicitud del usuario, usar la misma estructura de casillas y cuadros por módulo de los permisos generales. Marcar permite y desmarcar deniega solo a esa persona; retirar los selectores y los textos repetidos de acceso actual/heredado. Conservar iconos, protección del propietario y autorización del servidor. Los permisos que no se tocan conservan su regla anterior, incluida la herencia; no convertir todo el formulario en excepciones al guardar. El botón Usar permisos del rol prepara el restablecimiento de todas las excepciones y requiere Guardar para aplicarlo. Sin cambios de esquema ni asignaciones automáticas.

Verificación: 15 pruebas de permisos/217 aserciones aprobadas. Edge: ocho grupos de comprobación y seis capturas, incluyendo 1440/1024/390 px y tema oscuro. Concesión, denegación heredada, guardado con SweetAlert2 y restablecimiento del rol aprobados sobre SQLite temporal, sin errores JavaScript. Evidencia: capturas/permisos-individuales-casillas-2026-11-13/resultado-browser.json. Sin migraciones ni escrituras de prueba en MySQL local.

## Revisión visual de Existencias de materiales — 13/11/2026

Revisión individual autorizada al continuar después de Usuarios/Permisos. Aplicar los encabezados refinados y tamaños legibles ya aprobados al listado, agregar, corregir y movimientos. Listado con nombre azul/negrita; formularios de dos campos en col-12 col-lg-10 col-xl-8, reutilizando el parcial sin cambiar datos ni validaciones. Movimientos muestra una ficha compacta con iconos, existencia actual y unidad en dos columnas; historial completo debajo con encabezado de ficha y navegación conservada. No modificar controladores, servicios, cálculos, permisos, rutas ni MySQL por esta revisión.

Archivos de vistas: admin/existencias/index, create, edit y show; el parcial _form se mantiene íntegro. Se reutilizan page-heading, ficha-heading y record-fields-boxed sin CSS ni dependencias nuevas. Respaldo previo en storage/app/private/existencias-vistas-2026-11-13.

Verificación: 12 pruebas funcionales/128 aserciones aprobadas (ExistenciasMaterialTest); 23 grupos de comprobación y 16 capturas en Edge/Playwright, sin errores JavaScript ni desborde a 1440/1024/390 px o tema oscuro. Formulario limitado de ancho y campos alineados; ficha en dos columnas; agregar 5,5, reenviar sin duplicar y corregir a 3,25 mantiene exactamente dos movimientos. Maestro consulta historial sin altas/correcciones y obtiene 403 en sus URLs sin delegación. Datos SQLite temporales; no se ejecutaron migraciones ni escrituras sobre MySQL. Evidencias: capturas/existencias-revision-2026-11-13/resultado-browser.json; prueba reproducible en tests/browser/existencias.cjs.

Existencias, texto vigente: por solicitud del usuario, la acción Corregir cantidad pasa a llamarse Editar cantidad en el listado, encabezado y botón del formulario; la ayuda del historial usa editar. Se conserva el cálculo de diferencias, motivo, permisos y rutas. No activa la resta por fabricación pendiente de revisión.

## Ancho de listados con pocos datos — 13/11/2026

Por solicitud del usuario, los listados no deben ocupar toda la fila si sus datos permiten un ancho menor. Categorías, Usuarios, Cotizaciones, Pedidos y Pagos usan col-12 col-xl-10; Planificación, con cuatro columnas cortas, usa col-12 col-xl-8. Encabezado, acciones superiores, filtros, tabla y paginación comparten el ancho. Debajo de xl se usa todo el espacio disponible y se conserva el desplazamiento interno cuando es necesario. Roles ya conserva su ancho reducido; las tablas con descripciones extensas o muchas acciones mantienen espacio suficiente. No quitar columnas, campos o funciones para reducir el ancho.

Cambios limitados a seis index.blade.php y contenedores Bootstrap, sin CSS, dependencias, consultas ni controladores nuevos. Respaldo de vistas en storage/app/private/tablas-contenidas-2026-11-13. Verificación funcional: 76 pruebas/706 aserciones aprobadas en SQLite. Edge/Playwright: 43 grupos de comprobación y 36 capturas, cinco anchos (1920/1440/1280/1024/390) y tema oscuro móvil; paneles más estrechos, tablas/acciones sin recortes a 1440/1920, botones alineados y sin errores JavaScript. Datos temporales de cotización, pedido y pago, sin escrituras MySQL. Evidencia en capturas/tablas-contenidas-2026-11-13/resultado-browser.json; prueba reproducible tests/browser/tablas-contenidas.cjs. La resta automática por fabricación sigue pendiente de aprobación del diseño.

Ampliación solicitada a Materiales y Existencias: ambos listados también tienen ancho contenido, con sus encabezados y filtros alineados. Materiales usa col-12 col-xl-10; Existencias también usa col-12 col-xl-10, con menos relleno horizontal y separación entre icono y texto en sus acciones para que no se partan a 1440 px. En móvil conservan el ancho disponible y el desplazamiento interno cuando corresponda. Columnas, botones, rutas, permisos y cálculos se mantienen.

Verificación final de los ocho listados: 57 grupos de comprobación y 48 capturas en Edge, sin errores JavaScript; anchos 1920/1440/1280/1024/390 px y tema oscuro móvil. Existencias: 12 pruebas funcionales/128 aserciones aprobadas. Evidencias actualizadas en capturas/tablas-contenidas-2026-11-13/resultado-browser.json. Sin escrituras MySQL ni cambios del flujo pendiente de consumo al fabricar.

Corrección posterior de Existencias: listado, encabezado y filtros usan exactamente el ancho de Materiales (col-xl-10), sustituyendo el col-xl-11 que apenas reducía la fila. Sus acciones conservan texto, fuente, colores e iconos con utilidades px-2/mr-1. La tabla de movimientos también queda en col-xl-10, conservando sus siete columnas e historial completo. Navegador: 57 grupos de comprobación de listados y 23 de Existencias aprobados, sin errores JavaScript, con cantidades/reenvíos/permisos verificados en datos aislados.

Existencias, posición del botón superior: por solicitud del usuario, el encabezado del listado utiliza todo el ancho para situar Ver materiales al extremo derecho. La tabla y su buscador conservan col-xl-10. Esta excepción sustituye únicamente la alineación del encabezado de Existencias con su tabla; no cambia rutas, permisos ni otros módulos.

## Botones superiores al extremo derecho — 13/11/2026

El usuario extiende a los módulos revisados la ubicación aprobada en Existencias: los encabezados con acciones superiores aprovechan todo el ancho y sitúan sus botones a la derecha. Se amplían únicamente los contenedores del encabezado de Categorías, Materiales, Usuarios, Cotizaciones, Pagos y Movimientos de existencias. Clientes, fichas Ver y Permisos ya usaban el ancho completo. Tablas, buscadores, formularios y sus anchos reducidos se conservan; también iconos, textos, rutas y permisos. Esta decisión sustituye la alineación anterior del encabezado al ancho de la tabla, únicamente donde existen botones superiores.

Verificación Edge/Playwright aprobada: 57 grupos y 48 capturas de los ocho listados, incluidos posición de acciones a la derecha de las tablas contenidas en 1280/1440/1920 px, adaptación en 1024/390 y tema oscuro móvil. Sin errores JavaScript ni cambios MySQL. Fuente: tests/browser/tablas-contenidas.cjs; evidencia: capturas/tablas-contenidas-2026-11-13/resultado-browser.json.

Clientes, ajuste de ancho confirmado: el listado, buscador y paginación usan col-12 col-xl-10, igual que Materiales. El encabezado conserva su ancho completo para mantener Nuevo cliente al extremo derecho. Se conservan las seis columnas, textos, funciones y controles de acceso.

Verificación actualizada: 64 grupos de comprobación y 54 capturas en Edge de nueve listados; Clientes se probó con nombre, teléfono boliviano y correo. Botones alineados y tabla sin recorte a 1440/1920 px, sin desborde de página en 1280/1024/390 y tema oscuro móvil. SQLite temporal, cero escrituras MySQL. Evidencia: capturas/tablas-contenidas-2026-11-13/resultado-browser.json.

Clientes, legibilidad dentro del ancho reducido: teléfono en una línea mediante text-nowrap y correo con text-break para permitir ajuste de valores largos sin ocultarlos. Se mantienen los valores completos y los mismos botones. Repetición visual final aprobada con datos de contacto representativos.

## Clientes: ancho ajustado al contenido — 13/11/2026

Por aclaración del usuario sobre el espacio sobrante tras las acciones, el listado de Clientes sustituye la fracción fija col-xl-10 por col-auto (máximo disponible de Bootstrap) y la tabla utiliza w-auto. Tarjeta y columnas adoptan el ancho necesario del contenido, sin estirar Acciones para llenar un porcentaje de la pantalla. Se conserva el relleno normal del borde, el encabezado con Nuevo cliente a la derecha y los mismos datos/acciones. En móvil se mantiene desplazamiento interno. Esta decisión sustituye para Clientes el ancho fijo anterior.

Navegador aprobado en nueve listados: 64 grupos y 54 capturas. Para Clientes se mide además que el espacio a la derecha de los botones no supere el padding normal más dos píxeles y que el borde de la tarjeta coincida con el de la tabla en escritorio. Sin errores JavaScript ni cambios de lógica/MySQL. Evidencia: capturas/tablas-contenidas-2026-11-13/resultado-browser.json.

Existencias, ancho al contenido: por solicitud del usuario, el listado adopta col-auto y la tabla w-auto, igual que Clientes. Sustituye su fracción fija col-xl-10 y evita estirar la columna de acciones. Se conserva el relleno normal, los tres botones alineados, el buscador y Ver materiales al extremo derecho del encabezado. Sin cambios de cantidades, permisos, rutas ni base de datos.

Comprobación visual repetida y aprobada en escritorio/móvil y tema oscuro: 64 grupos de comprobación y 54 capturas de nueve listados. Para Existencias se mide específicamente que solo quede el padding normal después de las acciones y que tarjeta/tabla compartan borde derecho. Evidencias: capturas/tablas-contenidas-2026-11-13/resultado-browser.json.

Existencias, navegación desde movimientos: el botón Volver regresa a admin.existencias.index sin material_id, mostrando el listado completo. Sustituye el regreso filtrado anterior. Se conserva la posibilidad de filtrar al abrir desde la ficha de un material y el resto de las rutas. Verificado en navegador: tras volver aparecen los tres materiales de la fixture aislada, no únicamente el consultado; 23 grupos de comprobación aprobados.

## Existencias: acceso al listado general — 13/11/2026

Por solicitud del usuario, Agregar cantidad y Editar cantidad incluyen Listado general en su encabezado, hacia admin.existencias.index sin filtros. Cancelar y las redirecciones de store/update también regresan al listado completo. Cuando el listado se abre con material_id, se ofrece Listado general junto a Ver materiales. La consulta filtrada desde Materiales permanece disponible; las operaciones de cantidades, claves, permisos y auditoría no cambian.

Verificación: 12 pruebas funcionales/133 aserciones aprobadas, incluyendo destino exacto de redirección; 24 grupos de comprobación Edge y 16 capturas. Tras guardar, cancelar o pulsar Listado general desde ambos formularios aparecen los tres materiales de la fixture; las dos operaciones de cantidades conservan exactamente dos movimientos y su reenvío no duplica. SQLite aislada, sin escrituras MySQL. Evidencia: capturas/existencias-revision-2026-11-13/resultado-browser.json.

Aclaración vigente sobre Listado general en Existencias: el usuario se refiere a la pantalla individual que aparece después de guardar o editar. Se restaura la redirección de store/update con material_id y se conserva allí el botón Listado general. Se retiran los botones añadidos por interpretación anterior a los formularios y Cancelar conserva su salida filtrada. Volver desde movimientos continúa mostrando todos. Verificación: 12 pruebas/133 aserciones y 24 grupos de navegador aprobados; tras cada guardado hay una fila y al pulsar Listado general aparecen las tres de la fixture. Sin cambios en cantidades o MySQL. Sustituye la instrucción anterior de redirigir automáticamente al listado completo tras guardar.

Existencias, Cancelar — decisión vigente 14/11/2026: Cancelar en Agregar cantidad y Editar cantidad va al listado general sin guardar. Solo guardar/agregar o editar conserva la redirección al material individual con acceso a Listado general. Volver desde movimientos sigue llevando a todos. Verificación Edge: 25 grupos de comprobación aprobados; cancelar con campos modificados no cambia cantidades ni crea movimientos. Prueba aislada, sin escrituras MySQL.

Usuarios, grupo de permisos bloqueado — 14/11/2026: en las vistas general e individual se conserva el mismo cuadro compacto con su icono. Sus cinco casillas están deshabilitadas y con candado; propietario marcadas, maestro desmarcadas. Una nota breve explica que Usuarios es exclusivo del propietario. Restablecer el rol no activa casillas bloqueadas del maestro. Se conservan tipografía, temas y las demás casillas editables. Verificación: 17 grupos de navegador en general y nueve en individual, 18 capturas en capturas/permisos-usuarios-bloqueados-2026-11-14; sin errores JS. El servidor rechaza modificaciones de ese grupo.


## Cotizaciones: revisión visual — 14/11/2026

Aplicado a index/create/edit/show de admin/cotizaciones, reutilizando page-heading refinado, ficha-heading y record-fields-boxed. Encabezados sin fondo propio, tamaño legible y acciones superiores a la derecha; formularios y consulta con col-xl-10 y ancho disponible en móvil. Ver agrupa datos y costos en dos columnas con iconos, énfasis en material e importes finales y responsables compactos. El listado conserva ancho, filtros, columnas y acciones, con ID azul en negrita.

No se modifican el formulario compartido ni sus parciales, JavaScript, cálculos, controles de acceso, servicios, rutas, PDF o MySQL. Se conservan las recomendaciones, campos opcionales, validación y aprobación. Capturas en docs/capturas/cotizaciones-revision-2026-11-14.


## PDF de cotización — 14/11/2026

Plantilla resources/views/pdf/cotizacion.blade.php actualizada: cabecera con logo opcional, nombre y contacto; ID normal destacado; fechas agrupadas; tabla azul oscuro, filas alternas y total en menta. Formato A4, pie con numeración y encabezado de tabla repetido en varias páginas. Conserva características, observaciones, condiciones, datos del cliente cuando ya existían y precios aprobados. Sin costos internos ni porcentaje de ganancia. Servicio, autorización, consultas y cálculos sin cambios. Vista previa ilustrativa en output/pdf/cotizacion-vista-previa.pdf, generada con datos en memoria.


### Corrección del PDF para coincidir con las pantallas — 14/11/2026

Por observación del usuario, se sustituye la tabla de cabecera azul oscuro y el bloque total menta de la primera propuesta por el lenguaje visual de las fichas del sistema: encabezados claros #f0f6fc, bordes #e8eef5, recuadros #fafcfe, nombres azul #245e82 y total verde #23734e. Fechas en dos recuadros, ID en el encabezado, estado en pastilla suave e iconos vectoriales incrustados sin fuentes adicionales. Se mantiene el logo configurado, el contenido comercial, la impresión A4 y la numeración. Esta corrección sustituye la descripción visual anterior del PDF.


PDF, encabezado sin número (14/11/2026): por solicitud del usuario, mostrar una sola vez Cotización, con línea menta y estado debajo. Retirar el número visible, la etiqueta repetida Cotizaciones y el número del título HTML del documento; renombrar la clase a document-title. Se conserva el ID interno y las rutas. Vista previa regenerada sin SQL, encabezado inspeccionado y sintaxis verificada. Esta decisión sustituye la mención anterior de ID destacado en el PDF.


Estados, 14/11/2026: unificar record-status.is-active en menta #84dfc3 con texto #174c40 y record-status.is-inactive en plomo oscuro #59636f con texto blanco. Incluye los estados de Cotizaciones que antes conservaban tonos claros anteriores. Iconos, etiquetas y reglas de estado sin cambios. npm run build y 28 comprobaciones de navegador de Cotizaciones aprobados, con temas claro/oscuro y SQLite aislada.


Cotizaciones, 14/11/2026: listado ajustado a su contenido mediante col-auto/w-auto. El último botón queda junto al relleno normal de la celda, sin estirar Acciones. Recorrido de navegador aprobado: 29 comprobaciones y 21 capturas; incluye medición del espacio tras la última acción, escritorio/móvil, ambos temas y flujos anteriores. No se alteran filtros, columnas, rutas ni funciones.


## Crear cotización: recuadros y distribución — 14/11/2026

La clase quote-create-layout delimita los cambios visuales al alta. Guía y datos generales comparten una fila desde 1200 px; campos principales en recuadro suave con dos/tres columnas. Acabado y componentes opcionales comparten fila desde md al crear; en edición componentes conserva todo el ancho. Se mantienen desplegables opcionales, orden de campos, identificadores y atributos de cálculo. Tarjetas con borde suave y esquinas de 12/14 px, legibles en ambos temas; en móvil todo se apila. No hay campos nuevos, cambios de servicio, permisos, recomendaciones o MySQL.


## Tipos de muebles — 14/11/2026

Nuevo acceso en Catálogo → Tipos de muebles con icono de silla. Index con buscador, tabla al contenido (col-auto/w-auto), nombre azul en negrita, estado menta/plomo con icono y botones Ver/Editar alineados. Crear/Editar usa formulario contenido con nombre y estado en una fila; Ver utiliza ficha de dos campos e iconos y responsables en columna lateral. Se reutilizan componentes, tipografía y colores aprobados; sin CSS o dependencias nuevas. No hay botón Eliminar ni campo de código. Capturas: docs/capturas/tipos-mueble-2026-11-14.


## Cotizaciones sin ajuste adicional de mano de obra — 15/11/2026

Se retira el desplegable Ajuste de mano de obra y precio propuesto de Crear/Editar. Desaparecen sus tres campos; Mano de obra por mueble y Otros costos permanecen juntos en una fila. Resumen → ganancia sobre costo cuando el permiso lo permite → precio sugerido → guardado → aprobación. Si una estimación antigua conserva un ajuste, se muestra un aviso de solo lectura; al modificar cantidad/mano unitaria o aplicar otra referencia se utiliza el cálculo simple. El aviso y el navegador toman la referencia anterior desde el registro guardado; el servidor decide con sus datos reales, no con los atributos HTML. JavaScript ya no busca ni limpia controles inexistentes al aplicar un caso. Los ajustes de materiales se conservan.


Restauración posterior del 15/11/2026: por solicitud del usuario, se restablece el desplegable opcional de ajuste de mano de obra y precio propuesto en Crear/Editar, con sus tres campos y diseño anterior. La sección se abre al editar cuando hay valores o errores relacionados. Guía, Datos generales a la izquierda, recuadros y organización de los otros campos permanecen. Se restauran el JavaScript y servicio originales de ese bloque; la prueba exclusiva de la simplificación queda archivada en el respaldo privado.


## Ver cotización: recuadros en filas — 15/11/2026

Datos de la cotización y Resumen de importes comparten dos columnas desde 1200 px. Los cuatro importes breves por mueble comparten fila desde 1400 px. Las tablas de materiales conservan el ancho del detalle. Aprobación y responsables se agrupan en dos columnas cuando corresponde; si no hay aprobación disponible, responsables conserva una sola columna contenida. En móvil se apilan; cabecera y botones superiores se mantienen. Estilos limitados a quote-show-layout. Solo cambia presentación, sin modificar datos, cálculos, permisos, rutas ni PDF.


Cotizaciones, 15/11/2026: formato de entrada compacto. Porcentaje, medidas, cantidades, costos, ajuste de mano de obra, precio propuesto y precio final de aprobación eliminan únicamente ceros decimales finales. Ejemplos: 20.0000 → 20; 20.5000 → 20.5; 0.000100 → 0.0001. Se conservan decimales significativos y punto decimal para escribir. Catálogo y casos aplicados utilizan el mismo criterio; campos vacíos siguen vacíos. Sin cambios del formato monetario de consulta/PDF ni precisión de la base.


Cotizaciones, 15/11/2026: Datos generales conserva un único campo opcional Observaciones, con todo el ancho de su recuadro; se retiran el campo Condiciones para el cliente y la etiqueta Notas y condiciones de los formularios. El resto de la distribución, campos y acciones permanece. Las condiciones de documentos anteriores se conservan en su ficha/PDF y no se sobrescriben por guardar un formulario que ya no las contiene.


Menta intensa (15/11/2026): Precio final estimado, costo total del mueble, precio sugerido, indicadores de fabricación/terminado, icono del taller y avisos de éxito utilizan la menta #84dfc3 en lugar de tonos pálidos. Tarjeta métrica menta con degradado más intenso #62d1ad → #84dfc3. Se corrigen los textos de resúmenes en oscuro para conservar contraste sobre menta intensa. Otros fondos azules, plomos y colores de acciones se mantienen. npm run build aprobado; recorrido de Cotizaciones con 37 comprobaciones/25 capturas aprobado. Cambio limitado a CSS, sin pruebas funcionales repetidas ni cambios MySQL. Respaldo y log en storage/app/private/menta-intensa-2026-11-15.


## Crear pedido — 15/11/2026

Vista organizada en dos columnas desde 1200 px: cliente y entrega a la izquierda; cotización, total acordado y confirmación a la derecha. En pantallas menores las secciones se apilan. Los datos de cliente nuevo se agrupan en un recuadro con campos cortos en columnas; seleccionar un cliente existente mantiene el comportamiento de ocultar y desactivar ese bloque. Resumen compacto con las tres columnas visibles y total en menta intensa. Se conserva la cabecera refinada, iconos, botones redondeados y Cancelar plomo. Sin cambios de campos, rutas, cálculos, validaciones, permisos, controladores ni servicios. El parcial pedidos/_datos permanece intacto.


## Pedidos: listado, consulta y edición — 15/11/2026

Cabeceras refinadas consistentes con Crear pedido. Listado de ancho ajustado al contenido, nombres de clientes destacados y filtros con espacio para Buscar/Limpiar en la misma fila de escritorio. Consulta con fichas de Datos e Importe en columnas, campos con iconos, total en menta intensa, muebles en tabla separada y seguimiento vertical con fechas y responsables. Edición limitada a un panel de ancho moderado, cabecera verde y botones a la izquierda. Las secciones existentes de pagos, materiales utilizados, cierre y casos permanecen con sus mismas condiciones de autorización. Sin cambios de estados, rutas, campos, permisos, consultas o cálculos.


## Registrar materiales utilizados — 15/11/2026

Cabecera refinada y contenido de ancho moderado. Resumen de pedido e indicaciones en dos columnas de escritorio, panel de copia alineado a la izquierda y recuadros por mueble con encabezado verde. Cada material conserva sus campos y observación opcional; Utilizado total se destaca como entrada y Planificado/Diferencia permanecen de consulta. Las filas se reorganizan en móvil sin desbordamiento. Motivo y acciones finales en panel separado de ancho limitado. Las cantidades iniciales y copiadas eliminan ceros decimales finales mediante Decimal::entrada, sin redondear ni modificar valores persistidos. Se mantienen permisos, validaciones, selectores JS y retorno contextual al pedido.


15/11/2026: por solicitud del usuario se retira Copiar cantidades planificadas de Registrar materiales utilizados. Se elimina el aviso de copia y su código JavaScript/CSS. El personal introduce manualmente lo utilizado; Planificado permanece como referencia y Diferencia conserva su cálculo. Esta decisión sustituye la disponibilidad del botón descrita antes.


## Registrar cambio del pedido — 15/11/2026

Acceso desde Detalle del pedido durante fabricación. Cabecera refinada, panel de ancho moderado, selección de mueble, valores original/vigente en dos columnas, motivo y revisión de costos opcional. Referencias por material agrupadas, mano de obra/otros costos en columnas. Quien aprueba precios ve el nuevo precio opcional del grupo y la confirmación del cliente. Vista previa exacta con centavos enteros del nuevo total y saldo (solo si tiene acceso a pagos). Cancelar y guardar vuelven al detalle; los errores conservan old() y el servidor revalida todo. Historial dentro del pedido, sin menú nuevo. Comprobante A4 de precio anterior/nuevo, total y contactos del negocio, sin costos, márgenes ni firma inventada. Capturas en docs/capturas/cambios-pedido-2026-11-15.


## Editar muebles y precios del mismo pedido — 15/11/2026

Botón desde Detalle del pedido y desde Editar pedido; el antiguo formulario de coordinación conserva sus campos y rutas. Se reutiliza el formulario Blade de cotizaciones, contextualizado como Editar pedido, con cantidades totales, recálculo, otros muebles y costos. Características/selección de materiales existentes de solo lectura y botones de quitar ocultos para filas del pedido; nuevos muebles mantienen edición y búsqueda de casos. El servidor también valida pertenencia y conservación de referencias. Cancelar vuelve al pedido.

Guardar revisión redirige a su ficha; precio sugerido usa el porcentaje configurado, sin valor arbitrario. Tras aprobar, se muestra total anterior/nuevo y el formulario de motivo/aceptación del cliente. Aplicar vuelve al mismo pedido y sustituye el total, recalculando saldo. Historial muestra cantidades anteriores/nuevas, precio y mueble agregado con acceso a revisión/original; evita presentar un subtotal parcial de materiales utilizados como el presupuesto completo. Ver cotización abre la versión vigente. Capturas en docs/capturas/revision-pedido-2026-11-15.


## Pagos, Casos históricos y Planificación — 16/11/2026

Se extiende el estilo de los módulos ya revisados a las nueve pantallas existentes de estos tres módulos:

- Pagos: listado compacto, filtros alineados, crear/editar con ancho moderado y ficha Ver con importe destacado, datos en dos columnas, saldo y responsables.
- Casos: listado al contenido; características y costos en columnas, tabla independiente de materiales y panel de revisión de ancho limitado. Mantener las restricciones de costos y acciones de validación.
- Planificación: listado compacto, resumen de necesidades y tarjetas por mueble; Material/Unidad se agrupan en una celda conservando ambos valores. Formularios con filas de material encuadradas, iconos, motivo y acciones proporcionadas al contenido.

Cabeceras refinadas, paleta existente, acciones adaptables, lectura en móvil y tema oscuro. Las entradas numéricas de importe y cantidad eliminan ceros sobrantes mediante Decimal::entrada. No se modifican campos, cálculos, servicios, controladores, rutas, permisos ni base de datos. Se conservan las confirmaciones SweetAlert2 y navegación contextual.

Verificación: 41 pruebas funcionales / 323 aserciones; 40 grupos de comprobación de navegador y 36 capturas a 1440, 1024 y 390 px, temas claro/oscuro, sin errores JavaScript. Se probó añadir/quitar material y guardar planificación, editar un pago con confirmación y recalcular su saldo, y el acceso del maestro a casos/planificación con Pagos protegido. Datos de escritura exclusivamente en SQLite temporal; no se ejecutan migraciones ni seeders en MySQL. Recursos compilados con npm run build. Evidencias y copias previas en storage/app/private/vistas-pagos-casos-planificacion-2026-11-16.


## Ver cliente: pedidos asociados — 16/11/2026

Bajo Información del cliente se incorpora la tarjeta Pedidos del cliente, con el mismo encabezado/icono y tabla de ancho al contenido. Muestra ID, fecha, entrega prevista, estado compartido y botón Ver; conserva la columna de responsables a la derecha. Orden descendente y ocho filas por página, con ancla pedidos-cliente y mensaje vacío. Visible solo con autorización de consultar pedidos, comprobada antes de ejecutar la consulta. Sin importes administrativos añadidos, nuevas rutas ni cambios de esquema. Las pruebas de filtrado, cliente vacío, permisos y paginación se integran en PedidosPlanificacionTest. Evidencias en storage/app/private/pedidos-en-cliente-2026-11-16.
