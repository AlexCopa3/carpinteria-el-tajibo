# Arquitectura vigente

> Estado vigente de permisos, 13/11/2026: 31 tablas, 27 migraciones, 55 FK y 48 permisos. Delegación completa por rol y excepciones individuales con prioridad. [Aplicación y verificaciones](INFORME-PERMISOS-GENERALES-INDIVIDUALES.md). Los cortes previos que aparecen debajo se conservan como antecedentes.

> Estado posterior vigente (9/11/2026): Casos históricos usa una sola búsqueda, sin selector o modo separado. Perfil único config/perfiles/similitud-casos-historicos-v1.json. Los registros conservan su procedencia y revisiones. Véase [flujo actualizado](FLUJO-COTIZACION-CON-CASOS.md); esta decisión sustituye las instrucciones anteriores de seleccionar demostración.

Referencia actualizada tras la entrega 6B del 10 de noviembre de 2026. El sistema es independiente de la referencia escolar.

## Stack y organización

Laravel 12.70.2, PHP ^8.3 (pruebas con 8.3.28), Blade, JeroenNoten/AdminLTE 3.15.3, Bootstrap local 4.6.1, Spatie Permission 6.20.0, Laravel UI 4.6.3, Dompdf y Brick Math. Las versiones fijadas pertenecen a composer.lock/package-lock.json; no se actualizan por esta documentación.

Controladores CRUD por entidad, modelos Eloquent, políticas, Form Requests para estructuras complejas y servicios. Rutas explícitas /admin con nombres admin.*; vistas admin/<modulo>. Los overrides y recursos propios se mantienen fuera de vendor.

## Servicios activos

| Servicio | Responsabilidad |
|---|---|
| PermisosUsuarioService | Excepciones permitir/denegar por persona, precedencia sobre el rol, bloqueo y auditoría transaccional. |
| ReporteService | Filtros validados, acceso por permisos delegables, consultas de solo lectura y totales exactos para pantalla/PDF. |
| CatalogoService | Materiales, categoría/unidad, costo de referencia y cambios históricos. |
| ExistenciaMaterialService | Agregar/corregir saldos con motivo, transacción y prevención de duplicados. |
| CotizacionService | Detalles, cantidades, costos, recargo y aprobación inmutable. |
| CotizacionPdfService | PDF con precios aprobados e información comercial permitida. |
| PedidoService | Aceptación única, precios acordados y comienzo de fabricación. |
| PlanificacionMaterialService | Cantidades planificadas y disponibilidad informativa por pedido. |
| FabricacionService | Cantidades utilizadas por detalle, corrección con motivo, versión y auditoría; sin descontar saldos. |
| CierrePedidoService | Validación final de materiales, invalidación, ratificación de precio, término y entrega con bloqueos/idempotencia. |
| RetencionCasoService | Instantánea por detalle al terminar y conciliación idempotente, conservando cantidades y fuentes de costos. |
| RevisionCasoService | Revisión de materiales/precio y descarte auditados con permisos y versión. |
| ComparadorCasosService | Diferencias mixtas, rangos fijos y similitud racional exacta. |
| RecuperacionCasosService | Perfil versionado, elegibilidad, autorización y referencias sin escrituras. |
| PagoService | Registro/edición, saldos exactos, bloqueos actuales, reenvíos y auditoría; autorización delegable por permisos. |
| UsuarioService | Cuenta, ficha personal y protección del acceso de propietarios. |
| PermisosRolService | Catálogo completo editable por rol, validación y auditoría. |
| AuditoriaService | Responsables y valores anteriores/nuevos sin secretos. |
| EliminacionService | Borrado de catálogo condicionado por dependencias. |

## Infraestructura y límites

Idioma es, zona America/La_Paz y BOB/Bs fijo. Sesiones/caché file, colas sync. Los importes y cantidades usan DECIMAL y cálculo exacto. Sin conversiones universales ni descuentos automáticos de existencias.

Las cotizaciones aprobadas y el acuerdo de pedidos quedan protegidos. Las transacciones conservan bloqueos, versión e idempotencia. El dashboard cuenta registros del sistema, incluidos los ejemplos autorizados, sin presentarlos como ventas reales.

