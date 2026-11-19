# Instalación local con migrate --seed

> Estado vigente al 17/11/2026: el historial está distribuido desde 2020. Se mantienen 47 pedidos, 59 cotizaciones, 87 casos y 36 clientes; ahora hay 82 pagos. Estados: 4 aceptados, 4 en fabricación, 3 terminados y 36 entregados. HistorialCarpinteriaSeeder forma parte de la instalación local, con repetición idempotente. Véase [historial y verificaciones](HISTORIAL-CARPINTERIA.md).


> Actualización vigente del 16/11/2026: 87 casos vinculados a 39 pedidos (12 con uno, 12 con dos, 9 con tres y 6 con cuatro). Se retiraron los 25 casos sin pedido y se conservaron los dos vinculados anteriores. La instalación completa contiene 3 usuarios, 36 clientes, 26 materiales, 59 cotizaciones, 47 pedidos y 4 pagos. Las cifras anteriores describen cargas históricas. Véase [informe de la carga](INFORME-CASOS-PEDIDOS-87.md).


> Materiales principales vigentes: Madera y Melamina. La cuenta del propietario se introduce interactivamente y se conserva al repetir la carga.

> Estado posterior vigente (9/11/2026): Casos históricos usa una sola búsqueda, sin selector o modo separado. Perfil único config/perfiles/similitud-casos-historicos-v1.json. Los registros conservan su procedencia y revisiones. Véase [flujo actualizado](FLUJO-COTIZACION-CON-CASOS.md); esta decisión sustituye las instrucciones anteriores de seleccionar demostración.

**Decisión del usuario: 9 de noviembre de 2026.** Sustituye la separación obligatoria entre DatabaseSeeder y los ejemplos para una instalación local. No modifica el alcance de los módulos ni autoriza nuevas fases.

## Funcionamiento

Con .env configurado para la base carpinteria_el_tajibo y APP_ENV=local, ejecutar:

```powershell
php artisan migrate --seed
```

DatabaseSeeder prepara catálogos y permisos y llama a DatosSistemaSeeder. En una instalación sin propietario solicita sus datos mediante el comando existente tajibo:crear-propietario. La contraseña la elige quien instala, se solicita de forma oculta y se almacena cifrada. No se copia una contraseña del equipo original ni se genera una cuenta de acceso con clave conocida.

Si ya existe propietario activo se reutiliza, sin modificar su cuenta o contraseña. Si solo hay propietarios inactivos, se detiene para no reactivarlos ni crear otro por iniciativa propia. Sin interacción y sin propietario no se crean credenciales: hay que ejecutar el comando interactivamente.

El coordinador conserva los dos lotes históricos de auditoría y la organización por módulo. En una instalación completa genera 36 clientes, 26 materiales, 59 cotizaciones, 47 pedidos y 87 casos vinculados a pedidos, además de las categorías, existencias, movimientos, detalles y estados relacionados. Si un lote ya existe no lo repite ni sobrescribe ediciones posteriores. Los registros ficticios conservan su procedencia interna; no son casos reales ni nuevas validaciones comerciales humanas.

## Configuración y límites

- config/datos_sistema.php usa TAJIBO_DATOS_SISTEMA si se configuró expresamente; si no, habilita ejemplos por defecto solamente para APP_ENV=local.
- Para omitir ejemplos, definir TAJIBO_DATOS_SISTEMA=false. En producción DatabaseSeeder no invoca los ejemplos, incluso con la opción activada.
- Testing mantiene la activación explícita para pruebas controladas. No se cambia phpunit.xml ni la conexión aislada.
- La protección de destino de DatosSistemaSeeder permanece: base MySQL autorizada y esquema/catálogos correctos, o los destinos de pruebas permitidos. No se usa la base escolar.
- Los servicios conservan cálculos, autorizaciones, bloqueos, auditoría y transacciones por lote. No se crean tablas ni se modifican migraciones, precios de registros previos o existencias anteriores.
- El comando añade ejemplos; no vacía la base ni importa todos los datos de la computadora original. Un traslado exacto requiere exportación/importación de MySQL.
- Con datos importantes existentes, conservar respaldo verificado antes de actualizar. La actualización a 87 casos ya se aplicó en MySQL local tras respaldo restaurado y ensayo, sin ejecutar migraciones.

## Actualizar la copia de otra computadora

Copiar la versión actual de database/seeders y config/datos_sistema.php. El comando app/Console/Commands/CrearPropietario.php también debe estar disponible. Conservar .env y archivos propios de destino. Si no existía DatosSistemaSeeder.php, una copia antigua del proyecto no puede usar la instalación nueva sin estos archivos y sus módulos.

