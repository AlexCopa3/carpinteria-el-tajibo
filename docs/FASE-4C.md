# Fase 4C — Registro de materiales utilizados

Entrega del 9 de noviembre de 2026, implementada tras la aprobación del usuario. Se mantienen las convenciones del sistema escolar, sin modificarlo. La fase 4D continúa pendiente de autorización.

## Funcionalidad y acceso

Desde **Pedidos → Ver → Registrar materiales utilizados**, o desde la consulta de planificación, el propietario y los maestros autorizados registran cantidades durante **En fabricación**. No se crea otro menú ni un asistente de pasos.

El formulario agrupa materiales por detalle de mueble. Normalmente solo se completa Utilizado total; unidad y planificación son de lectura y la diferencia se calcula automáticamente. Son cantidades totales del grupo, sin multiplicar nuevamente por muebles. Copiar cantidades planificadas rellena solo vacíos; requiere revisar y guardar. Las observaciones son opcionales y se permite añadir un material activo no planificado. La consulta muestra cantidades, diferencia, responsable y fecha del último registro efectivo.

- Vacío/NULL: Sin registrar. Cero expreso: No utilizado. No son equivalentes.
- Guardado parcial permitido. No se inventan consumos anteriores.
- Corregir una cantidad ya registrada, incluso volverla a vacío, exige un único motivo general de al menos cinco caracteres. Auditoría conserva antes/después y actor del servidor.
- Material adicional: cantidad inicial NULL y planificación 0. No se crea al dejar vacía su cantidad ni se duplica uno ya presente en ese detalle. Se conservan filas históricas por componente.
- Material inactivo anterior se conserva; no se añade uno nuevo inactivo.
- Guardar sin cambios no genera una modificación ficticia. Bloqueo transaccional y versión rechazan reenvíos obsoletos; la interfaz también evita doble envío.

La consulta exige pedidos.consultar y planificacion.consultar. Para registrar/corregir se requieren también pedidos.modificar y planificacion.modificar, cuenta activa y estado En fabricación. La política se comprueba en controlador, Form Request y servicio; los IDs, cantidades, unidades, campos permitidos y pertenencia al pedido se verifican en servidor. No se modificaron roles, permisos ni asignaciones existentes.

No se descuentan existencias ni se generan movimientos de almacén. No cambian precios, costos, ganancia, PDF, cotización aprobada o acuerdo del pedido. Esta pantalla no habilita Terminado/Entregado, validación definitiva, costos finales, pagos, casos históricos ni CBR/k-NN.

## Código y estructura

| Área | Archivos principales |
|---|---|
| Controlador explícito edit/update | app/Http/Controllers/FabricacionController.php |
| Validación compleja | app/Http/Requests/Pedidos/GuardarMaterialesUtilizadosRequest.php |
| Transacciones, versión y auditoría | app/Services/FabricacionService.php |
| Modelo y autorización | app/Models/MaterialPedido.php, app/Policies/PedidoPolicy.php |
| Vistas por módulo | resources/views/admin/fabricacion/edit.blade.php, _fila.blade.php y _resumen.blade.php |
| Consultas integradas | PedidoController, PlanificacionMaterialController y sus vistas show |
| Rutas explícitas | GET /admin/pedidos/{pedido}/fabricacion/edit y PUT /admin/pedidos/{pedido}/fabricacion; admin.fabricacion.edit/update |
| Comportamiento del formulario | resources/js/pedidos.js; compilación Vite en public/build |
| Pruebas | tests/Feature/FabricacionTest.php, tests/mysql/fabricacion-worker.php y escenario tests/browser/fase4b.cjs ampliado |

Se conservan las rutas anteriores. Hay 73 entradas de rutas, dos más que antes. No se incorporaron dependencias nuevas. Laravel 12.70.2, PHP 8.3.28 de pruebas, AdminLTE 3, Bootstrap 4, Spatie, Brick Math y Dompdf permanecen según los archivos lock. Los controladores CRUD sencillos mantienen su estructura; la operación transaccional compleja usa servicio y Form Request.

## Migración y MySQL

Se ejecutó únicamente **2026_11_09_200000_add_uso_to_materiales_pedido_table.php**. Las 21 migraciones anteriores conservan sus SHA-256. No se ejecutaron seeders sobre la base operativa.

| Nueva columna de materiales_pedido | Definición |
|---|---|
| cantidad_utilizada | DECIMAL(18,4), NULL; CHECK MySQL impide valores negativos |
| observaciones_uso | TEXT, NULL |
| uso_registrado_por_id | FK nullable a users.id, ON DELETE RESTRICT |
| uso_registrado_at | TIMESTAMP, NULL |

