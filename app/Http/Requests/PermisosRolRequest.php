<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermisosRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('rol')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('permisos')) {
            $this->merge(['permisos' => []]);
        }
    }

    public function rules(): array
    {
        $permitidos = \Spatie\Permission\Models\Permission::where('guard_name', 'web')->pluck('name')->all();

        return ['permisos' => ['present', 'array'], 'permisos.*' => ['required', 'string', 'distinct', Rule::in($permitidos)],
            'name' => ['prohibited'], 'guard_name' => ['prohibited'], 'rol' => ['prohibited']];
    }
}
