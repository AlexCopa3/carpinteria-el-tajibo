<?php

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'decimal' => 'El campo :attribute debe tener entre :decimal decimales.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'integer' => 'El campo :attribute debe ser un entero.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha igual o posterior a :date.',
    'date_format' => 'El campo :attribute debe tener el formato :format.',
    'custom' => [
        'fecha_prevista_entrega' => [
            'after_or_equal' => 'La entrega prevista debe ser hoy o una fecha posterior.',
        ],
    ],
    'date' => 'El campo :attribute debe ser una fecha válida.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'unique' => 'El valor de :attribute ya está registrado.',
    'exists' => 'El valor seleccionado para :attribute no es válido.',
    'in' => 'El valor seleccionado para :attribute no es válido.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'array' => 'El campo :attribute debe ser una lista.',
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
    ],
    'max' => [
        'numeric' => 'El campo :attribute no debe superar :max.',
        'string' => 'El campo :attribute no debe superar :max caracteres.',
        'array' => 'El campo :attribute no debe superar :max elementos.',
    ],
    'prohibited' => 'El campo :attribute no se permite en esta operación.',
    'password' => [
        'letters' => 'La contraseña debe contener letras.',
        'mixed' => 'La contraseña debe contener mayúsculas y minúsculas.',
        'numbers' => 'La contraseña debe contener al menos un número.',
        'symbols' => 'La contraseña debe contener al menos un símbolo.',
    ],
    'attributes' => [
        'name' => 'nombre', 'nombre' => 'nombre', 'email' => 'correo electrónico', 'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña', 'categoria_id' => 'categoría',
        'unidad_id' => 'unidad de inventario', 'precio_referencia' => 'precio de referencia',
        'stock_minimo' => 'umbral de stock', 'material_id' => 'material', 'presentacion_id' => 'presentación de compra',
        'cantidad_comprada' => 'cantidad comprada', 'costo_presentacion' => 'costo por presentación',
        'estado_costo' => 'situación del costo', 'motivo' => 'motivo', 'activo' => 'estado',
        'telefono' => 'teléfono', 'direccion' => 'dirección', 'observaciones' => 'observaciones', 'rol' => 'rol',
    ],
];
