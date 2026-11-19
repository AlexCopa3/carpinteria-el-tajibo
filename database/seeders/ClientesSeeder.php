<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Services\AuditoriaService;
use Illuminate\Database\Seeder;

class ClientesSeeder extends Seeder
{
    public function run(array &$carga = []): void
    {
        if (! isset($carga['lote'], $carga['propietario'])) {
            throw new \RuntimeException('Ejecuta DatosSistemaSeeder para respetar el orden y el control de duplicados.');
        }
        $propietario = $carga['propietario'];
        if ($carga['lote'] === DatosSistemaSeeder::PRIMERA_CARGA) {
            $personas = [
                ['Lucía Quispe Flores', 'La Paz · Miraflores'], ['Diego Mamani Choque', 'El Alto · Ciudad Satélite'],
                ['Valeria Condori Rojas', 'La Paz · Sopocachi'], ['Andrés Flores Gutiérrez', 'El Alto · Villa Adela'],
                ['Camila Vargas Soria', 'La Paz · Villa Copacabana'], ['Mateo Huanca Pérez', 'El Alto · Villa Bolívar'],
                ['Daniela Choque Ríos', 'La Paz · San Antonio'], ['Gabriel Rojas Apaza', 'La Paz · San Pedro'],
                ['Mariana Soria Lima', 'El Alto · Río Seco'], ['Sebastián Pérez Condori', 'El Alto · Santiago II'],
                ['Paola Apaza Flores', 'La Paz · Villa Fátima'], ['Renato Gutiérrez Mamani', 'El Alto · Villa Dolores'],
            ];
            $clientes = [];
            foreach ($personas as $indice => [$nombre,$zona]) {
                $cliente = Cliente::factory()->create(['nombre' => $nombre, 'telefono' => sprintf('+591 7001%04d', $indice + 1), 'email' => null, 'direccion' => $zona]);
                app(AuditoriaService::class)->registrar(null, $cliente, 'creado', [], app(AuditoriaService::class)->datos($cliente));
                $clientes[] = $cliente;
            }

        } else {
            $personas = [
                ['María Fernanda Choque Vargas', 'La Paz · Obrajes'], ['José Luis Paredes Flores', 'El Alto · Villa Adela'],
                ['Fabiola Limachi Calle', 'La Paz · Miraflores'], ['Ricardo Apaza Gutiérrez', 'El Alto · 16 de Julio'],
                ['Natalia Rojas Quisbert', 'La Paz · Villa Fátima'], ['Héctor Mamani Salinas', 'El Alto · Senkata'],
                ['Alejandra Soria Céspedes', 'El Alto · Villa Bolívar'], ['Oscar Quispe Aguilar', 'La Paz · San Antonio'],
                ['Patricia Condori Mendoza', 'La Paz · Sopocachi'], ['David Flores Arce', 'El Alto · Villa Exaltación'],
                ['Carolina Pérez Huanca', 'El Alto · Ciudad Satélite'], ['Marco Antonio Choque', 'La Paz · San Pedro'],
                ['Adriana Vargas Lima', 'El Alto · Santiago II'], ['Fernando Quisbert Ríos', 'La Paz · Pampahasi'],
                ['Gabriela Calle Rojas', 'El Alto · Villa Ingenio'], ['Roberto Huanca Molina', 'El Alto · Villa Dolores'],
                ['Silvia Mamani Poma', 'La Paz · Achumani'], ['Javier Condori Salazar', 'La Paz · Munaypata'],
                ['Verónica Gutiérrez Choque', 'El Alto · Villa Tunari'], ['Ramiro Apaza Mercado', 'La Paz · Tembladerani'],
                ['Paola Limachi Suárez', 'La Paz · Villa Fátima'], ['Luis Alberto Quispe', 'El Alto · Río Seco'],
                ['Claudia Céspedes Rojas', 'El Alto · Villa Esperanza'], ['Miguel Ángel Poma', 'La Paz · Cotahuma'],
            ];
            $clientes = [];
            foreach ($personas as $indice => [$nombre,$zona]) {
                $cliente = Cliente::factory()->create(['nombre' => $nombre, 'telefono' => sprintf('+591 7102%04d', $indice + 1), 'email' => null, 'direccion' => $zona]);
                app(AuditoriaService::class)->registrar(null, $cliente, 'creado', [], app(AuditoriaService::class)->datos($cliente));
                $clientes[] = $cliente;
            }

        }
        $carga['clientes'] = $clientes;
    }
}
