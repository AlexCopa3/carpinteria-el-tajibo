# Carga de 87 casos vinculados a pedidos

Aplicación y verificación: 16/11/2026. Base exclusiva: `carpinteria_el_tajibo` en MySQL local. Sin cambios en el proyecto escolar.

## Resultado

| Casos por pedido | Pedidos | Casos |
|---|---:|---:|
| 1 | 12 | 12 |
| 2 | 12 | 24 |
| 3 | 9 | 27 |
| 4 | 6 | 24 |
| Total | 39 | 87 |

Se conservaron los dos casos vinculados existentes, se retiraron los 25 sin pedido y sus 118 líneas y se generaron 85 casos nuevos mediante 37 pedidos y cotizaciones adicionales. Cada caso corresponde a un detalle de pedido. No se renumeraron IDs.

Totales locales: 3 usuarios, 36 clientes, 5 categorías, 26 materiales, 59 cotizaciones, 47 pedidos, 4 pagos y 87 casos con 406 líneas de materiales. Pedidos: 4 aceptados, 4 en fabricación, 25 terminados y 14 entregados. Se mantienen 31 tablas, 27 migraciones, 2 roles y 50 permisos.

Por tipo: 14 escritorios, 14 mesas, 17 casos de sillas, 14 armarios, 14 estantes y 14 puertas. Se reutilizan las 25 plantillas vigentes con contexto de La Paz y El Alto: no equivalen a 87 diseños distintos. Material principal Madera o Melamina; sin MDF. Cada caso conserva características, cantidad de muebles, materiales y cantidades utilizadas, costos estimados desglosados y precio del grupo. La revisión humana sigue pendiente y el origen es simulado.

## Implementación

- `database/seeders/CasosHistoricosSeeder.php`: genera pedidos con una a cuatro líneas, cotiza y aprueba mediante servicios, registra uso de materiales, cierra y retiene los casos. Operación transaccional; identifica la carga por auditoría y no sobrescribe ediciones al repetir.
- `database/seeders/FabricacionSeeder.php`: comparte el cierre validado y conserva el comportamiento de los dos pedidos anteriores.
- `tests/Support/CasosPerfil.php`: corpus fijo exclusivo de pruebas del comparador, separado de la carga de datos de la aplicación.
- Pruebas de instalación, seeders y similitud actualizadas; fixture de navegador del comparador ajustado al corpus fijo.

Los primeros 25 casos nuevos conservan el vínculo técnico lote/posición con los descriptores del perfil de similitud. No se reescriben sus características ni costos después del cierre; la asignación del vínculo se realiza en su transacción inicial. No se modificaron pesos, rangos, k=3 ni umbral=80. La cifra de 87 describe esta carga y no limita la retención futura de nuevos pedidos.

No se modificaron controladores, modelos, rutas, vistas, estilos ni migraciones. Los usuarios, hashes de contraseñas, roles, permisos, clientes, pagos, existencias y todos los demás registros anteriores ajenos a la retirada autorizada se compararon íntegramente y permanecen idénticos.

## Respaldo y ensayo

Evidencia privada en `storage/app/private/casos-pedidos-87-20261116234212_cecc62/`:

- `respaldo.sql`: copia completa anterior al ensayo.
- `respaldo-previo-aplicacion.sql`: copia completa nueva inmediatamente anterior a la aplicación.
- Ambos respaldos se restauraron y compararon con el origen antes de utilizarse.
- SHA-256 del respaldo previo: `077848ad56a418f2e3d7d4af38844657dfebcafda34c55bfd36505f6835448af`.
- `antes.json`, `ensayo.json`, `casos-sin-pedido.json`, `resultado-local.json`, `despues.json`, `verificacion-vistas-locales.json` y `cierre.json` documentan la ejecución.

Se confirmó base, estructura y contenido sin diferencias respecto al ensayo. Se utilizó mantenimiento durante el respaldo final y la transacción. Se retiró únicamente la copia temporal creada para este trabajo al finalizar; los respaldos privados permanecen. La aplicación está fuera de mantenimiento.

## Verificaciones realizadas

- **53 pruebas funcionales aprobadas, 1.170 aserciones**, en 260,79 segundos: CasosSeederTest, InstalacionSeedTest, SeedersModulosTest y SimilitudTest. Escrituras de pruebas en SQLite aislado.
- **Pint aprobado** para los ocho archivos PHP modificados.
- MySQL: restauración exacta, preservación de todos los registros anteriores no retirados, 87 detalles únicos vinculados, cantidades de casos iguales al uso registrado, ausencia de referencias huérfanas y estructura conservada.
- Repetir DatabaseSeeder no modifica los datos ni duplica casos/pedidos, tanto en el ensayo como en la base local.
- Totales de pedido iguales a la suma de sus detalles; precios de detalle iguales a los aprobados; versiones aceptadas coherentes; pagos sin superar acuerdos.
- **157 respuestas HTTP 200**: listados, reportes, los 47 pedidos, los 87 casos y una cotización; cuatro PDF con firma válida. Lecturas sin alterar datos. Los dos maestros conservan su redirección obligatoria al perfil para cambiar contraseña.
- Recomendador comprobado en copia y base local: devuelve tres casos para la consulta de mesa de madera utilizada en la verificación.
- **Microsoft Edge / Playwright aprobado** sobre MySQL aislado: listado, pedidos con uno/dos/tres/cuatro casos, fichas y enlaces al pedido; escritorio y móvil sin desbordamiento horizontal ni errores JavaScript. Las credenciales de navegador se generaron solo en la copia; nunca se cambiaron las de la base local.

[Resultado de navegador](capturas/casos-pedidos-87-2026-11-16/resultado-browser.json) y [captura de pedido con cuatro casos](capturas/casos-pedidos-87-2026-11-16/pedido-4-casos.png). El ensayo de navegador requiere una copia aislada previamente preparada; no debe apuntarse a la base local.

No se ejecutó la suite completa: los resultados anteriores corresponden a las pruebas seleccionadas para esta carga. No se ejecutaron migraciones ni se añadieron tablas o módulos.
