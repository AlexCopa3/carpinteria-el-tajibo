<?php

namespace App\Http\Requests\Cotizaciones;

use App\Models\Cotizacion;
use App\Models\User;
use App\Support\Acceso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GuardarCotizacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cotizacion = $this->route('cotizacion');

        return Acceso::permite($this->user(), 'costos.consultar')
            && ($cotizacion instanceof Cotizacion
                ? $this->user()->can('update', $cotizacion)
                : $this->user()->can('create', Cotizacion::class));
    }

    public static function reglas(?User $usuario, bool $edicion, ?Cotizacion $cotizacion = null, mixed $detalles = []): array
    {
        $acabadosAnteriores = $edicion && $cotizacion ? $cotizacion->detalles()->pluck('acabado', 'id')->all() : [];
        $validarAcabado = function ($atributo, $valor, $fallar) use ($acabadosAnteriores, $detalles) {
            if (in_array($valor, config('tajibo.acabados'), true)) {
                return;
            }
            $indice = explode('.', $atributo)[1] ?? null;
            $detalle = is_array($detalles) ? ($detalles[$indice] ?? null) : null;
            $id = is_array($detalle) ? ($detalle['id'] ?? null) : null;
            // Permitir conservar solamente el texto anterior de este mismo detalle.
            if ((is_int($id) || is_string($id)) && isset($acabadosAnteriores[$id]) && $acabadosAnteriores[$id] === $valor) {
                return;
            }
            $fallar('Selecciona un acabado de la lista.');
        };

        return [
            'clave_registro' => $edicion ? ['prohibited'] : ['required', 'uuid'],
            'version' => $edicion ? ['required', 'integer', 'min:1'] : ['prohibited'],
            'cliente_id' => ['prohibited'],
            // La fecha se genera en servidor y no puede cambiarse desde la solicitud.
            'fecha' => ['exclude'],
            'observaciones' => ['nullable', 'string', 'max:3000'],
            'condiciones' => ['nullable', 'string', 'max:3000'],
            'recargo_porcentaje' => Acceso::permite($usuario, 'margenes.gestionar')
                ? ['nullable', 'regex:/^\d{1,5}(?:\.\d{1,4})?$/D'] : ['prohibited'],
            'detalles' => ['required', 'array', 'min:1', 'max:20'],
            'detalles.*' => ['array:id,tipo_mueble_id,material_principal_id,largo_cm,ancho_cm,alto_cm,puertas,cajones,divisiones,acabado,cantidad_muebles,observaciones,mano_obra_unitaria,mano_obra_total_aplicada,motivo_ajuste_mano_obra,otros_costos_total,precio_propuesto,referencia_caso,materiales'],
            'detalles.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'detalles.*.tipo_mueble_id' => ['required', 'integer', 'exists:tipos_mueble,id'],
            'detalles.*.material_principal_id' => ['required', 'integer', 'exists:materiales,id'],
            'detalles.*.largo_cm' => ['required'],
            'detalles.*.ancho_cm' => ['required'],
            'detalles.*.alto_cm' => ['required'],
            'detalles.*.puertas' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'detalles.*.cajones' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'detalles.*.divisiones' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'detalles.*.acabado' => ['required', 'string', 'max:150', $validarAcabado],
            'detalles.*.cantidad_muebles' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'detalles.*.observaciones' => ['nullable', 'string', 'max:2000'],
            'detalles.*.mano_obra_unitaria' => ['required'],
            'detalles.*.mano_obra_total_aplicada' => ['nullable'],
            'detalles.*.motivo_ajuste_mano_obra' => ['nullable', 'string', 'max:2000'],
            'detalles.*.otros_costos_total' => ['required'],
            'detalles.*.precio_propuesto' => ['nullable'],
            'detalles.*.referencia_caso' => ['nullable', 'string', 'max:20000'],
            'detalles.*.materiales' => ['required', 'array', 'min:1', 'max:50'],
            'detalles.*.materiales.*' => ['array:id,material_id,componente,cantidad_unitaria,cantidad_total_aplicada,costo_unitario_estimado,estado_costo,fuente_costo,motivo_ajuste_cantidad,retirar,motivo_retiro'],
            'detalles.*.materiales.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'detalles.*.materiales.*.material_id' => ['required', 'integer', 'exists:materiales,id'],
            'detalles.*.materiales.*.componente' => ['required', 'string', 'max:100'],
            'detalles.*.materiales.*.cantidad_unitaria' => ['required'],
            'detalles.*.materiales.*.cantidad_total_aplicada' => ['nullable'],
            'detalles.*.materiales.*.costo_unitario_estimado' => ['nullable'],
            'detalles.*.materiales.*.estado_costo' => ['required', 'in:conocido,pendiente'],
            'detalles.*.materiales.*.fuente_costo' => ['required', 'in:referencia_catalogo,estimacion_manual'],
            'detalles.*.materiales.*.retirar' => ['sometimes', 'boolean'],
            'detalles.*.materiales.*.motivo_retiro' => ['nullable', 'string', 'max:2000'],
            'detalles.*.materiales.*.motivo_ajuste_cantidad' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function rules(): array
    {
        $cotizacion = $this->route('cotizacion');

        return self::reglas($this->user(), $cotizacion instanceof Cotizacion,
            $cotizacion instanceof Cotizacion ? $cotizacion : null, $this->input('detalles', []));
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $permitidos = ['_token', '_method', 'desde', ...array_filter(array_keys($this->rules()), fn ($key) => ! str_contains($key, '.'))];
            foreach (array_diff(array_keys($this->all()), $permitidos) as $campo) {
                $validator->errors()->add($campo, 'Este campo no se permite en una cotización preliminar.');
            }
        }];
    }

    public function attributes(): array
    {
        return ['cliente_id' => 'cliente', 'detalles' => 'muebles', 'detalles.*.acabado' => 'acabado', 'detalles.*.materiales' => 'materiales del mueble'];
    }
}
