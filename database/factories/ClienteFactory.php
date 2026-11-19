<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'nombre' => ''.fake()->randomElement(['Lucía Quispe Flores', 'Diego Mamani Choque', 'Valeria Condori Rojas', 'Andrés Flores Gutiérrez', 'Camila Vargas Soria', 'Mateo Huanca Pérez']),
            'tipo' => 'persona',
            'telefono' => '+591 7'.fake()->numerify('#######'),
            'email' => null,
            'direccion' => fake()->randomElement(['La Paz · Miraflores', 'El Alto · Ciudad Satélite', 'La Paz · Villa Copacabana', 'El Alto · Villa Bolívar', 'La Paz · San Antonio']).'',
            'observaciones' => '',
            'activo' => true,
        ];
    }
}
