# Diseño de datos vigente

> Estado vigente de permisos, 13/11/2026: 31 tablas, 27 migraciones, 55 FK y 48 permisos. Delegación completa por rol y excepciones individuales con prioridad. [Aplicación y verificaciones](INFORME-PERMISOS-GENERALES-INDIVIDUALES.md). Los cortes previos que aparecen debajo se conservan como antecedentes.

> Estado posterior vigente (9/11/2026): Casos históricos usa una sola búsqueda, sin selector o modo separado. Perfil único config/perfiles/similitud-casos-historicos-v1.json. Los registros conservan su procedencia y revisiones. Véase [flujo actualizado](FLUJO-COTIZACION-CON-CASOS.md); esta decisión sustituye las instrucciones anteriores de seleccionar demostración.

Mapa consolidado el 9 de noviembre de 2026. La actualización [5A del 9 de noviembre](FASE-5A.md) verifica 29 tablas y 25 migraciones alineadas, con 51 FK. El [corte del 5 de noviembre](INFORME-GENERAL-2026-11-05.md) se conserva como inventario histórico previo.

| Área | Tablas |
|---|---|
| Cuenta y contacto | users, personal, password_reset_tokens, sessions |
| Spatie | roles, permissions, model_has_roles, model_has_permissions, role_has_permissions |
| Catálogo y clientes | categorias, clientes, unidades_materiales, materiales, tipos_mueble |
| Existencias/historial | existencias_materiales, movimientos_materiales, cambios_materiales |
| Cotizaciones | cotizaciones, detalles_cotizacion, materiales_cotizacion |
| Pedidos y planificación | pedidos, detalles_pedido, materiales_pedido, estados_pedido |
| Casos históricos | casos_historicos, materiales_caso |
| Configuración/auditoría | ajustes, auditorias |
| Control de esquema | migrations |

## Relaciones

users tiene personal mediante user_id único; roles/permisos utilizan los pivotes de Spatie. Categorías y unidades clasifican materiales; cada material puede tener un saldo y múltiples movimientos/cambios.

Cotización → detalles → materiales cotizados. La relación histórica con cliente admite NULL: las nuevas cotizaciones no lo solicitan. Pedido tiene cotizacion_id único, cliente, instantánea de contacto, detalles y eventos de estado. Cada detalle de pedido enlaza su detalle cotizado y materiales planificados.

materiales_pedido conserva cantidad_inicial y cantidad_planificada. 4C añade cantidad_utilizada DECIMAL(18,4) NULL, observaciones_uso TEXT NULL, uso_registrado_por_id FK nullable restrictiva a users y uso_registrado_at TIMESTAMP NULL. NULL significa pendiente; cero explícito, no utilizado. CHECK MySQL impide negativos. El actor/fecha se generan en servidor y las correcciones conservan antes/después en auditorías. Las 31 filas anteriores quedaron NULL, sin atribuirles consumo real. La comparación con existencias sigue siendo informativa, sin reserva ni descuento.

4D añade a pedidos materiales_validados_por_id, materiales_validados_at, precio_confirmado_por_id y precio_confirmado_at, todos nullable; las dos referencias a users son restrictivas. Los 11 pedidos anteriores conservaron NULL. Cambiar materiales revisados invalida su validación operativa y conserva auditoría. Término/entrega usan estados_pedido y ocurrido_at, sin duplicar fechas ni tablas. Precio acordado/cotización se conservan; no se capturan costos reales.

5A retiene un caso por detalle_pedido_id único al terminar con validaciones y conserva sus materiales en materiales_caso. Lote/posición únicos identifican internamente demostraciones; cada línea tiene posición única por caso y referencias restrictivas a material/unidad/fila de pedido. Características son las acordadas, cantidades las registradas; costos son estimaciones de referencia y precio es el acuerdo del grupo. Cabecera/líneas permanecen protegidas; solo se revisan metadatos mediante permisos y auditoría. Costo desconocido NULL y motivo de inelegibilidad; nunca convertir simulados en reales.

## Reglas de conservación

Cantidades/importes con DECIMAL; cantidades por unidad y totales nominales/aplicados preservados. Otros costos son totales por detalle. El precio aprobado por detalle y su suma quedan protegidos; el pedido copia el acuerdo sin multiplicarlo de nuevo. Los UUID son controles internos de repetición, no códigos visibles alternativos.

Las FK restrictivas y servicios protegen relaciones. Movimientos y estados son inmutables. La auditoría polimórfica y las instantáneas JSON no se convierten en FK inexistentes. No borrar tablas o columnas por tener cero filas.

