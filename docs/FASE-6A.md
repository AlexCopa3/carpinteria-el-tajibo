# Entrega 6A — Pagos de pedidos

10 de noviembre de 2026. Diseño aprobado: [propuesta 6A](PROPUESTA-FASE-6A-PAGOS.md). **Estado: implementada y aplicada a MySQL, con verificaciones aprobadas.**

## Funcionalidad

Pagos en el menú del propietario y acceso Ver pagos/Registrar pago desde Ver pedido. Listado filtrable por pedido/medio, formularios Registrar/Editar y ficha Ver con resumen y responsables. No hay Eliminar ni Anular.

Campos de llenado: pedido, fecha, importe y efectivo/QR. Referencia y observaciones opcionales; al editar, motivo breve y control de versión. Si entra desde pedido, se selecciona automáticamente. No se pide tipo Anticipo/Parcial/Saldo: el sistema calcula Sin pagos, Pago parcial o Pagado.

Registro exclusivamente de importes ya recibidos; no cobra QR ni se conecta a bancos. Precio acordado se conserva. Pagado y saldo se calculan con los registros, sin inferir cobros fuera del sistema ni crear pagos ficticios de pedidos antiguos.

El propietario puede corregir importe, fecha, medio, referencia, observaciones o pedido asociado. Se recalculan origen/destino, se conserva el registrador original y auditoría anterior/nueva. Las pantallas obsoletas se rechazan. No se guardan saldos duplicados en pedidos.

Entrega con saldo permitida: se muestra saldo pendiente en revisión final y en confirmación; tras entregar, servidor informa el saldo actual. No cambia la fabricación ni exige pago completo. Una corrección posterior que deje saldo no reabre el pedido entregado.

Maestros sin botones, listados, cobros/saldos en HTML/JSON o acceso directo. Permisos asignados por error no evitan la comprobación de propietario. Precio acordado visible de pedidos conserva su acceso anterior.

## Esquema y archivos

Nueva tabla pagos: id automático; pedido_id FK; fecha; importe DECIMAL(18,2); medio; referencia/observaciones opcionales; registrado_por_id FK; version; clave_registro UUID único; hash_solicitud; timestamps. MySQL comprueba importe positivo, versión positiva y medio permitido. Dos FK restrictivas. Sin modificación de columnas/tablas existentes.

| Archivo/grupo | Responsabilidad |
|---|---|
| database/migrations/2026_11_10_200000_create_pagos_table.php | Única migración nueva. |
| app/Models/Pago.php, Pedido.php | Relación de pagos/pedido/registrador y auditoría. |
| app/Policies/PagoPolicy.php, AppServiceProvider.php | Propietario activo, capacidades y tabla disponible. |
| app/Http/Requests/Pagos/GuardarPagoRequest.php | Campos permitidos, importe, fecha, versión y motivo. |
| app/Services/PagoService.php | Saldos exactos, bloqueos, reenvíos, edición y auditoría atómicos. |
| app/Http/Controllers/PagoController.php | index/create/store/show/edit/update. |
| resources/views/admin/pagos | index/create/edit/show y parciales form/resumen, Bootstrap 4, iconos/colores/temas existentes. |
| routes/web.php, config/navegacion.php | Seis rutas explícitas admin.pagos.*, POST create y PUT update; menú Pagos. |
| PedidoController, pedidos/show, fabricacion/_cierre | Resumen/advertencia autorizados y entrega sin bloqueo. |
| tests/Feature/PagosTest.php, tests/browser/fase4b.cjs | Pruebas funcionales y navegador aislado. |
| ConsolidacionMigracionesTest, FaseCuatroATest | Expectativa de 26 migraciones por la nueva tabla, sin debilitar las otras comprobaciones. |

No se añadieron bibliotecas, tecnologías, JS/CSS o un historial genérico. Confirmación SweetAlert2 reutiliza el manejador actual; componentes de responsables existentes. No se compiló Vite porque solo se modifican Blade/PHP y pruebas. No se modificó el sistema escolar.

## Consistencia

Dinero como strings DECIMAL y BigDecimal; no sumas mediante float. Registro/edición bloquean pedidos en orden ascendente y después el pago. Al validar el saldo, leer pagos con lockForUpdate para obtener el estado actual, no el snapshot abierto previamente por las consultas de autorización. Edición excluye su importe anterior del total.

El UUID y la huella del alta previenen doble registro. Dos envíos iguales devuelven el mismo pago/auditoría; reutilizar UUID con datos distintos se rechaza. Si el pago ya fue corregido, repetir el alta no revierte esa corrección. El retorno de una fila leída con bloqueo no usa fresh() para evitar volver a un snapshot anterior y ocultar un registro concurrente.

La versión evita pérdidas de actualización. Se rechazan sobrepagos y campos enviados de saldo, precio, roles o registrador. El motivo se normaliza y valida también en servicio. Guardado/auditoría se confirman juntos o revierten juntos.

