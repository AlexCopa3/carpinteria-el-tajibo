# Consumos de referencia por mueble

Actualizado: 17/11/2026. Se corrigieron los 87 casos y sus registros vinculados, manteniendo IDs 1–87, fechas, nueve tipos, cantidades de muebles, dimensiones, procedencia y revisión pendiente. No se acreditó validación por un carpintero.

## Unidades y alcance

El usuario confirmó utilizar medidas de referencia porque el catálogo no indicaba las dimensiones de «pieza» o «tablero».

- Madera: tablón de 240 × 20 × 5 cm, volumen bruto de 0,024 m³. Cálculo por volumen de los componentes, con 20 % de reserva y redondeo a piezas enteras por mueble. Se consideran paneles encolados y patas laminadas. Es una estimación de suministro; no es un patrón de corte ni garantiza el aprovechamiento de cada tablón.
- Melamina: tablero de 275 × 183 cm y 18 mm. Asignación rectangular por cortes, permitiendo rotación, con 1 cm de recorte por borde y corte de 4 mm. Se toma el mayor entre la asignación de paneles y el área más 10 % de reserva. Se asignan tableros enteros por unidad de mueble; los sobrantes quedan incluidos. Fondos y cajones usan el mismo tablero.
- Juego de correderas: una unidad equivale a un par para un cajón.
- Canto PVC: preencolado, metros de bordes visibles más 10 %. No se cantean fondos ni uniones ocultas.
- Vidrio: pieza de referencia 75 × 200 cm, 4 mm, una por paño frontal de vitrina con recorte incluido. No representa un precio de vidrio cortado de cualquier medida.

Las especificaciones se registraron en los cinco materiales correspondientes. Se mantienen los 27 materiales y sus precios de referencia anteriores; no son cotizaciones verificadas de proveedores. La cantidad registrada corresponde al suministro asignado al mueble, incluidos recortes; no se creó un control de retazos.

## Criterios de los nueve tipos

- Mesa: tapa, patas/apoyos, faldones/travesaño y repisa inferior cuando corresponde.
- Silla: asiento, patas delanteras y traseras, respaldo y travesaños.
- Puerta: hoja, jambas y dintel del marco, bisagras y acabado cuando corresponde. Alcance sin cerradura ni instalación de obra.
- Escritorio: tapa, apoyos, faldón, repisas y cajonera según sus características.
- Armario, estante, velador, cómoda y vitrina: laterales, tapa/base, fondo, divisiones y repisas. Los frentes y cajas de cajones se cuentan aparte.
- Cajones: un juego de correderas y un tirador/pomo por cajón.
- Puertas de mueble: bisagras según altura y peso de referencia y un tirador/pomo por puerta. Vitrina con bastidores, junquillos y paños frontales.
- Repisas: cuatro soportes por repisa regulable de los muebles de caja.