No se requiere npm para este cambio de PHP. Si la computadora tenía configuración cacheada, limpiar una vez con php artisan config:clear antes de la instalación. No dejar TAJIBO_DATOS_SISTEMA=false si se desean cargar los ejemplos automáticamente.

## Archivos del cambio

- database/seeders/DatabaseSeeder.php: integración local y alta inicial interactiva.
- config/datos_sistema.php: activación predeterminada local y opción explícita de exclusión.
- .env.example y README.md: instrucciones actualizadas.
- tests/Feature/InstalacionSeedTest.php: integración del comando, repetición, desactivación, producción y ejecución sin interacción.
- tests/Feature/SeedersModulosTest.php: nombre de la prueba de exclusión en testing actualizado, sin retirar verificaciones existentes.

## Verificación histórica de la integración inicial

- Prueba específica de instalación: 4 pruebas y 33 aserciones aprobadas.
- Regresión completa: 162 pruebas y 1.764 aserciones aprobadas, en 180,10 segundos.
- Todas las escrituras y migraciones de prueba se ejecutaron en SQLite aislado; no se sembró ni modificó MySQL operativo.
- Se probó el comando migrate --seed con captura interactiva de propietario, preservación de su contraseña/cuenta al repetir, cantidades de ambos lotes y ausencia de duplicados.
- No se cambiaron vistas ni estilos, por lo que no se repitieron pruebas de navegador para esta entrega.

Se preparó una actualización por USB con seeders y configuración, sin .env, SQL, usuarios, tokens ni contraseñas reales. Antes de usarla, conservar el .env y archivos propios del destino.

## Antecedente histórico: fase 5A — 9/11/2026

La instalación local con php artisan migrate --seed incluye ahora CasosHistoricosSeeder: 35 casos simulados pendientes y 172 líneas de materiales, después de los ejemplos de catálogo/pedidos existentes. Conservar también database/seeders/data/casos_historicos.json al trasladar el proyecto. CasosPermisosSeeder incorpora las capacidades nuevas sin restablecer personalizaciones. Producción/desactivación no cargan datos de ejemplo; repetir no sobrescribe revisiones. La recuperación de casos usa el perfil único distribuido con la aplicación. En una base existente, antes de las dos migraciones nuevas se requieren respaldo restaurable y ensayo; no usar comandos de borrado. [Entrega 5A](FASE-5A.md).

## Traslado del perfil experimental 5B

Copiar config/similitud.php y config/perfiles/similitud-casos-historicos-v1.json junto a servicios/controlador/vistas actuales. Referencias por lote/posición/código/alias, sin IDs fijos de otra computadora. No regenerar automáticamente hash/rangos/perfil: requiere revisión/versionado. Nueva cotización/Editar → Describe el mueble → Buscar casos similares, sin guardar ni seleccionar otro modo. El perfil no carga datos ni sustituye importar una base existente. [Entrega](FASE-5B.md).

## Recursos del formulario con casos

Al copiar esta actualización, incluir PropuestaCasoService, controlador/servicios/formulario/validaciones y tarjetas actuales, además de public/build con su manifest. Si no se copian los compilados, ejecutar npm ci y npm run build. Esta integración no añade migraciones ni necesita recargar seeders; incluye la configuración de perfil único de Casos históricos.

## Antecedente histórico: fase 6A — Pagos

La entrega añade únicamente 2026_11_10_200000_create_pagos_table.php. En aquella entrega, migrate --seed creaba pagos vacía; actualmente PagosSeeder carga cuatro pagos simulados. Para una base existente, respaldar/restaurar/verificar y ensayar antes de migrar: copiar también modelo/política/servicio/Form Request/controlador/vistas, integración de pedidos, rutas y navegación actuales. No restablecer permisos; pagos.consultar y pagos.gestionar ya existían. El propietario registra efectivo/QR manualmente. [Entrega y aplicación comprobada](FASE-6A.md).


## Nombres actuales de la carga

El coordinador es DatosSistemaSeeder y la configuración es config/datos_sistema.php (datos_sistema.habilitado). Usar TAJIBO_DATOS_SISTEMA para activar o desactivar la carga. Se conserva la variable antigua TAJIBO_DATOS_EJEMPLO como alias si la nueva no está definida. Los lotes y claves de auditoría existentes se mantienen, por lo que el cambio de nombre no provoca otra carga. Los documentos relacionados se llaman CARGA-DATOS.md y CATALOGO-CASOS-HISTORICOS.md.
