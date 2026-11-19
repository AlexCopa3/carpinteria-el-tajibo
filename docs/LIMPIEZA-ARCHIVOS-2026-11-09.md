# Limpieza de archivos — 9 de noviembre de 2026

## Alcance

Se retiraron archivos sin uso comprobado o sustituidos, consolidando documentos vigentes y preservando requisitos, migraciones y lógica funcional. No se modifica MySQL, no se ejecutan seeders sobre la base local y no se toca el sistema escolar.

## Archivos retirados con respaldo

- 11 archivos de código/ensayos/notas: un enum sin referencias, siete README internos de reserva/duplicados, el escenario de navegador 4A sustituido y dos auxiliares MySQL que aún exigían un esquema anterior.
- 1302 archivos de documentación/evidencia: informes y resultados intermedios, notas históricas y capturas sustituidas. Los nombres exactos constan en [el archivo histórico](ARCHIVO-HISTORICO.md) y su manifest privado.
- Respaldo previo íntegro de 1.426 archivos, verificado al descomprimir cada entrada y comparar su SHA-256 con el origen; incluye documentos anteriores y archivos que se actualizan.

Se mantiene una copia privada comprimida. No se borran respaldos existentes ni se presentan los archivos históricos como si describieran el estado actual.

## Justificación de código

TipoMovimiento solo aparecía en su propia declaración; la lógica activa de ExistenciaMaterialService utiliza sus tipos actuales sin ese enum. fase4a.cjs no contiene mensajes de comprobación exclusivos frente al escenario completo fase4b.cjs y sus flujos ya están incluidos allí. package.json pasa a ejecutar fase4b como prueba principal. Los auxiliares tests/mysql/fase4a.php y fase4a-worker.php estaban ligados entre sí a un esquema antiguo de 26/31/32 tablas; no son una comprobación válida del corte actual de 27 tablas y se archivan juntos, sin ejecutarlos.

No se eliminan fixtures de Cotizaciones: PermisosCotizacionTest y sus contratos los usan. Tampoco se retiran componentes Blade, modelos, servicios, políticas, catálogos, migraciones o seeders por tener pocas referencias o tablas vacías. CambiosMaterial e inventario.* aún tienen usos reales. Auth/recuperación se conserva como capacidad condicionada por configuración, no como código muerto.

## Consolidación documental

Quedan documentos de arquitectura, convenciones, permisos, datos, interfaz, ejemplos, instalación y verificación, además del informe/diccionario del corte 5/11 y la entrega 4B. Se agrega docs/README.md como índice. Se corrigen enlaces de documentos retirados y las rutas de referencia a ejemplos/interfaz. AGENTS.md y REQUISITOS.md mantienen sus decisiones funcionales acumuladas.

## Resultados del cierre

- 162 pruebas PHP aprobadas, 1764 aserciones y 159.66 segundos; SQLite aislado.
- 75 comprobaciones de Edge/Playwright aprobadas mediante npm run test:browser; SQLite temporal. Incluye ambos roles, catálogo/existencias, cotizaciones/PDF, pedidos/planificación, SweetAlert2, menú fijo, escritorio/móvil y temas claro/oscuro; sin errores JavaScript.
- 71 entradas de rutas idénticas antes/después y cero enlaces locales ausentes en los documentos activos.
- 21 migraciones y 12 seeders conservados. Ninguna tabla/columna, usuario, permiso, precio, saldo ni historial de MySQL fue modificado.
- Autoload regenerado por Composer sin ejecutar scripts ni actualizar dependencias.
- AGENTS.md y REQUISITOS.md comparados contra su respaldo: texto conservado salvo referencias documentales y nota final de limpieza. No se modificó el sistema escolar.

Evidencia nueva: [resultado de navegador](capturas/actual/resultado-browser.json), [resultado agregado](../storage/app/private/limpieza-2026-11-09/resultado-final.json), [log PHP](../storage/app/private/limpieza-2026-11-09/pruebas-php.log) y [manifest privado](../storage/app/private/limpieza-2026-11-09/plan.json). Los escenarios específicos auth/usuarios/ajustes se conservaron, pero no se ejecutaron por separado en este cierre. No se repitieron pruebas MySQL ni de rendimiento.

