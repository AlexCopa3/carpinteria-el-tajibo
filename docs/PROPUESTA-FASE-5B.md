# Propuesta 5B — Recuperación de casos similares

> Estado posterior: diseño 5B aprobado e implementado el 9 de noviembre de 2026. Véase [entrega 5B](FASE-5B.md). El texto siguiente conserva el análisis previo a aprobación; sus menciones de preparación no describen el estado actual. 5A y 5B entregadas; 5C pendiente.

9 de noviembre de 2026. El usuario solicita pasar a la siguiente fase tras 5A. Este documento concreta el método y parámetros que REQUISITOS.md/AGENTS.md mantienen sujetos a revisión. **Todavía no se implementó ni activó el motor de la aplicación.** Los cálculos siguientes son un análisis independiente, no una entrega funcional ni evaluación de precisión comercial.

## 1. Datos disponibles

Lectura actual de MySQL: carpinteria_el_tajibo, 29 tablas y 25 migraciones, sin modificaciones. Existen 35 casos simulados: 34 pendientes y uno revisado; cero casos de origen real. La revisión no cambia el origen. config/similitud.php sigue desactivado y sin valores definitivos; config/tajibo.php no habilita simulados para uso normal.

El análisis experimental utiliza los 35 descriptores sintéticos sin cambiar su revisión, no utiliza clientes, precios o costos para ordenar semejanza. No afirma que los pendientes estén validados por el negocio. Los resultados normales no se llenarán con simulados para ocultar la ausencia de casos reales.

## 2. Alcance propuesto y uso sencillo

Desde Ver cotización, cada mueble tendrá Buscar casos similares. Se usarán sus características guardadas, sin volver a llenar otro formulario de dimensiones/componentes. En 5B se consulta; aplicar materiales/costos al formulario queda en 5C.

La pantalla presentará hasta tres casos, porcentaje, diferencias comprensibles y enlaces a sus fichas. Cada tarjeta mostrará el tamaño original del grupo y sus importes/materiales históricos como referencia, sin convertir el precio del grupo en un precio unitario ni multiplicarlo por la nueva cantidad. No habrá aprobación automática, edición de la cotización, nuevos movimientos o descuentos de existencias.

- **Uso normal:** solo casos reales revisados, completos y con un perfil real aprobado. Hoy no hay casos reales; devolver información de ausencia de antecedentes.
- **Demostración experimental:** selección explícita de modo, exclusivamente en local/testing y cuando la configuración aprobada lo permita. Solo simulados pendientes/revisados completos; descartados excluidos. Identificar el origen y revisión de cada resultado y que no es una recomendación comercial validada. No mezclar ambos grupos ni usar el modo de demostración en producción.
- Mostrar uno o dos casos si son los únicos que cumplen; no inventar tres ni exigir tres para mostrar un antecedente.
- Si ninguno cumple, indicar No hay antecedentes suficientemente similares y conservar la cotización manual rápida.

## 3. Comparación propuesta

Primero filtrar por **el mismo tipo exacto de mueble**. Una mesa no compite con una silla aunque coincidan algunas medidas. Propuesta para datos mixtos: promedio ponderado de diferencias normalizadas, inspirado en Gower; k-NN selecciona los vecinos con menor distancia. No es un clasificador de tipos ni un entrenamiento que aprenda precios por sí solo.

| Característica | Peso piloto |
|---|---:|
| Largo | 15 % |
| Ancho | 15 % |
| Alto | 15 % |
| Material principal | 25 % |
| Acabado | 10 % |
| Puertas | 5 % |
| Cajones | 5 % |
| Divisiones | 5 % |
| Cantidad de muebles del grupo | 5 % |
| Total | 100 % |

Los materiales secundarios serán salidas de referencia; no se pedirá al personal rellenar de nuevo toda la lista para poder buscar. No usar precios, ganancia, observaciones libres o ubicación como características. Comparar material por ID del catálogo y acabado por su valor explícito, sin tratar códigos numéricos de categorías como distancias ni inventar sinónimos o interpretar texto libre. Otros no demuestra equivalencia de detalles que no se capturaron.

