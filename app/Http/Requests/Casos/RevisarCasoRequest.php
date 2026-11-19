<?php

namespace App\Http\Requests\Casos;

use App\Models\CasoHistorico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RevisarCasoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $caso = $this->route('caso');
        $capacidad = match ($this->input('accion')) {
            'materiales' => 'validarMateriales','precio' => 'validarPrecio','descartar' => 'descartar',default => null
        };

        return $caso instanceof CasoHistorico && $capacidad && $this->user()?->can($capacidad, $caso);
    }

    public static function reglas(): array
    {
        return ['version' => 'required|integer|min:1', 'accion' => 'required|in:materiales,precio,descartar', 'confirmacion' => 'required|accepted', 'observaciones' => 'required_if:accion,descartar|nullable|string|min:5|max:2000'];
    }

    public function rules(): array
    {
        return self::reglas();
    }

    protected function passedValidation(): void
    {
        if (array_diff(array_keys($this->except('_token', '_method')), array_keys(self::reglas()))) {
            throw ValidationException::withMessages(['solicitud' => 'La solicitud contiene campos no permitidos.']);
        }
    }
}
