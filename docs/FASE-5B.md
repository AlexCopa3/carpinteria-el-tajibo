# Entrega 5B — Recuperación de casos similares

> Actualización posterior autorizada: buscar desde el formulario antes de guardar y elegir el vecino más similar como principal o una alternativa editable. Véase [flujo de cotización con casos](FLUJO-COTIZACION-CON-CASOS.md). Los límites de consulta sola de la entrega original quedan sustituidos únicamente para esta integración.

9 de noviembre de 2026. Implementación autorizada tras aprobar el [diseño 5B](PROPUESTA-FASE-5B.md). Consulta de antecedentes únicamente; 5C no implementada.

## Uso y alcance

En **Cotizaciones → Ver**, cada mueble guardado tiene **Buscar casos similares**. Se reutilizan sus características, sin volver a llenar materiales o dimensiones. Se muestran hasta tres antecedentes del mismo tipo, porcentaje/diferencias, materiales/cantidades del grupo original, costos estimados y precio histórico. No multiplica, adapta ni copia estos datos a la cotización. El precio aprobado y los permisos del propietario se conservan.

El modo normal admite exclusivamente casos reales revisados/completos con perfil real aprobado. Actualmente hay **35 casos simulados/172 líneas de materiales**, 34 pendientes y uno revisado; no existen casos reales ni perfil normal. El modo normal informa que faltan antecedentes. **Ver demostración experimental**, elegido expresamente, consulta simulados completos en local/testing; se bloquea en producción aunque se fuerce la URL. Revisar un simulado conserva su origen. Se preservó la revisión preexistente.

## Cálculo y perfil

| Característica | Peso |
|---|---:|
| Largo, ancho y alto | 15 % cada uno |
| Material principal | 25 % |
| Acabado | 10 % |
| Puertas, cajones, divisiones y cantidad de muebles | 5 % cada uno |

Diferencias numéricas normalizadas y categorías por igualdad, mediante distancia ponderada inspirada en Gower. Similitud = 100 × (1 − distancia). Comparación/corte con racionales exactos de Brick Math; presentación redondeada a dos decimales. **k=3, similitud >=80 % antes de redondear**: 79,9999 % queda excluido aunque visualmente redondee a 80,00 %. Empates por ID ascendente. Costos/precios no intervienen en el orden.

Rangos numéricos congelados por tipo; fuera de cobertura no se extrapola. Rango constante: igualdad tiene diferencia cero, desigualdad uno. No se completan ausentes con cero, promedios o valores actuales.

Perfil **demostracion-5b-v1**, config/perfiles/similitud-demostracion-v1.json. SHA-256: 58a833375f709889002b84ee914a3e691159366adad6782282084e867f1be8d6.

Los 35 descriptores se identifican por lote/posición, código de tipo y alias de material, resueltos a IDs locales; no se exigen los IDs de esta computadora. Se comprueban hash, configuración, referencias/rangos y se obtiene una huella adicional con los vínculos actuales. Descartar un contribuyente, cambiar sus descriptores o hacer incompatibles material/unidad/costos invalida el perfil, sin recalibración silenciosa. Solo pasar pendiente a revisado no lo invalida. Renombrar el material principal puede exigir revisar el vínculo semántico. Nuevos casos no amplían rangos.

Se verifica coherencia de cantidades, unidades capturadas/catálogo, costos unitarios/subtotales y total. Desconocidos permanecen NULL y antecedentes incompletos se excluyen. Material histórico inactivo se identifica conservando valores. Importes del grupo histórico completo, costos etiquetados **estimados**, sin presentarlos como gastos reales.

Parámetros piloto aprobados para datos sintéticos: no son probabilidad de acierto, precisión comercial medida, predictor entrenado de precios ni validación humana. Perfil real y evaluación con datos revisados del negocio pendientes.

## Archivos y autorización

| Archivo o grupo | Cambio |
|---|---|
| app/Http/Controllers/SimilitudController.php | show explícito, validación/autorización y pertenencia del detalle. |
| app/Services/ComparadorCasosService.php | Comparación exacta y cobertura. |
| app/Services/RecuperacionCasosService.php | Perfil fijo, selección elegible y separación de origen. |
| config/similitud.php y config/perfiles/similitud-demostracion-v1.json | Consulta activa y perfil versionado; perfil normal NULL. |
| routes/web.php | GET admin.similitud.show; 80 entradas totales, conservando las 79 anteriores. |
| resources/views/admin/similitud/show.blade.php | Referencias/diferencias plegables, escritorio/móvil y claro/oscuro. |
| resources/views/admin/cotizaciones/show.blade.php | Enlace autorizado por detalle. |
| resources/views/admin/casos/index.blade.php y show.blade.php | Orientación actualizada. |
| tests/Feature/SimilitudTest.php y ComparadorSimilitudTest.php | Permisos, perfiles, exactitud y solo lectura. |
| tests/Feature/PhaseOneTest.php | Expectativas de configuración del piloto aprobado. |
| tests/browser/preparar-casos.php y fase4b.cjs | Fixtures aislados y recorrido experimental. |