Persisten columnas de compatibilidad: materiales.categoria copia texto, stock_minimo no se solicita y se prohíbe en el controlador, y movimientos_materiales conserva campos históricos de costo. CambiosMaterial sigue siendo escrito por CatalogoService. Ninguno se elimina en esta limpieza de archivos.

## Migraciones

Una tabla de negocio por archivo; agrupaciones de Laravel/Spatie conservadas. La migración allow_cotizaciones_without_cliente modifica una columna y no crea tabla. 4C añade una migración aditiva de cuatro columnas sin crear otra tabla; 4D añade cuatro metadatos a pedidos; 5A añade dos creaciones individuales; por eso 25 migraciones no significa 25 tablas. No se modifica el historial aplicado ni se eliminan archivos de migración.

No existen las tablas presentaciones_compra, operaciones_inventario, entradas_materiales, movimientos_inventario ni configuraciones_cotizacion en el corte actual. Cache/colas usan file/sync; las tablas técnicas de autenticación pueden permanecer vacías.

El [diccionario](DICCIONARIO-BD-ACTUAL-2026-11-05.md) contiene columnas, tipos, nulabilidad y FK observadas. El [informe general](INFORME-GENERAL-2026-11-05.md) mapea las 21 migraciones y cantidades del corte, incluidos datos ficticios. Las entregas [4C](FASE-4C.md), [4D](FASE-4D.md) y [5A](FASE-5A.md) documentan las ampliaciones posteriores, sus respaldos y ensayos. Otros cambios de MySQL requieren diseño, aprobación, respaldo restaurable y ensayo aislado.

## Perfil de similitud 5B

Sin nuevas entidades, migraciones o escrituras de búsqueda. config/perfiles/similitud-casos-historicos-v1.json conserva descriptores/rangos fijos, versión y referencias por lote/posición/código/alias; el servicio resuelve IDs locales y comprueba integridad. Sin log de consultas GET ni cambio de origen/revisión. Perfil único; procedencia conservada y candidatos completos pendientes/revisados del mismo tipo. Desconocidos no se convierten a cero; históricos sin escalamiento. [Detalle](FASE-5B.md).

## Procedencia de propuesta aplicada

Sin cambios de esquema. referencia_caso es entrada opcional firmada, no una columna nueva: el servidor verifica actor/autenticidad y registra referencia_caso_utilizada en auditorías existentes al guardar, dentro de la transacción. Consulta sin guardar no genera registros. Se conservan los casos históricos y los cálculos/validaciones existentes de la nueva cotización. [Flujo](FLUJO-COTIZACION-CON-CASOS.md).

## Referencia económica de 5C

El token de procedencia existente conserva cantidad original/solicitada y ambos totales iniciales consultados. Se registra en la misma auditoría de selección al guardar; los importes finales editados se calculan y guardan por el flujo habitual. Sin columnas o tablas nuevas. [Reglas](FASE-5C.md).

## Pagos — 6A

pagos pertenece a pedidos y users.registrado_por_id con dos FK restrictivas; importe DECIMAL(18,2), fecha, efectivo/qr, referencia/observaciones, UUID único/huella, versión y timestamps. Pedido tiene muchos pagos. Saldos derivados con suma decimal, sin columnas adicionales. Edición con motivo conserva anteriores/nuevos en auditorias; sin borrado o anulación. Actualizado: 30 tablas/26 migraciones/53 FK. [Esquema detallado y respaldo](FASE-6A.md).

## Reportes 6B

Sin nuevas tablas o migraciones. Consulta cotizaciones/pedidos/pagos y datos del negocio en ajustes. Totales calculados con decimales exactos; fecha de cotización, aceptación o pago según reporte. Saldo actual por pedido = precio acordado menos todos sus pagos vigentes, sin persistir saldos o generar cobros iniciales. Todos los registros, incluidos seeders, según decisión confirmada; no se cambia origen/revisión. [Entrega](FASE-6B.md).

## Excepciones individuales — 13/11/2026

Implementación aplicada y verificada el 13 de noviembre de 2026, tras aprobación del usuario, respaldo completo restaurado y ensayo aislado. Permisos generales editables para todos los módulos; excepciones individuales Según su rol / Permitir / Denegar con prioridad sobre Spatie. El propietario conserva las cuatro capacidades de recuperación documentadas. Base carpinteria_el_tajibo: 31 tablas, 27 migraciones, 55 FK y 48 permisos; propietario 48 y maestro sus mismos 15. Sin delegaciones automáticas al maestro, cambios de contraseñas ni pérdidas de registros. Informe: docs/INFORME-PERMISOS-GENERALES-INDIVIDUALES.md. Esta decisión sustituye las restricciones históricas por rol; conservar las validaciones del negocio y no avanzar a fases nuevas.