El recuento de bisagras depende de altura y peso; ese criterio general está documentado por [Blum](https://ea.blum.com/en/number-of-hinges/). Las reglas numéricas de esta carga son supuestos conservadores, no la tabla de aplicación de un herraje concreto.

## Acabados

Barnizado natural incorpora lija, sellador, barniz y disolvente. Sin acabado omite recubrimientos, pero conserva preparación y encolado de la madera. Melamina no recibe barniz.

Se calcula la superficie de los componentes por ambas caras y cantos: barniz/pintura con dos manos, rendimiento de referencia de 9 m²/L por mano y 10 % de reserva; sellador con una mano a 10 m²/L. Disolvente: 20 % del volumen de recubrimientos, como supuesto de la carga. Lija y cola crecen con las superficies. Los litros se redondean hacia arriba a centésimas.

La [ficha de Barniz Copal de Monopol](https://www.pinturasmonopol.com/wp-content/uploads/2023/03/Barniz-copal.pdf) informa 35–40 m²/galón/mano y dilución según aplicación. Se usó como referencia general para elegir el rendimiento; el producto genérico del catálogo no se convirtió en esa marca. Los rendimientos de sellador, cola, lija, reservas y dilución son supuestos explícitos. Confirmar despiece, veta, encolado, formulación y herrajes del trabajo concreto antes de fabricar.

## Corrección de registros y precios

ConsumosMaterialesSeeder se ejecuta después de limitarA87 y antes de finalizarIds en DatabaseSeeder. Se aplica una vez por consumos_muebles_referencia_v1 exclusivamente a los 87 antecedentes generados pendientes de revisión. No modifica el cálculo de similitud ni introduce un cálculo automático para las nuevas cotizaciones manuales.

Cantidades por mueble × muebles solicitados se registran de forma consistente en materiales_cotizacion, materiales_pedido y materiales_caso. Se recalculan subtotales, costos de los detalles/cotizaciones, precios acordados, casos y pedidos. Se mantienen mano de obra y otros costos; el precio final conserva el recargo efectivo anterior por detalle. Los pagos se ajustan proporcionalmente al nuevo acuerdo, preservando cuáles estaban completamente pagados y cuáles tenían saldo, sin añadir pagos ni cambiar fechas.

Usuarios, contraseñas, permisos, clientes, existencias y movimientos permanecen intactos. No se restan retroactivamente estos consumos históricos. Sin cambios de esquema, controladores, modelos, rutas o algoritmo. Perfil v2, 61 referencias, k=3 y umbral 80 % conservados. Repetir los seeders no duplica ni vuelve a calcular registros ya corregidos.

## Las tres mesas de la consulta

| Caso | Medidas cm | Madera de referencia | Barniz L | Materiales Bs | Costo total Bs |
|---|---|---:|---:|---:|---:|
| 62 | 160 × 80 × 75 | 4 piezas | 1,12 | 404,28 | 584,28 |
| 86 | 160 × 90 × 75 | 4 piezas | 1,21 | 410,75 | 600,75 |
| 82 | 180 × 90 × 76 | 4 piezas | 1,33 | 419,13 | 774,13 |

La diferencia restante en costo total procede de la mano de obra y otros costos originales, que no se normalizaron por esta solicitud. Compartir un número entero de piezas no implica consumos netos o aprovechamientos idénticos.

## Aplicación y verificaciones

Base: carpinteria_el_tajibo. Respaldo completo restaurado, ensayo MySQL aislado y navegador aprobados antes de aplicar; nuevo respaldo restaurado inmediatamente antes. Aplicación transaccional de los registros, con claves foráneas activas; ajuste de autoincrementos fuera de transacción.

Evidencia privada: storage/app/private/consumos-20261118023543_ee9c4e/. SHA-256 del respaldo previo: 6a0c74a85381ba01276181c449a7415f7f67d857e5162c2a8795cf61d11fbc45. La auditoría conserva los registros anteriores y los despieces propuestos. Se verificó que cantidades de muebles, descriptores, fechas e IDs permanecieran intactos y solo cambiaran los consumos, sus importes vinculados, especificaciones y versión del caso.

- 8 pruebas, 2098 aserciones aprobadas: reglas por tipo, accesorios/acabados, rechazo de panel imposible, instalación completa, relaciones, cantidades por grupo, IDs y repetición. No se ejecutó toda la suite.
- Pint aprobado.
- MySQL: relaciones sin huérfanos; cantidades, subtotales y costos sincronizados; precios/saldos consistentes; proporción pagada conservada; tablas protegidas idénticas; esquema intacto e idempotencia.
- Navegador: las mismas mesas 86/82/62, cuatro tipos, aplicación de principal/alternativas, móvil y oscuro sin desbordamiento ni errores JavaScript. [Resultado](capturas/consumos-materiales/resultado-browser.json).
- Lecturas locales: 157 HTTP 200, cuatro PDF válidos, cambio obligatorio de contraseña de los dos maestros y consultas de control de los nueve tipos correctas, sin escrituras. Estas consultas no constituyen validación independiente de calidad.

Estado: 87 casos, 47 pedidos entregados, 59 cotizaciones y 82 pagos; 635 líneas de materiales de casos/pedidos y 666 de cotizaciones. 31 tablas y 27 migraciones. Importe acordado Bs 134.767,92; pagado Bs 126.379,49; saldo Bs 8.388,43.


## Excel y cierre

Registros-El-Tajibo.xlsx sincronizado con 47 pedidos, 809 líneas de costos, 82 pagos y 36 clientes. Se mantuvo el diseño. Fórmulas recalculadas, pagos/saldos comparados con MySQL, prueba temporal de modificación con restitución y XLSX guardado sin errores de fórmula; vistas afectadas revisadas. Totales exactos coincidentes.

La copia temporal del ensayo fue retirada conservando los respaldos y las evidencias. Base operativa intacta durante la retirada y aplicación fuera de mantenimiento.

## Tornillos por montaje: criterio vigente v2 — 17/11/2026

El usuario solicitó reducir y adecuar los tornillos. Se retiró la base uniforme de 24. Las cantidades siguientes son por unidad de mueble y se multiplican por los muebles solicitados; son hipótesis del montaje de referencia, no cantidades universales para cualquier ensamble.

| Tipo | Base de tornillos |
|---|---:|
| Mesa sin repisa | 12: ocho para faldones/apoyos y cuatro para la tapa |
| Silla de madera ensamblada y encolada | 4 para el asiento |
| Puerta | 6 para el marco, más cuatro por bisagra |
| Escritorio | 8 en madera o 12 en melamina; ocho adicionales si lleva cajonera/compartimento |
| Cajas: armario, estante, velador, cómoda y vitrina | 8 en madera encolada o 12 en melamina atornillada |

Se suman cuatro por repisa fija de mesa/escritorio; las repisas regulables de caja ya tienen sus soportes y no suman esa fijación. Cada cajón agrega ocho tornillos para el par de correderas y cuatro para caja de madera encolada u ocho para caja de melamina atornillada. Cada bisagra suma cuatro. No se reducen las fijaciones de herrajes solo por disminuir el total.

ConsumosMaterialesSeeder usa ahora consumos_muebles_referencia_v2, aplicado una vez; el marcador v1 se conserva en auditoría. Una instalación nueva carga directamente el criterio vigente. El desglose de tornillos por estructura/repisas/cajones/bisagras queda registrado con cada plan.

Se verificó por caso/material que todos los demás consumos, costos unitarios y subtotales permanecen idénticos; ningún caso aumenta tornillos. Se recalcularon los costos y precios relacionados, manteniendo recargos efectivos y proporciones pagadas. Mano de obra, otros costos, fechas, descriptores, revisión y origen intactos. Cuentas, permisos, existencias y movimientos intactos.

Mesas 62, 82 y 86: 12 tornillos por mueble, conservando los cuatro tablones y todos los consumibles del acabado. Costos totales: Bs 580,08; Bs 769,93 y Bs 596,55, respectivamente. Sus precios finales quedan en Bs 696,10; Bs 923,92 y Bs 715,86.

Respaldo completo restaurado, ensayo MySQL aislado y navegador aprobados antes de aplicar. Evidencia privada: storage/app/private/tornillos-20261118025515_4f6ce1/. SHA-256 del respaldo previo: 6f8736f5b7455d93d25060131c1e84e8b7b712b8bfe02fd674520d545ab1289c. Se verificaron integridad referencial, sincronización de cantidades/importes, ausencia de sobrepagos, proporciones pagadas e idempotencia. Sin migraciones nuevas ni cambios de esquema.

9 pruebas/2106 aserciones y Pint aprobados; no se ejecutó toda la suite. Navegador: las tres mesas con 12 tornillos, cuatro tipos, aplicación de propuestas, móvil y oscuro, sin errores ni desbordamiento. [Resultado](capturas/tornillos-materiales/resultado-browser.json). Base local: 11 respuestas HTTP 200 y un PDF válido, sin escrituras.

Totales vigentes: Bs 133.514,17 acordados, Bs 125.221,02 pagados y Bs 8.293,15 pendientes. Se mantienen 87 casos con IDs 1–87, 47 pedidos entregados, 59 cotizaciones, 82 pagos y 27 materiales; 635 líneas de materiales de casos/pedidos y 666 de cotizaciones. Esta sección sustituye los importes de v1; el resto de sus referencias se conserva.

Cierre v2: Excel sincronizado (47 pedidos,809 costos,82 pagos,36 clientes), fórmulas y totales exactos del XLSX guardado verificados y vistas afectadas revisadas. Copia temporal retirada conservando respaldo. Aplicación disponible; casos1–87 y siguienteID88.
