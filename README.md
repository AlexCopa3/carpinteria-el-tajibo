# Carpintería El Tajibo

> Estado posterior vigente (9/11/2026): Casos históricos usa una sola búsqueda, sin selector o modo separado. Perfil único config/perfiles/similitud-casos-historicos-v1.json. Los registros conservan su procedencia y revisiones. Véase [flujo actualizado](docs/FLUJO-COTIZACION-CON-CASOS.md); esta decisión sustituye las instrucciones anteriores de seleccionar demostración.

> Actualización posterior autorizada: buscar desde el formulario antes de guardar y elegir el vecino más similar como principal o una alternativa editable. Véase [flujo de cotización con casos](docs/FLUJO-COTIZACION-CON-CASOS.md). Los límites de consulta sola de la entrega original quedan sustituidos únicamente para esta integración.

Aplicación Laravel independiente para cotizaciones de muebles, pedidos, planificación de materiales, pagos y reportes. Conserva las convenciones del sistema escolar como referencia, sin reutilizar su base ni sus funcionalidades escolares.

**Instalación actualizada: 9 de noviembre de 2026.** El [informe general del 5 de noviembre](docs/INFORME-GENERAL-2026-11-05.md) describe los módulos y el esquema de ese corte. Después se integraron ejemplos al seeder local y se implementaron [4C: materiales utilizados](docs/FASE-4C.md), [4D: revisión final, cierre y entrega](docs/FASE-4D.md) y [5A: casos históricos/materiales](docs/FASE-5A.md), con respaldo, ensayo y comprobación de MySQL.

## Instalación local y datos de ejemplo

Requisitos: PHP compatible con ^8.3 y composer.lock, Composer y MySQL/InnoDB. Node/npm se requieren para compilar los recursos si no se dispone de public/build.

1. Copiar el proyecto completo, por ejemplo a C:/wamp64/www/carpinteria-el-tajibo. Si falta vendor, ejecutar `composer install`. Si faltan los recursos compilados, ejecutar `npm ci` y `npm run build`. Conservar public/vendor con los recursos locales de AdminLTE y SweetAlert2.
2. Crear una base MySQL nueva y vacía llamada carpinteria_el_tajibo. Si ya hay registros importantes, preparar y verificar un respaldo antes de actualizarla; no usar comandos de borrado.
3. Crear .env desde .env.example si no existe y configurar DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME y DB_PASSWORD de esa computadora. Usar APP_ENV=local. En una instalación nueva sin APP_KEY, ejecutar `php artisan key:generate`; no reemplazar una clave ya utilizada al trasladar una instalación existente.
4. Desde la carpeta del proyecto, ejecutar:

```powershell
php artisan migrate --seed
```

En modo local este comando carga permisos, unidades, tipos de muebles y los dos conjuntos de datos ficticios mediante DatosEjemploSeeder. Si no hay propietario, solicita nombre, correo y una contraseña elegida por quien instala. No hay credenciales predeterminadas. Si ya hay propietario activo, conserva su cuenta; si solo hay propietarios inactivos, requiere reactivar uno antes de cargar ejemplos.

Los ejemplos agregan 36 clientes, 27 materiales, 26 cotizaciones, 11 pedidos con sus existencias/detalles y 35 casos históricos iniciales con 172 líneas de materiales. No representan ventas ni validaciones humanas reales. El coordinador reconoce los lotes ya cargados y no duplica ni restablece sus registros al repetir el comando. Los 35 casos se registran como simulados pendientes de revisión; La búsqueda única los consulta como antecedentes completos, conservando origen y revisión.

No se necesita activar una variable temporal de PowerShell. Para instalar **sin ejemplos**, definir expresamente `TAJIBO_DATOS_EJEMPLO=false` en .env antes de ejecutar el comando. Producción no carga estos ejemplos desde DatabaseSeeder, aunque la opción esté activada. Las pruebas usan su configuración aislada y no incluyen ejemplos por defecto.

Si existía una caché de configuración anterior al cambio de archivos/.env, ejecutar una vez `php artisan config:clear`. Luego iniciar:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Abrir http://127.0.0.1:8000/login. Para Apache/Wamp, DocumentRoot debe apuntar a public. No ejecutar migrate:fresh, db:wipe ni restauraciones para agregar ejemplos.

## Trasladar una instalación existente

Para reproducir exactamente los datos de otra computadora, exportar/importar su MySQL y conservar APP_KEY y los archivos propios de storage/app. Un seeder reproduce los ejemplos definidos en código, no todos los registros o cambios manuales de la instalación original.