Suite final: 293 pruebas y 2920 aserciones aprobadas; 25 grupos de comprobación en Edge con datos temporales, sin errores JavaScript. Nueve comprobaciones MySQL en clon con escrituras revertidas, más renderizado local CLI de solo lectura. Las pruebas no acreditan precisión académica del recomendador ni validación humana.

permisos_usuario: id BIGINT UNSIGNED autoincremental; user_id/permission_id BIGINT UNSIGNED obligatorios; permitido BOOLEAN obligatorio; created_at/updated_at. FK a users y permissions con CASCADE de la excepción; UNIQUE(user_id, permission_id). User tiene hasMany permisosIndividuales; PermisoUsuario pertenece a usuario y permiso. Sin fila se mantiene Spatie; no se migran ni borran asignaciones directas.


## Catálogo de tipos de muebles — 14/11/2026

Administración sobre tipos_mueble existente (id, codigo, nombre varchar(80), activo, timestamps). Sin migraciones ni cambios SQL. El ID sigue siendo autoincremental. codigo (único, máximo 30) es una clave técnica interna, no un campo del formulario; las altas generan una clave tipo- seguida de 20 caracteres aleatorios, y las ediciones conservan la clave, usada por perfiles de similitud y antecedentes. TiposMuebleSeeder sigue firstOrCreate por sus seis códigos iniciales y no revierte nombres/estados editados. TipoMueble mantiene detalles hasMany y añade la relación polimórfica de auditoría, utilizando auditorias existente. El CRUD no reescribe nombres congelados de detalles aprobados, pedidos o casos; no elimina filas ni altera las relaciones existentes.


## Edición directa de la cotización del pedido — 16/11/2026

Decisión vigente aprobada: retirar completamente el mecanismo de cotización original, revisiones comerciales y cambios de pedido. Se edita la misma cotización y sus líneas actuales, conservando sus IDs. No crear otra cabecera ni archivar versiones de detalles; no mantener ciclos, mapas JSON, tablas de cambios ni referencias originales.

Editar muebles y precios abre el formulario mediante GET sin escribir datos. Guardar usa CotizacionService con transacción, bloqueo del pedido/cotización, permisos efectivos y comprobación de versión. Restablece la aprobación y recalcula costos y precio sugerido. Conservar el diseño de los muebles aceptados y permitir aumentar su cantidad y añadir muebles; no reducir muebles aceptados desde este flujo.

Registrar materiales utilizados sincroniza exactamente cantidades explícitas, incluidos aumentos, reducciones, cero y materiales nuevos, sin multiplicarlas nuevamente. Vacío sigue pendiente. Conservar costos unitarios anteriores; los nuevos usan una referencia positiva del catálogo como estimación. Costo desconocido permanece NULL y bloquea la aprobación; nunca se convierte automáticamente en gratuito. Si falta autorización de cotizaciones, guardar uso y advertir que alguien autorizado debe actualizar costos. Los errores de una sincronización autorizada revierten la operación completa.

El precio_total_acordado del pedido y precio_acordado de cada detalle contienen el acuerdo actual. Guardar o aprobar una cotización no modifica pagos ni saldo. Actualizar pedido exige aprobación de todos los detalles, aceptación del cliente, permisos de precios/planificación, versiones actuales y total no inferior a lo pagado. PedidoService::aceptarCotizacion actualiza el acuerdo y planificación, conservando consumo, autores y fechas. cotizacion_aceptada_version es un marcador técnico de aceptación, sin historial ni copias. Reenvío idéntico es idempotente.

Cierre exige la cotización actual aprobada y aceptada, importes/cantidades coherentes y materiales validados. Casos usan costos actuales de esa cotización y las cantidades utilizadas. Conservar auditoría general de responsables, permisos, pagos y existencias; no realizar descuentos automáticos de materiales por esta limpieza.

Se retiran las dos tablas de cambios, cinco columnas del mecanismo anterior y sus cinco migraciones de modificación/creación. La migración original de pedidos incorpora el marcador de aceptación para instalaciones nuevas. El usuario autoriza consolidar los datos iniciales y omitir respaldos adicionales en esta operación. Ensayar primero en MySQL aislado y verificar precios, pagos, saldos, integridad y datos de otros módulos. No modificar el sistema escolar.

