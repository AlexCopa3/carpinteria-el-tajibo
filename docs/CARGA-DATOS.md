# Carga de datos por módulo

> Estado vigente al 17/11/2026: el historial está distribuido desde 2020. Se mantienen 47 pedidos, 59 cotizaciones, 87 casos y 36 clientes; ahora hay 82 pagos. Estados: 4 aceptados, 4 en fabricación, 3 terminados y 36 entregados. HistorialCarpinteriaSeeder forma parte de la instalación local, con repetición idempotente. Véase [historial y verificaciones](HISTORIAL-CARPINTERIA.md).


Estado vigente: 16/11/2026. Los datos se cargan en la misma base local por decisión del usuario y conservan internamente su origen simulado. No acreditan operaciones reales ni revisión humana.

## Instalación y repetición

Con `.env` configurado para `carpinteria_el_tajibo` y `APP_ENV=local`:

```powershell
php artisan migrate --seed
```

Si falta el propietario se solicita su alta interactiva; si existe se conserva su cuenta y contraseña. `TAJIBO_DATOS_SISTEMA=false` excluye la carga. Producción no carga estos datos desde DatabaseSeeder. Véase [instalación](INSTALACION-CON-SEED.md).

Repetir no borra registros, no repone datos eliminados y no sobrescribe ediciones. Los módulos dependen del coordinador y de sus lotes de auditoría; no deben ejecutarse de forma aislada. Para un traslado exacto entre computadoras se necesita exportación/importación de la base.

## Organización

- PermisosSeeder, UnidadesSeeder, TiposMuebleSeeder y CasosPermisosSeeder preparan los catálogos técnicos.
- DatosSistemaSeeder coordina CategoriasSeeder, ClientesSeeder, MaterialesSeeder, ExistenciasMaterialesSeeder, CotizacionesSeeder y PedidosSeeder.
- CasosHistoricosSeeder completa 87 casos a través de los servicios de cotización, pedidos, fabricación y retención. FabricacionSeeder prepara los dos cierres anteriores y comparte su procedimiento de cierre.
- UsuariosSeeder, PagosSeeder y AjustesSeeder completan los datos de sus módulos. HistorialCarpinteriaSeeder organiza el período 2020–2026 y completa los pagos de los trabajos cerrados.
- Factories: User, Cliente, Categoria, Material y CasoHistorico. Las factories de pruebas no sustituyen los flujos de negocio.

## Contenido vigente

| Entidad | Registros |
|---|---:|
| Usuarios | 3 |
| Clientes | 36 |
| Categorías | 5 |
| Materiales | 26 |
| Cotizaciones | 59 |
| Pedidos | 47 |
| Pagos | 82 |
| Casos históricos | 87 |
| Materiales de casos | 406 |

Todos los casos tienen detalle de pedido. 39 pedidos aportan los casos: 12 con uno, 12 con dos, 9 con tres y 6 con cuatro. Los otros ocho pedidos siguen abiertos. Un caso es una línea de muebles iguales; su cantidad física se registra dentro del caso.

Las [25 plantillas](CATALOGO-CASOS-HISTORICOS.md) del JSON se reutilizan para distintos pedidos; no se presentan como 87 diseños únicos. Los casos nuevos conservan materiales, cantidades utilizadas, costos estimados y precio del grupo. Los totales del grupo no se multiplican nuevamente por la cantidad de muebles. Materiales principales: Madera y Melamina, sin MDF.

La repetición se reconoce mediante auditoría. Conservar las claves históricas `datos_bolivianos_20261105_v1`, `datos_bolivianos_20261105_v2`, la acción `datos_ejemplo_cargados` y el lote nuevo `casos_desde_pedidos_87_v1`. No borrar marcadores para intentar repetir la carga. Los 25 vínculos técnicos del perfil se mantienen, sin modificar pesos, rangos, k=3 ni umbral=80.

La búsqueda única «Buscar casos similares» permite reutilizar una propuesta editable antes de aprobar la nueva cotización. Los reportes y el dashboard muestran registros del sistema; no acreditan ventas verificadas. Véase [informe de aplicación y pruebas](INFORME-CASOS-PEDIDOS-87.md).
