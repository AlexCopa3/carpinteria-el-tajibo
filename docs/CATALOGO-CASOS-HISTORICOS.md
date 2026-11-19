# Plantillas de casos históricos

Las 25 plantillas de esta tabla se reutilizan para cargar 87 casos, todos vinculados a pedidos. No se insertan como casos independientes. Se conservan sus posiciones de lote como referencias técnicas del perfil fijo de similitud. Los 87 casos no representan 87 diseños distintos: cada caso es una línea de un pedido. La distribución y las verificaciones están en [el informe de carga](INFORME-CASOS-PEDIDOS-87.md). Origen simulado y revisión humana pendiente; importes ilustrativos en Bs, sin fuente comercial real. El contexto boliviano describe la plantilla y no interviene en la similitud.

Los totales corresponden al grupo completo de muebles. Tableros, piezas y accesorios por unidad tienen cantidades enteras, conforme al catálogo actual. No representan despiece geométrico ni recomendaciones validadas. Para puertas, Ancho es el espesor de la hoja en centímetros.

| Posición | Tipo | Contexto | Dimensiones cm | Material | Acabado | Muebles | Costo estimado total Bs | Precio ilustrativo total Bs |
|---|---|---|---|---|---|---:|---:|---:|
| 1 | escritorio | La Paz · Miraflores | 100 × 50 × 75 | Melamina | Melamina | 1 | 502.30 | 577.65 |
| 3 | escritorio | La Paz · Sopocachi | 140 × 60 × 75 | Madera | Barnizado natural | 1 | 937.60 | 1172.00 |
| 4 | escritorio | El Alto · Villa Adela | 110 × 55 × 75 | Melamina | Melamina | 1 | 613.50 | 797.55 |
| 6 | escritorio | El Alto · Río Seco | 130 × 60 × 75 | Madera | Barnizado natural | 1 | 1038.80 | 1246.56 |
| 7 | mesa | La Paz · Miraflores | 90 × 60 × 75 | Melamina | Melamina | 1 | 533.10 | 613.07 |
| 9 | mesa | La Paz · Sopocachi | 160 × 90 × 76 | Madera | Barnizado natural | 2 | 1816.80 | 2271.00 |
| 10 | mesa | El Alto · Villa Adela | 80 × 50 × 45 | Melamina | Melamina | 2 | 1183.60 | 1538.68 |
| 12 | mesa | El Alto · Río Seco | 180 × 90 × 76 | Madera | Barnizado natural | 1 | 1074.60 | 1289.52 |
| 13 | silla | La Paz · Miraflores | 42 × 42 × 85 | Madera | Barnizado natural | 4 | 1280.80 | 1472.92 |
| 14 | silla | El Alto · Ciudad Satélite | 45 × 45 × 90 | Madera | Barnizado natural | 6 | 2488.60 | 2986.32 |
| 15 | silla | La Paz · Sopocachi | 48 × 46 × 92 | Madera | Barnizado natural | 2 | 742.00 | 927.50 |
| 17 | silla | La Paz · San Pedro | 46 × 44 × 88 | Madera | Sin acabado | 1 | 431.40 | 496.11 |
| 18 | silla | El Alto · Río Seco | 44 × 43 × 87 | Madera | Barnizado natural | 6 | 2998.20 | 3597.84 |
| 19 | armario | La Paz · Miraflores | 90 × 50 × 180 | Melamina | Melamina | 1 | 1494.00 | 1718.10 |
| 21 | armario | La Paz · Sopocachi | 180 × 60 × 210 | Madera | Barnizado natural | 1 | 2079.80 | 2599.75 |
| 22 | armario | El Alto · Villa Adela | 80 × 45 × 170 | Melamina | Melamina | 1 | 1605.20 | 2086.76 |
| 24 | armario | El Alto · Río Seco | 200 × 60 × 220 | Madera | Barnizado natural | 1 | 2281.00 | 2737.20 |
| 25 | estante | La Paz · Miraflores | 60 × 30 × 150 | Melamina | Melamina | 1 | 837.90 | 963.59 |
| 27 | estante | La Paz · Sopocachi | 120 × 35 × 180 | Madera | Barnizado natural | 1 | 1009.20 | 1261.50 |
| 28 | estante | El Alto · Villa Adela | 70 × 30 × 120 | Melamina | Melamina | 1 | 914.10 | 1188.33 |
| 30 | estante | El Alto · Río Seco | 90 × 35 × 160 | Madera | Barnizado natural | 1 | 1160.40 | 1392.48 |
| 31 | puerta | La Paz · Miraflores | 80 × 4 × 200 | Madera | Barnizado natural | 1 | 1069.20 | 1229.58 |
| 32 | puerta | El Alto · Ciudad Satélite | 90 × 4 × 210 | Madera | Barnizado natural | 2 | 2314.20 | 2777.04 |
| 33 | puerta | La Paz · Sopocachi | 75 × 4 × 200 | Madera | Barnizado natural | 1 | 1120.00 | 1400.00 |
| 35 | puerta | La Paz · San Pedro | 100 × 5 × 210 | Madera | Sin acabado | 1 | 1139.30 | 1310.20 |

Datos detallados de cantidades y costos: [archivo del seeder](../database/seeders/data/casos_historicos.json). El archivo por sí mismo no escribe; CasosHistoricosSeeder lo carga de forma idempotente y DatabaseSeeder lo incluye en la instalación local. Los materiales se resuelven mediante relaciones del catálogo, sin asignar IDs manuales ni sobrescribir nombres/costos existentes.

Estado de carga y pruebas: [entrega 5A](FASE-5A.md). No se marcaron casos como revisados en MySQL operativo.
