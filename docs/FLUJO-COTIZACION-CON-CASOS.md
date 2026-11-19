# Cotizar con casos históricos

Actualizado el 9 de noviembre de 2026. El usuario solicita una búsqueda única y retirar el modo separado de demostración. La recomendación principal sigue siendo **el caso más similar**, sin promedio de vecinos.

## Uso

1. Abrir Nueva cotización o Editar y describir el mueble: tipo, material principal, cantidad, medidas, acabado y componentes.
2. Pulsar **Buscar casos similares** sin guardar y sin llenar materiales/costos.
3. Consultar hasta tres antecedentes que superen el umbral. El primero es la **Recomendación principal**; si hay uno o dos, no se inventan más.
4. Pulsar **Usar propuesta principal** o **Usar este caso**, confirmar el reemplazo y editar materiales, mano de obra y otros costos para la nueva cotización.
5. Guardar con las validaciones habituales; el propietario sigue aprobando el precio final.

Sin casos suficientes se ofrece el llenado manual. También se puede elegir Continuar manualmente. Los campos se despliegan cuando se necesitan; errores de validación abren las secciones correspondientes. Ver el antecedente abre otra pestaña para conservar el formulario.

## Consulta y adaptación

Mismo tipo, hasta tres, similitud exacta >=80 % antes de redondear. Pesos: dimensiones 15 % cada una, material 25 %, acabado 10 %, puertas/cajones/divisiones/cantidad 5 % cada uno. Rangos fijos por tipo, sin extrapolación. El perfil conserva los mismos 35 descriptores y rangos, sin nueva calibración.

Una sola búsqueda sobre Casos históricos, sin modo normal/demostración ni selección por entorno. Se mantienen la exclusión de descartados, motivos de no elegibilidad, costos desconocidos, unidades incompatibles y referencias incompletas. Los pendientes siguen pendientes: utilizar sus estimaciones no inventa una revisión.

La propuesta divide las cantidades del grupo histórico por su número de muebles, redondea hacia arriba según unidad (entero o cuatro decimales) y el formulario multiplica por la cantidad solicitada. Mano de obra por mueble con dos decimales; otros costos conservados como total fijo del detalle, sin multiplicarlos nuevamente. Cantidades editables y ajustes conjuntos justificados.

Materiales a precios actuales del catálogo; desconocidos/no positivos quedan pendientes, nunca conocidos a costo cero. Histórico y precio histórico se muestran para su grupo original. Precio nuevo no se aprueba ni se copia del antecedente; el propietario conserva cálculo y aprobación habituales. No se copian márgenes/recargos. Material inactivo, principal distinto o unidad incompatible requiere revisión manual.

Sin despiece ni ajuste de consumo por dimensiones. El usuario revisa diferencias y sobrantes. Cambiar características invalida resultados y descarta respuestas atrasadas. Confirmación evita sobrescribir el llenado manual por accidente. Datos de la nueva cotización editables; antecedentes originales inmutables.

## Nombres y archivos

| Elemento | Estado actual |
|---|---|
| SimilitudController | show y buscar sin parámetro modo; rutas existentes conservadas. |
| RecuperacionCasosService | buscar/buscarFormulario comparten comparación, sin ramificaciones de modo/origen. |
| PropuestaCasoService | Principal/alternativas y procedencia verificable sin campo modo. |
| config/similitud.php | Un único perfil; retiradas configuraciones de modos y flag incluir_simulados. |
| config/perfiles/similitud-casos-historicos-v1.json | Sustituye similitud-demostracion-v1.json; versión casos-historicos-v1. |
| admin/cotizaciones y admin/similitud | Un solo botón Buscar casos similares y tarjetas compartidas. |
| admin/casos | Nombre Casos históricos; procedencia Registro inicial o Desde pedido. |
| CasosHistoricosSeeder / CasoHistoricoFactory | Generadores por módulo, fuentes registro_inicial/estimacion_inicial y carga idempotente. |
| docs/CATALOGO-CASOS-HISTORICOS.md | Sustituye CASOS-DEMOSTRACION-FASE-5.md. |
| resources/js/cotizaciones.js y public/build | Solicitud sin selector de modo, manifest/compilación actualizados. |