Para una característica numérica: diferencia = mínimo(1, valor absoluto de la diferencia / rango del perfil). Para material/acabado: 0 si coinciden y 1 si difieren. Distancia = suma de peso × diferencia / suma de pesos. Similitud = 100 × (1 − distancia). Un dato requerido ausente no se convierte en cero ni se elimina su peso para inflar el resultado; se informa el dato faltante.

Si el rango de un campo es cero, igualdad aporta diferencia 0 y desigualdad 1, evitando división por cero. La ordenación y comparación con el umbral usan aritmética racional/decimal exacta; solo se redondea a dos decimales para mostrar. 79,9999 % no cumple 80 % aunque se vea redondeado a 80,00 %. En empates, ID ascendente para resultados reproducibles.

**Propuesta piloto a aprobar:** k=3, máximo de resultados=3 y umbral **al menos 80 %** antes del redondeo. Son una hipótesis experimental revisable; no son parámetros definitivos ni una precisión validada. El porcentaje es un puntaje de comparación, no una probabilidad de que el presupuesto sea correcto.

### Rangos fijos y límite de extrapolación

Congelar rangos por tipo a partir del conjunto de referencia del perfil de demostración, con versión, IDs/descriptores contribuyentes y hash. No recalcularlos silenciosamente al agregar un caso ni ampliar el rango incluyendo la nueva consulta. El motor devolverá la versión/huella del perfil junto a sus resultados.

Como regla adicional del proyecto para revisar: si una característica numérica de la consulta queda fuera del intervalo que cubre ese perfil, **no emitir una recomendación calificada**; explicar la característica fuera de cobertura y continuar manualmente. Esto evita que una diferencia extrema en una sola dimensión conserve un porcentaje alto porque las demás coinciden. No se propone afirmar que un escritorio de 300 cm está cubierto por ejemplos de 100–150 cm.

Esta regla es conservadora: una diferencia pequeña fuera de los límites también dará ausencia de cobertura. Se revisará con ejemplos del negocio antes de flexibilizarla o definir tolerancias; no se fijan tolerancias arbitrarias ahora. Una diferencia de acabado Otros se presenta con advertencia de información limitada, sin interpretar notas como si fueran atributos estructurados.

La revisión pendiente→revisado no altera descriptores/rangos del perfil de demostración. Si se descarta un caso que contribuyó al perfil o cambian sus descriptores, exigir una revisión/versionado del perfil, sin conservar una calibración silenciosamente distinta. Los casos reales futuros necesitarán un perfil real aprobado; no se presentará la calibración sintética como validación productiva.

## 4. Ejemplos calculados

Consulta exacta de los descriptores actuales, solo en el análisis experimental. Los ID son los normales de la base; no se generan nuevos códigos.

| Consulta de demostración | Casos que alcanzarían 80 % |
|---|---|
| Mesa de madera 150 × 85 × 76, dos unidades | Caso 9: 96,63 %; Caso 12: 88,63 % |
| Escritorio de melamina 130 × 60 × 75, dos cajones | Caso 4: 82,75 % |
| Armario de madera 150 × 55 × 200 | Caso 21: 86,00 % |
| Escritorio fuera del rango, largo 300 cm | Ninguno; continuar manualmente |

El primer ejemplo encuentra dos referencias y no completa una tercera por debajo del umbral. El segundo encuentra una. Los resultados no significan que esas cantidades de materiales puedan reutilizarse sin revisión humana ni escalarse geométricamente por dimensiones.

Comparación descriptiva de cuántos resultados pasarían distintos umbrales (máximo tres), sin modificar el piloto:

| Consulta | Umbral 75 % | Umbral 80 % | Umbral 85 % |
|---|---:|---:|---:|
| Mesa de madera 150 × 85 × 76, dos unidades | 2 | 2 | 2 |
| Escritorio de melamina 130 × 60 × 75, dos cajones | 1 | 1 | 0 |
| Armario de madera 150 × 55 × 200 | 1 | 1 | 1 |

Estos números solo muestran sensibilidad de estas consultas. No sirven para elegir el umbral por precisión: no hay juicios independientes del propietario/maestros ni datos reales que permitan medirla. No se entrenó o evaluó un predictor de costos.

## 5. Arquitectura y archivos previstos

