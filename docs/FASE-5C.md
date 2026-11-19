# Entrega 5C — Cotización asistida y comparación económica

10 de noviembre de 2026. Cierre del flujo de selección editable implementado anteriormente en [Cotizar con casos](FLUJO-COTIZACION-CON-CASOS.md), después de que el usuario solicitara continuar con el siguiente paso.

## Resultado

Al describir el mueble, Buscar casos similares muestra hasta tres antecedentes. Principal = vecino con mayor similitud; el usuario confirma una propuesta y edita sus materiales/costos antes de guardar. Cuando no hay antecedentes suficientes, sigue disponible el llenado manual. Casos históricos sigue siendo un único módulo, sin modos separados.

Se completa la comparación de costos que faltaba: **Con costos del caso** y **Con precios actuales**, ambas para la misma cantidad de muebles solicitada. Los importes originales del caso se conservan aparte para su grupo original. Así se evita comparar accidentalmente el presupuesto de un mueble con el de dos.

El segundo escenario actualiza únicamente precios de materiales. Mano de obra y otros costos siguen siendo estimaciones del caso, editables; no se presentan como gastos actuales comprobados. Ninguno de los dos importes es un precio final aprobado. Ganancia sobre costo y aprobación mantienen sus permisos y cálculo habitual del propietario.

## Reglas

- Conservar las mismas cantidades adaptadas en ambos escenarios: referencia por mueble, redondeo hacia arriba según unidad y multiplicación única por cantidad solicitada.
- Histórico adaptado: cantidades adaptadas × costos unitarios capturados, redondeando cada subtotal a dos decimales. Si un material tiene varias líneas con costos diferentes, utilizar costo ponderado por cantidad mediante racionales exactos, sin redondear previamente el costo medio.
- Actual: esas mismas cantidades × precios del catálogo, manteniendo el cálculo previo.
- Mano de obra: la misma estimación unitaria redondeada a dos decimales × muebles solicitados. Otros costos: el mismo total fijo, sin multiplicarlo.
- Un costo histórico desconocido deja pendiente el escenario histórico; un precio actual desconocido deja pendiente el actual. No se sustituye uno por el otro ni por cero.
- Si cambia el tamaño del lote, avisar que los ahorros de trabajo conjunto pueden cambiar. Sin campos obligatorios nuevos ni descuento automático.
- Sin escalamiento geométrico por dimensiones, mezcla de vecinos, nuevos parámetros de similitud o modificaciones de antecedentes.

Ejemplo de prueba: un caso de dos muebles utiliza cinco piezas (dos a Bs 10 y tres a Bs 20), mano de obra Bs 101 y otros Bs 7. Para tres muebles, la regla existente propone tres piezas enteras por mueble: nueve en total. Costo histórico medio ponderado = Bs 16; comparación histórica adaptada = 9 × 16 + 3 × 50,50 + 7 = **Bs 302,50**. Con precio actual Bs 10 por pieza: **Bs 248,50**. El total histórico original **Bs 188** permanece sin modificación. El redondeo y el cambio de lote se señalan para revisión.

## Archivos y conservación

| Archivo | Cambio |
|---|---|
| app/Services/PropuestaCasoService.php | Histórico adaptado exacto, costo ponderado por material, flag de lote diferente y referencia económica inicial en token firmado. |
| resources/views/admin/similitud/_resultados.blade.php | Dos filas comparables, alcance de precios actuales y aviso de cambio de lote. |
| tests/Feature/SimilitudTest.php | Histórico ponderado, cantidades iguales, costos fijos, pendientes independientes y procedencia. |
| tests/browser/fase4b.cjs | Dos estimaciones y avisos por lote en el flujo existente. |
| Documentación vigente, AGENTS.md y REQUISITOS.md | Estado y reglas del cierre, conservando decisiones previas. |

No se modificaron modelos, rutas, migraciones, dependencias, controladores, cálculo de aprobación o PDF comercial. No hay nuevos campos que llenar. La referencia económica consultada se incorpora a la procedencia firmada ya existente y a la auditoría habitual al guardar; no es un nuevo historial ni una autorización de importes.

Los 35 casos iniciales conservan procedencia y revisión. No se inventa evidencia de fabricación, validación humana o precisión comercial. Permisos del maestro/propietario y protección de cotizaciones aprobadas se mantienen.

MySQL carpinteria_el_tajibo conserva **29 tablas, 25 migraciones y 51 FK**. Diagnóstico de todas las tablas antes/después idéntico; cero escrituras operativas, sin migraciones/seeders o restauraciones. No se necesitó un nuevo respaldo SQL porque no se actualizó la base. Copias previas de archivos y verificaciones privadas en storage/app/private/cierre-5c-2026-11-10. El sistema escolar no se modificó.

## Verificación

Suite completa: **246 pruebas/2367 aserciones, 356,30 s**, aprobadas. Navegador: **108 comprobaciones**, aprobadas sin errores JavaScript, en SQLite temporal; 86 capturas acumuladas. Primera comprobación económica: cuatro pruebas/26 aserciones. Detalle en [verificación vigente](VERIFICACION.md).

MySQL de solo lectura: 27 detalles existentes (12 con referencias, siete fuera de cobertura y ocho sin antecedentes); controlador/Blade y dos propuestas por propietario/maestro comprobados, sin autenticación HTTP MySQL. Esas cantidades no son métricas de precisión.

Pruebas de escritura aisladas en SQLite. Evidencias en storage/app/private/cierre-5c-2026-11-10 y [resultado de navegador](capturas/fase5c/resultado-browser.json).

- [Formulario escritorio](capturas/fase5c/formulario-similitud-1440.png).
- [Formulario móvil](capturas/fase5c/formulario-similitud-390.png).
- [Tema oscuro](capturas/fase5c/formulario-similitud-oscuro-390.png).
- [Propuesta editable](capturas/fase5c/formulario-propuesta-editable-390.png).

## Alcance final

5C queda cerrada funcionalmente en búsqueda previa al guardado, selección/edición y comparación económica. La evaluación con personal y datos de trabajos comprobados sigue pendiente; no se declara precisión comercial. Sin pagos, captura adicional de gastos, descuentos de existencias, nuevos módulos o parámetros definitivos.