Perfil SHA-256: **46ae9298baddd8e577b9647bf1d743ed1a9179b654a293d2f6fe54f56e2d8c7c**. El cambio de archivo/versión no cambia los pesos, rangos o descriptores. Contribuyentes descartados/modificados o hash incompatible invalidan el perfil sin reajuste silencioso.

## Conservación y alcance

Los 35 registros iniciales fueron generados; conservan internamente su origen simulado y revisión (34 pendientes y uno revisado en la base verificada). No se convierten en trabajos realizados ni revisiones humanas. No hay módulo separado: todos son antecedentes del módulo Casos históricos y el origen permanece como trazabilidad de datos.

Se mantienen los valores históricos del lote, checksum y auditorías que ya existían. Dos referencias de compatibilidad aún contienen la denominación antigua del lote en seeder/perfil; se conservan porque identifican registros ya cargados y evitan duplicación. No se renombraron dentro de MySQL ni se borró evidencia histórica. El JSON original de los 35 casos conserva su checksum; no se alteró retrospectivamente. El seeder reconoce auditoría antigua por lote/checksum y crea futuras auditorías con el nombre casos_historicos_cargados.

Cuenta activa y permisos cotizaciones.consultar, casos.consultar y costos.consultar, además de crear/modificar según contexto. Edición aprobada bloqueada. Se rechazan modo y otros parámetros externos que ya no pertenecen al contrato. CSRF se conserva. Referencia firmada acredita procedencia, nunca autoriza precios o importes: servidor valida y recalcula, con auditoría en la misma transacción al guardar.

MySQL **carpinteria_el_tajibo: 29 tablas/25 migraciones/51 FK**. Comparación íntegra antes/después: idéntica, cero escrituras operativas. Ninguna migración o recarga de semillas; sin respaldo/restauración nuevos porque no hubo actualización de base. Copias previas de archivos y evidencias en storage/app/private/unificacion-casos-2026-11-09. El sistema escolar no se modificó.

Esta actualización no acredita precisión comercial ni rendimiento productivo. No se implementan pagos, nuevos módulos, ajustes geométricos o descuentos de existencias.

## Verificación

- Suite completa final: **243 pruebas/2347 aserciones, 304,17 s**, aprobadas; incluye la prueba de compatibilidad del lote/auditoría anterior.
- Primera comprobación dirigida: **41 pruebas/215 aserciones, 109,06 s**, aprobadas. Después se añadió una regresión específica de compatibilidad de auditoría/lote antiguo; resultados finales en [verificación vigente](VERIFICACION.md).
- Edge/Playwright: **107 comprobaciones**, aprobadas, sin errores JavaScript; SQLite temporal y 86 capturas del recorrido acumulado.
- Vite build aprobado con dependencias actuales, sin nuevas instalaciones.
- MySQL: servicio sobre 27 detalles, 12 con referencias, siete fuera de cobertura y ocho sin antecedentes. Propietario/maestro: ficha renderizada, dos resultados y dos propuestas en formulario para cada rol, sin escrituras ni sesión HTTP MySQL. No son métricas de exactitud.

[Resultado de navegador](capturas/casos-historicos-unificados/resultado-browser.json), [formulario escritorio](capturas/casos-historicos-unificados/formulario-similitud-1440.png), [móvil](capturas/casos-historicos-unificados/formulario-similitud-390.png), [oscuro](capturas/casos-historicos-unificados/formulario-similitud-oscuro-390.png) y [propuesta editable](capturas/casos-historicos-unificados/formulario-propuesta-editable-390.png).

## Comparación completada en 5C — 10/11/2026

Las propuestas muestran ahora Con costos del caso y Con precios actuales para la misma cantidad solicitada, sin alterar los importes originales. Aviso explícito si cambia el tamaño del lote; los ahorros conjuntos requieren revisión. El precio actual solo actualiza materiales, y la aprobación del propietario se conserva. [Entrega y ejemplo de cálculo](FASE-5C.md).