No se prevén tablas ni migraciones nuevas en 5B: reutilizar los casos/materiales de 5A. El perfil piloto se conservaría en la configuración propia versionada, aprobado por el propietario y documentado; no se añadirá ahora otro formulario cotidiano de parámetros ni se escribirá código PHP desde la web. Una futura administración de perfiles persistentes necesitará su diseño específico.

- RecuperacionCasosService para filtrado, integridad del perfil, diferencias, k/umbral y formato de referencia sin campos administrativos.
- SimilitudController con consulta explícita y validación/autorización de cotización y detalle; ruta GET propuesta /admin/cotizaciones/{cotizacion}/detalles/{detalle}/similares, nombre admin.similitud.show, antes de cualquier enlace de aplicación futuro. Comprobar que el detalle pertenece a la cotización, además de sus permisos.
- Form Request si la validación de modo/parámetros lo justifica; características tomadas del detalle guardado, sin confiar en precios/perfiles enviados por el cliente.
- Blade de consulta por módulo y enlace discreto por mueble en Ver cotización, manteniendo AdminLTE, iconos, SweetAlert2 donde corresponda, botones y claro/oscuro/móvil.
- config/similitud.php y archivo de perfil de referencia propio con versión/rangos y conjunto identificado; sin modificar vendor ni introducir bibliotecas nuevas. Reutilizar el cálculo exacto disponible en el proyecto.
- Pruebas funcionales y de navegador con datos aislados. No modificar el seeder, revisiones o casos actuales para que la búsqueda aparente tener más evidencia.

La consulta exige cuenta activa y permisos cotizaciones.consultar, casos.consultar y costos.consultar, con políticas del servidor. No crear permisos administrativos redundantes ni conceder al maestro márgenes, ganancias o aprobación. No habrá escritura de cotizaciones/casos ni registro de búsquedas mediante GET en MySQL. Un enlace en la interfaz no sustituye la autorización de acceso directo.

## 6. Verificación realizada en esta preparación

Un analizador independiente ejecutó **247 comprobaciones aritméticas** de identidad, simetría, límites, suma de pesos, corte sin redondeo y separación por tipo en los ejemplos. No son pruebas funcionales de Laravel ni métricas de exactitud comercial. Los casos consultados no se usaron como su propia evaluación predictiva; comprobar identidad de una fórmula no evalúa generalización.

Evidencias privadas: storage/app/private/fase5b-2026-11-09/diagnostico.json, analizar.cjs y analisis-piloto.json. La lectura MySQL cotejó los datos antes/después y no los modificó. No se activó config/similitud.php, no se escribieron controladores/servicios de aplicación ni se ejecutaron migraciones en esta preparación.

Tras aprobar el piloto: implementar y verificar en bases aisladas las reglas, acceso por roles, ausencia de casos reales, modo experimental, datos incompletos, rangos constantes/fuera de cobertura, cambios del perfil, umbral exacto, orden estable y máximo de vecinos. Regresión completa y navegador en claro/oscuro/móvil, preservando precios/PDF, estados, retención, saldos y permisos. Si el alcance siguiera sin escrituras MySQL, verificarlo expresamente; cualquier necesidad nueva de esquema se presentará antes de aplicarla.

## 7. Fundamento y aprobación pendiente

El trabajo original de [Gower, 1971](https://mathematics.foi.hr/Rprojekti/BDP%20concept/Gover_metric.pdf) fundamenta combinar atributos numéricos normalizados y categorías en una similitud. Pesos, umbral, perfiles, exclusiones de cobertura y reglas de demostración de este documento son propuestas específicas del proyecto; el artículo no valida su utilidad para esta carpintería.

AGENTS.md exige: «No fijar pesos, rangos, k o umbral finales sin revisión». Por ello se presenta este diseño antes de activarlo. Se solicita aprobar la implementación 5B con los pesos piloto, k=3/80 %, perfil de demostración identificado y rechazo de extrapolación, conservando el uso normal limitado a reales revisados con perfil real aprobado. La activación sería experimental y revisable, no una declaración de parámetros definitivos o validación humana. Aplicar materiales/precios a la cotización sigue fuera de esta entrega, en 5C.