Conexión comprobada: MySQL local, base **carpinteria_el_tajibo**. Antes: 27 tablas, 21 migraciones y 31 materiales de pedidos. Después: **27 tablas y 22 migraciones**, sin discrepancias entre archivos e historial. Las cuatro columnas nuevas quedaron NULL en las 31 filas anteriores. Se cotejaron hashes de todos los datos anteriores proyectando sus columnas originales: usuarios, contraseñas, permisos, materiales, saldos, movimientos, cotizaciones, precios y demás registros se preservaron. La estructura final coincide con el ensayo. No se eliminaron tablas, columnas ni datos; no se utilizó migrate:fresh o db:wipe.

## Respaldo y ensayo aislado

Directorio privado de evidencias: storage/app/private/fase4c-2026-11-09/.

- Respaldo completo: respaldo-completo.sql, **374845 bytes**.
- SHA-256: **1eab73a69b62cec7958d41060a96832eb70abb65bb6608fe96e5055ce75a8b4e**.
- Restaurado y cotejado: **carpinteria_el_tajibo_ensayo_4c_20261109153926_3ce807**, con las 27 tablas y registros idénticos al origen antes del ensayo.
- Ensayo de migración: todas las columnas/filas originales preservadas, campos nuevos NULL, FK restrictiva y CHECK efectivos.
- Escrituras funcionales exclusivamente aisladas: decimales exactos, cero/pendiente, correcciones, actor, material adicional, unidad entera y restricciones SQL. Las comprobaciones transaccionales revirtieron sus escrituras; la fixture/concurrencia permanece solo en el clon.
- Concurrencia MySQL con dos procesos sobre la misma versión: un guardado aceptado y otro rechazado por versión; exactamente una fila adicional y una auditoría.
- La base operativa volvió a cotejarse con el respaldo inmediatamente antes de aplicar. La aplicación estuvo brevemente en mantenimiento y quedó disponible tras la verificación.

Evidencias privadas: respaldo-verificado.json, ensayo-aprobado.json, estructura-ensayada.json, aplicacion-verificada.json y verificacion-local.json. Los scripts de este directorio son evidencia controlada de este despliegue, no instaladores para reutilizar en otra computadora. El respaldo y el clon contienen datos: no publicarlos ni incluirlos en public.

## Pruebas ejecutadas y límites

| Comprobación | Resultado |
|---|---|
| php artisan test, SQLite aislado | **178 aprobadas, 1902 aserciones, 162.96 s** |
| FabricacionTest dentro de la suite | 16 pruebas de permisos, cantidades, correcciones, concurrencia lógica, datos ajenos, no cambios y rollback |
| Edge/Playwright, SQLite temporal aislado | **82 comprobaciones aprobadas**, sin errores JavaScript |
| MySQL en clon restaurado | 9 comprobaciones aprobadas, incluidas concurrencia real y restricciones |
| MySQL operativo después de aplicar | Formulario, Ver pedido y planificación renderizados con propietario y maestro; seis comprobaciones de solo lectura, sin cambios de datos y sin referencias de uso huérfanas |
| Recursos | Vite compilado y PHP de archivos nuevos revisado con lint/Pint |

El navegador comprobó copiado sin sobrescribir, diferencias exactas, material adicional, corrección con motivo, rechazo de versión obsoleta, SweetAlert2, precios y existencias conservados. Incluye regresión de catálogo, cotizaciones/PDF, pedidos/planificación, dashboard, menú fijo y temas. Los formularios nuevos se comprobaron a 1440, 1024 y 390 px, también en oscuro móvil, sin desborde horizontal.

Las escrituras de prueba usan SQLite temporal o el clon MySQL; no se registró uso ficticio como consumo real en la base operativa. La comprobación local posterior renderiza controladores/Blade; no equivale a una sesión HTTP real autenticada sobre MySQL. No se certifica cierre de fabricación, rendimiento productivo ni precisión de CBR/k-NN.

- [Resultado del navegador](capturas/fase4c/resultado-browser.json).
- [Formulario de escritorio](capturas/fase4c/fabricacion-edit-1440.png).
- [Formulario móvil](capturas/fase4c/fabricacion-edit-390.png).
- [Formulario oscuro móvil](capturas/fase4c/fabricacion-edit-oscuro-390.png).
- [Consulta de materiales utilizados](capturas/fase4c/fabricacion-show-1440.png).

La siguiente fase requiere revisión y aprobación expresa del usuario.