Al actualizar una copia de USB, copiar también los seeders actuales por módulo, DatosEjemploSeeder y config/datos_ejemplo.php. No sobrescribir las credenciales .env ni los archivos subidos de la computadora de destino. Los auxiliares históricos privados no son instaladores.

## Estado funcional

Acceso, usuarios/personal, perfil, roles/permisos, clientes, categorías, materiales, existencias sencillas, cotizaciones manuales con aprobación y PDF, pedidos y planificación están implementados. El pedido avanza por Aceptado → En fabricación → Terminado → Entregado. En Ver pedido se revisan materiales y el propietario confirma el precio acordado, termina y registra entrega. Los cerrados quedan protegidos y retienen un caso por detalle con sus materiales/cantidades/costos estimados y precio. Consulta/revisión de casos y [recuperación de casos 5B](docs/FASE-5B.md) implementadas. Nueva cotización/Editar → Describe el mueble → Buscar casos similares, sin guardar antes. Hasta tres referencias; el mejor vecino es la propuesta principal. Se puede elegir, confirmar y editar sus materiales/costos, o llenar manualmente. Los 35 antecedentes iniciales se consultan con el mismo botón, sin otro modo; conservan internamente su procedencia y estado de revisión. Selección editable y comparación económica de 5C implementadas. Sin descuentos automáticos de existencias. Pagos efectivo/QR implementado exclusivamente para propietario.

La aplicación usa AdminLTE 3 y Bootstrap 4, español, America/La_Paz, moneda fija BOB/Bs, paleta menta y temas claro/oscuro. Las operaciones sensibles mantienen autorización en servidor, servicios, transacciones, auditoría e idempotencia.

## Verificación y documentación

La documentación activa está organizada en [docs/README.md](docs/README.md). Los archivos sustituidos de código/documentación se respaldaron y retiraron; véase [la limpieza](docs/LIMPIEZA-ARCHIVOS-2026-11-09.md).

```powershell
php artisan test
composer check-platform-reqs
```

El escenario principal de navegador se ejecuta con `npm run test:browser` y utiliza Edge/Playwright con SQLite temporal. PHPUnit usa SQLite aislado, no la base operativa. La prueba InstalacionSeedTest cubre migrate --seed, creación interactiva del propietario, repetición, desactivación y exclusión de ejemplos en producción. Los escenarios de navegador están en tests/browser y usan bases temporales.

- [Instalación con un comando](docs/INSTALACION-CON-SEED.md).
- [Informe general de módulos y estado del 5 de noviembre](docs/INFORME-GENERAL-2026-11-05.md).
- [Diccionario del esquema de ese corte](docs/DICCIONARIO-BD-ACTUAL-2026-11-05.md).
- [Seeders y procedencia de ejemplos](docs/DATOS-EJEMPLO.md).
- [Convenciones de código](docs/CONVENCIONES-CODIGO.md).

AGENTS.md y REQUISITOS.md recogen las decisiones acumuladas. No modificar el sistema escolar ni avanzar a otra fase sin autorización.

Entrega actual: [5B, recuperación de similares](docs/FASE-5B.md). 259 pruebas PHP/2463 aserciones y 117 comprobaciones de navegador. MySQL sin cambios en esta fase, 29 tablas/25 migraciones; no hay migraciones nuevas para 5B.

Cierre funcional actual: [fase 5C](docs/FASE-5C.md), comparación histórica/actual para la misma cantidad y revisión de diferencias de lote, sin nuevos campos obligatorios.

Entrega actual: [6A — Pagos](docs/FASE-6A.md), efectivo/QR y edición auditada solo para propietario. Precio, pagos registrados y saldo por pedido; entrega con advertencia sin bloqueo. MySQL verificado con 30 tablas/26 migraciones/53 FK. No se generaron pagos de ejemplo automáticamente.

## Reportes administrativos — fase 6B

[Entrega y verificaciones](docs/FASE-6B.md): cotizaciones, pedidos, pagos recibidos y saldos actuales con filtros, pantalla y PDF imprimible. Solo propietario con permisos correspondientes. Incluye todos los registros del sistema, sin confundir precios con cobros o ganancias verificadas. Sin migraciones o cargas adicionales; usa las 30 tablas y 26 migraciones actuales.

Edición directa del pedido (16/11/2026): la misma cotización se actualiza sin originales ni revisiones comerciales. Guardar restablece su aprobación; el acuerdo y saldo solo cambian al aprobar y confirmar aceptación. Esquema consolidado: 31 tablas y 27 migraciones. [Detalle](docs/EDICION-DIRECTA-COTIZACION.md).