Cuenta activa y cotizaciones.consultar, casos.consultar, costos.consultar; comprobación también en servicio. Detalle ajeno: 404; modo inválido o parámetros no admitidos: 422; demostración productiva: 403. Sin permisos/asignaciones nuevos. Ambos roles consultan, sin acciones de aprobación o margen.

Se conservan rutas /admin, admin.*, controladores por operación y Blade por módulo. Request->validate en controlador para un único selector sencillo; comparación/recuperación en servicios. Sin dependencias nuevas, edición de vendor o compilación adicional: Brick Math y recursos visuales existentes.

## MySQL, conservación y respaldos

Base confirmada: **carpinteria_el_tajibo**, **29 tablas/25 migraciones/51 FK**. Sin nuevas tablas/columnas, migraciones, seeders o escrituras. Se preservaron usuarios/contraseñas, roles/permisos, catálogo, saldos/movimientos, casos/revisiones, cotizaciones/pedidos y auditorías; las búsquedas no se registran como auditorías.

Diagnóstico completo antes/después de todas las tablas: datos y estructura idénticos. Hashes de las 25 migraciones conservados. Al no haber escrituras MySQL, no se necesitó un nuevo respaldo SQL ni ensayo de actualización de esquema; el respaldo restaurado de 5A es evidencia de aquella entrega. Copia previa de archivos editados: storage/app/private/fase5b-2026-11-09/implementacion/codigo-anterior.

## Pruebas ejecutadas

| Verificación | Resultado |
|---|---|
| Suite PHP completa, SQLite aislado | **232 pruebas/2275 aserciones, 294,63 s**, aprobadas. |
| Repetición específica tras último ajuste Blade | **21 pruebas/86 aserciones, 46,83 s**, aprobadas. |
| Edge/Playwright, SQLite temporal | **102 comprobaciones**, aprobadas, sin errores JS; 1440/1024/390 px y oscuro. |
| Servicio sobre MySQL operativo | **27 detalles existentes**: 7 fuera de cobertura, 12 con referencias y 8 sin antecedentes en demostración. |
| Controlador/Blade sobre MySQL | Cuatro: propietario/maestro × normal/demostración; normal sin referencias, demostración con dos. |
| Integridad MySQL y migraciones | Datos/estructura idénticos al diagnóstico inicial; cero escrituras. |

Las 27 consultas por detalle no son 27 sentencias SQL ni una métrica de exactitud. Los cuatro renderizados MySQL invocaron servicio/controlador/Blade con roles existentes, **sin autenticación HTTP real**. El navegador recorrió la aplicación con SQLite temporal aislado. Fixtures que representan reales revisados prueban reglas de origen/revisión, sin acreditar trabajos reales o revisión humana.

Se probaron corte previo al redondeo, identidad/simetría, constantes, pesos, categorías no ordinales, límites fijos, desconocidos, cambios de perfil, descartes, revisión sin recalibración, empates/máximo tres, precio ajeno al ranking, permisos, cuenta inactiva, URL manipulada, prohibición productiva y comparación íntegra de datos sin escrituras.

Evidencias: [resultado de navegador](capturas/fase5b/resultado-browser.json); logs/JSON en storage/app/private/fase5b-2026-11-09/implementacion: pruebas-php.log, pruebas-finales.log, pruebas-navegador.log, diagnostico-inicial.json, migraciones-previas.json y verificacion-mysql.json. El escenario guarda 80 capturas del recorrido acumulado, seis específicas de similitud.

## Capturas

- [Resultados escritorio](capturas/fase5b/similitud-resultados-1440.png).
- [Resultados 1024](capturas/fase5b/similitud-resultados-1024.png).
- [Resultados móvil y diferencias](capturas/fase5b/similitud-resultados-390.png).
- [Tema oscuro móvil](capturas/fase5b/similitud-oscuro-390.png).
- [Fuera de cobertura](capturas/fase5b/similitud-sin-cobertura-390.png).
- [Consulta del maestro](capturas/fase5b/similitud-maestro-390.png).

## Límite

5B implementada para consulta experimental. Sin aplicación/autollenado a cotizaciones, recomendaciones duales de costos, revisión comercial, pagos, escalamiento geométrico o descuentos de existencias. **5C requiere autorización posterior.**
