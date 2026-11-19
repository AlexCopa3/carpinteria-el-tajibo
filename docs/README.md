# Documentación del proyecto

Índice actualizado el 16 de noviembre de 2026. Las decisiones funcionales permanecen en [REQUISITOS](../REQUISITOS.md) y las instrucciones de trabajo en [AGENTS](../AGENTS.md).

| Documento | Para qué sirve |
|---|---|
| [Cotización con el mismo ID](EDICION-DIRECTA-COTIZACION.md) | Reapertura, aprobación y aceptación bajo el ID original, historial, migración aditiva y pruebas del 16/11. |
| [Estado actual](INFORME-ESTADO-ACTUAL.md) | Punto de entrada al estado vigente y límites de las fases. |
| [Instalación](INSTALACION-CON-SEED.md) | Preparar otra computadora y cargar ejemplos con migrate --seed. |
| [Arquitectura](ARQUITECTURA.md) | Stack, organización y servicios activos. |
| [Convenciones de código](CONVENCIONES-CODIGO.md) | Patrones escolares conservados y adaptaciones de seguridad. |
| [Datos](DISENO-DATOS.md) | Relaciones y reglas de conservación. |
| [Diccionario MySQL, corte 5/11](DICCIONARIO-BD-ACTUAL-2026-11-05.md) | Columnas/FK del corte histórico; las cuatro nuevas columnas de uso se detallan en la entrega 4C. |
| [Permisos](PERMISOS.md) | Roles editables y excepciones individuales permitir/denegar. |
| [Implementación de permisos](INFORME-PERMISOS-GENERALES-INDIVIDUALES.md) | Aplicación autorizada, respaldo, ensayo MySQL, pruebas y estado final del 13/11. |
| [Interfaz](DISENO-INTERFAZ.md) | Formularios, navegación, paleta y temas claro/oscuro. |
| [Ejemplos](CARGA-DATOS.md) | Seeders por módulo, repetición y procedencia ficticia. |
| [Verificación](VERIFICACION.md) | Pruebas recientes y sus límites. |
| [Informe general, corte 5/11](INFORME-GENERAL-2026-11-05.md) | Inventario de módulos/migraciones y cifras de ese día. |
| [Entrega 4B](FASE-4B.md) | Alcance aprobado de pedidos y planificación, con su evidencia original. |
| [Entrega 4C](FASE-4C.md) | Materiales utilizados: implementación, migración, respaldo, ensayo y pruebas del 9/11. |
| [Diseño aprobado 4C](PROPUESTA-FASE-4C.md) | Propuesta aprobada por el usuario y aplicada; consultar la entrega para resultados. |
| [Entrega 4D](FASE-4D.md) | Validación de materiales, cierre y entrega; respaldo, MySQL y pruebas del 9/11. |
| [Diseño aprobado 4D](PROPUESTA-FASE-4D.md) | Diseño aprobado e implementado; consultar la entrega para resultados. |
| [Entrega 5A](FASE-5A.md) | Casos/materiales, 35 ejemplos, retención, revisión, respaldo y pruebas. |
| [Entrega 6B: Reportes](FASE-6B.md) | Cotizaciones, pedidos, pagos/saldos, filtros y PDF; propietario, solo lectura. |
| [Entrega 6A: Pagos](FASE-6A.md) | Pagos editables, saldo, privacidad y verificación de respaldo/ensayo. |
| [Propuesta 6A: Pagos](PROPUESTA-FASE-6A-PAGOS.md) | Diseño aprobado y aplicado; efectivo/QR, edición y entrega con advertencia. |
| [Entrega 5C](FASE-5C.md) | Selección editable, comparación económica para igual cantidad y aviso por cambio de lote. |
| [Cotizar con casos](FLUJO-COTIZACION-CON-CASOS.md) | Buscar antes de guardar, elegir principal/alternativas y editar la propuesta. |
| [Entrega 5B](FASE-5B.md) | Consulta experimental, perfil fijo, permisos, pruebas y capturas. |
| [Diseño aprobado 5B](PROPUESTA-FASE-5B.md) | Propuesta histórica aprobada e implementada; resultados en la entrega. |
| [Diseño fase 5](PROPUESTA-FASE-5.md) | 5A–5C implementadas; comparación económica y selección editable cerradas. |
| [35 casos históricos iniciales](CATALOGO-CASOS-HISTORICOS.md) | Datos ficticios de La Paz/El Alto ya cargados, pendientes de revisión humana. |
| [Limpieza](LIMPIEZA-ARCHIVOS-2026-11-09.md) | Archivos retirados, justificación y comprobaciones. |
| [Archivo histórico](ARCHIVO-HISTORICO.md) | Ubicación del respaldo de informes/capturas sustituidos. |

Los informes históricos se respaldaron fuera de la documentación activa para no confundirlos con el estado vigente. El archivo privado no es necesario para ejecutar la aplicación ni contiene una autorización para ejecutar sus antiguos scripts.

- [Edición directa de la cotización](EDICION-DIRECTA-COTIZACION.md): retirada del mecanismo de revisiones, esquema consolidado y verificaciones del 16/11/2026.