La instalación local con migrate --seed incorpora ejemplos y solicita el primer propietario si falta; producción los excluye. No hay contraseñas predeterminadas. Ver [instalación](INSTALACION-CON-SEED.md) y [procedencia de ejemplos](CARGA-DATOS.md).

## Alcance

Fases 1–3, 4A–4D y 5A–5B implementadas. Aceptado → En fabricación → Terminado → Entregado, con materiales revisados y precio acordado ratificado por propietario. Casos históricos/materiales, retención y revisión están implementados; 35 ejemplos simulados (34 pendientes y uno revisado en el cierre). Recuperación habilitada con un único perfil fijo de Casos históricos, sin selector de modos. Los casos conservan origen/revisión y no se recalibran rangos automáticamente. 5C y pagos 6A implementados. 6B implementa reportes de cotizaciones, pedidos, pagos y saldos con PDF. Captura de gastos pendiente. [Entrega 5B](FASE-5B.md).

MySQL fue comprobado y actualizado con respaldo restaurado y ensayo el 9 de noviembre: 25 migraciones y 29 tablas. [5A](FASE-5A.md) añade casos_historicos/materiales_caso; la entrega [4D](FASE-4D.md) añade cuatro metadatos nullable a pedidos; [4C](FASE-4C.md) conserva los campos de uso. Las 23 migraciones anteriores a 5A permanecen intactas. Véase [diccionario](DICCIONARIO-BD-ACTUAL-2026-11-05.md) e [informe general](INFORME-GENERAL-2026-11-05.md).

## Integración posterior autorizada

La búsqueda también funciona durante registro/edición, antes de guardar, mediante SimilitudController.buscar y RecuperacionCasosService.buscarFormulario. PropuestaCasoService prepara alternativas (mejor vecino como principal), cálculo por unidad y procedencia verificable; CotizacionService conserva el guardado transaccional. Sin entidades/dependencias nuevas. [Flujo](FLUJO-COTIZACION-CON-CASOS.md).

## Cierre 5C

PropuestaCasoService agrega comparación histórica adaptada mediante racionales exactos, conservando el costo actual existente. Sin nuevas entidades, rutas o dependencias. [Entrega](FASE-5C.md).


## Pagos 6A aplicado

Pago/PagoPolicy/GuardarPagoRequest/PagoService y controlador CRUD explícito. Seis rutas /admin/pagos con admin.pagos.*, vistas por operación. Una tabla nueva: 30 tablas/26 migraciones/53 FK. Saldos calculados; permisos existentes y bloqueo del maestro. [Entrega](FASE-6A.md).

Reportes conserva rutas explícitas /admin y vistas admin/reportes; ReporteController usa index/show/pdf y Dompdf existente. Sin entidad Reporte persistida: se consultan registros vigentes con permisos del origen. [Alcance y límites](FASE-6B.md).

## Permisos vigentes — 13/11/2026

Implementación aplicada y verificada el 13 de noviembre de 2026, tras aprobación del usuario, respaldo completo restaurado y ensayo aislado. Permisos generales editables para todos los módulos; excepciones individuales Según su rol / Permitir / Denegar con prioridad sobre Spatie. El propietario conserva las cuatro capacidades de recuperación documentadas. Base carpinteria_el_tajibo: 31 tablas, 27 migraciones, 55 FK y 48 permisos; propietario 48 y maestro sus mismos 15. Sin delegaciones automáticas al maestro, cambios de contraseñas ni pérdidas de registros. Informe: docs/INFORME-PERMISOS-GENERALES-INDIVIDUALES.md. Esta decisión sustituye las restricciones históricas por rol; conservar las validaciones del negocio y no avanzar a fases nuevas. User::hasPermissionTo consulta las excepciones de permisos_usuario antes del resultado normal de Spatie. Gate y Acceso comparten ese resultado; las políticas conservan las condiciones de negocio. No se modifica vendor ni se introduce una arquitectura adicional.