## Respaldo y ensayo

Base confirmada carpinteria_el_tajibo, diagnóstico previo 29 tablas/25 migraciones y 11 pedidos. Permisos de pagos existentes solo en propietario. Respaldo completo **430.886 bytes**, archivo privado storage/app/private/fase6a-2026-11-10/respaldo-completo.sql.

SHA-256: da96a3fe83277c3ac9ada430e5f78fa1162de5d5f8f972a6e54f8b60b23608f8.

Se restauró en copia aislada y se compararon todas las filas y estructuras: idénticas. El ensayo final aprobado usa carpinteria_el_tajibo_ensayo_6a_20261110120831_2929d5. Copias de diagnóstico anteriores conservadas en respaldo-verificado.json, sin borrarlas o tocar el origen.

El ensayo detectó y corrigió dos efectos de snapshots InnoDB: sobrepago concurrente con lectura de saldo antigua y reenvío cuyo fresh() no veía todavía el pago de otra transacción. Esos resultados fueron fallos de ensayo, no operaciones sobre la base original. Se restauró nuevamente el mismo respaldo y las pruebas corregidas pasaron:

- Registro/reenvío, edición, reasignación entre pedidos y rechazo de versión antigua.
- Dos pagos Bs 80 ante saldo Bs 100: uno registrado, uno rechazado; saldo Bs 20.
- Mismo UUID simultáneo: ambos reciben el mismo pago, una sola creación/auditoría.
- Dos ediciones simultáneas de la misma versión: una aceptada y otra rechazada.
- CHECK MySQL rechaza importe negativo.

No se reiniciaron bases ni se eliminaron tablas/columnas. No se resembraron permisos o usuarios. Scripts privados son evidencia de una aplicación específica, no instaladores generales.

## Aplicación y estado final

Aplicada únicamente 2026_11_10_200000_create_pagos_table el 10/11/2026 a las 12:28 (America/La_Paz), con mantenimiento breve retirado al finalizar. Estado comprobado: **30 tablas, 26 migraciones y 53 FK**. Tabla pagos vacía; no se generaron cobros ficticios. Comparación de todas las tablas anteriores: filas y estructuras conservadas; solo se agregó la tabla y la entrada correspondiente en migrations. Usuarios, contraseñas, roles, permisos, precios, pedidos, existencias, movimientos, casos y auditorías previas intactos.

La estructura de pagos coincide con la ensayada. Guardas previas exigieron diagnóstico sin cambios, respaldo restaurado/hash, migración idéntica al ensayo, suite completa y navegador aprobados. No se ejecutó un migrate general ni ningún seeder. Las 25 migraciones anteriores conservan sus hashes.

Verificación MySQL posterior, sin escrituras: controlador/listado/formulario y resumen por propietario correctos; maestro denegado y pedido sin datos de cobros. No equivale a una sesión HTTP autenticada sobre MySQL; el recorrido HTTP de navegador se ejecutó sobre SQLite temporal. El servidor HTTP en 127.0.0.1:8000 no estaba comprobable al cerrar; no se inició ni modificó su configuración. El sistema no quedó en mantenimiento.

## Pruebas

Primera comprobación de Pagos: **13 pruebas/94 aserciones**, aprobadas en SQLite aislado. Edge/Playwright: **117 comprobaciones**, aprobadas sin errores JavaScript, con 95 capturas acumuladas. Suite completa final: **259 pruebas/2463 aserciones, 331,28 s**, aprobadas. Las comprobaciones de esquema verifican las 26 migraciones y el orden correcto de reversión en SQLite aislado.

El navegador cubre efectivo/QR, cancelación, conservación de errores, registro, reenvío, edición, versión obsoleta, sobrepago, completar saldo, corrección posterior a entrega y privacidad de maestros. 1440/1024/390 px y oscuro. Las escrituras usan SQLite temporal; no registran pagos sobre pedidos locales.

Evidencia privada: storage/app/private/fase6a-2026-11-10. Logs, respaldo/restauración, estructura ensayada, carreras y aplicación se conservan ahí.

- [Resultado navegador](capturas/fase6a/resultado-browser.json).
- [Registrar escritorio](capturas/fase6a/pagos-create-1440.png).
- [Registrar móvil](capturas/fase6a/pagos-create-390.png).
- [Tema oscuro](capturas/fase6a/pagos-create-oscuro-390.png).
- [Editar](capturas/fase6a/pagos-edit-390.png).
- [Ver pago](capturas/fase6a/pagos-show-1440.png).
- [Saldo en pedido entregado](capturas/fase6a/pagos-pedido-entregado.png).

## Límites

Módulo manual de pagos recibidos, no sistema contable. Sin transferencias, devoluciones, anulaciones, cobro automático, comprobante fiscal, caja, gastos, ganancias o reportes nuevos. Sin descuentos de existencias ni alteraciones de precios acordados/casos históricos. Esas funciones no quedan autorizadas por esta entrega.
