<?php

namespace App\Services;

use App\Models\Cotizacion;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use App\Support\Acceso;
use App\Support\Decimal;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReporteService
{
    public const TIPOS = ['cotizaciones' => 'Cotizaciones', 'pedidos' => 'Pedidos', 'pagos' => 'Pagos recibidos', 'saldos' => 'Saldos actuales'];

    public function autorizar(User $actor, ?string $tipo = null): void
    {
        $actor = $actor->fresh();
        abort_unless(Acceso::permite($actor, 'reportes.administrativos'), 403);
        if ($tipo !== null) {
            abort_unless(isset(self::TIPOS[$tipo]), 404);
            abort_unless(Acceso::permite($actor, $tipo === 'cotizaciones' ? 'cotizaciones.consultar' : 'pedidos.consultar'), 403);
            if (in_array($tipo, ['pagos', 'saldos'], true)) {
                abort_unless(Acceso::permite($actor, 'pagos.consultar'), 403);
            }
        }
    }

    public function generar(User $actor, string $tipo, array $filtros): array
    {
        $this->autorizar($actor, $tipo);
        if (array_diff(array_keys($filtros), ['desde', 'hasta', 'estado', 'medio', 'page'])) {
            throw ValidationException::withMessages(['filtros' => 'El reporte contiene filtros no permitidos.']);
        }
        $estados = match ($tipo) {
            'cotizaciones' => ['borrador' => 'Borrador', 'pendiente_costos' => 'Costos pendientes', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada'],
            'pedidos' => ['aceptado' => 'Aceptado', 'en_fabricacion' => 'En fabricación', 'terminado' => 'Terminado', 'entregado' => 'Entregado'],
            'saldos' => ['pendiente' => 'Con saldo pendiente', 'pagado' => 'Pagado', 'sin_pagos' => 'Sin pagos'],
            default => [],
        };
        $filtros = Validator::make($filtros, [
            'desde' => $tipo === 'saldos' ? 'prohibited' : 'nullable|date_format:Y-m-d',
            'hasta' => $tipo === 'saldos' ? 'prohibited' : 'nullable|date_format:Y-m-d',
            'estado' => $estados ? ['nullable', Rule::in(array_keys($estados))] : ['prohibited'],
            'medio' => $tipo === 'pagos' ? ['nullable', 'in:efectivo,qr'] : ['prohibited'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
        ])->validate();
        if ($tipo !== 'saldos') {
            $filtros['desde'] = $filtros['desde'] ?? today()->startOfMonth()->format('Y-m-d');
            $filtros['hasta'] = $filtros['hasta'] ?? today()->format('Y-m-d');
            if ($filtros['hasta'] < $filtros['desde']) {
                throw ValidationException::withMessages(['hasta' => 'La fecha final debe ser igual o posterior a la inicial.']);
            }
        }
        $filas = [];
        $totales = [];
        $etiquetas = [];
        if ($tipo === 'cotizaciones') {
            $columnas = ['ID', 'Fecha', 'Estado', 'Precio aprobado Bs'];
            $verCostos = Acceso::permite($actor->fresh(), 'costos.consultar');
            if ($verCostos) {
                $columnas[] = 'Costo estimado Bs';
            }
            $aprobado = BigDecimal::zero();
            $pendientes = 0;
            $rechazadas = 0;
            $consulta = Cotizacion::whereDate('fecha', '>=', $filtros['desde'])->whereDate('fecha', '<=', $filtros['hasta']);
            if (! empty($filtros['estado'])) {
                $consulta->where('estado', $filtros['estado']);
            }
            foreach ($consulta->orderByDesc('id')->get() as $cotizacion) {
                $rechazada = $cotizacion->estado === 'rechazada';
                $confirmado = ! $rechazada && $cotizacion->aprobado_at !== null && $cotizacion->precio_final !== null;
                if ($confirmado) {
                    $aprobado = $aprobado->plus($cotizacion->precio_final);
                } elseif ($rechazada) {
                    $rechazadas++;
                } else {
                    $pendientes++;
                }
                $fila = [$cotizacion->id, $cotizacion->fecha->format('d/m/Y'), $estados[$cotizacion->estado] ?? $cotizacion->estado, $rechazada ? 'Rechazada' : ($confirmado ? Decimal::mostrar($cotizacion->precio_final, 2) : 'Sin aprobar')];
                if ($verCostos) {
                    $fila[] = $cotizacion->costo_total === null ? 'Pendiente' : Decimal::mostrar($cotizacion->costo_total, 2);
                }
                $filas[] = $fila;
            }
            $totales = ['Cotizaciones' => count($filas), 'Sin precio aprobado' => $pendientes, 'Precios aprobados (Bs)' => Decimal::mostrar($aprobado, 2)];
            if ($rechazadas > 0) {
                $totales['Rechazadas'] = $rechazadas;
            }
            $nota = 'Los precios aprobados no representan cobros. Los costos son estimaciones; un costo pendiente no se considera cero.';
        } elseif ($tipo === 'pedidos') {
            $columnas = ['ID', 'Cliente al aceptar', 'Aceptación', 'Entrega prevista', 'Estado', 'Precio acordado Bs'];
            $suma = BigDecimal::zero();
            $consulta = Pedido::whereDate('fecha_aceptacion', '>=', $filtros['desde'])->whereDate('fecha_aceptacion', '<=', $filtros['hasta']);
            if (! empty($filtros['estado'])) {
                $consulta->where('estado', $filtros['estado']);
            }
            foreach ($consulta->orderByDesc('id')->get() as $pedido) {
                $suma = $suma->plus($pedido->precioVigente());
                $filas[] = [$pedido->id, $pedido->datos_cliente_acordados['nombre'], $pedido->fecha_aceptacion->format('d/m/Y'), $pedido->fecha_prevista_entrega?->format('d/m/Y') ?? 'Sin definir', $pedido->estado_nombre, Decimal::mostrar($pedido->precioVigente(), 2)];
            }
            $totales = ['Pedidos' => count($filas), 'Precios acordados (Bs)' => Decimal::mostrar($suma, 2)];
            $nota = 'El período corresponde a la aceptación del pedido, no a su entrega. Los precios acordados no son pagos recibidos.';
        } elseif ($tipo === 'pagos') {
            $columnas = ['ID pago', 'Pedido', 'Cliente', 'Fecha del pago', 'Medio', 'Importe Bs'];
            $suma = BigDecimal::zero();
            $consulta = Pago::with('pedido')->whereDate('fecha', '>=', $filtros['desde'])->whereDate('fecha', '<=', $filtros['hasta']);
            if (! empty($filtros['medio'])) {
                $consulta->where('medio', $filtros['medio']);
            }
            foreach ($consulta->orderByDesc('id')->get() as $pago) {
                $suma = $suma->plus($pago->importe);
                $filas[] = [$pago->id, $pago->pedido_id, $pago->pedido->datos_cliente_acordados['nombre'], $pago->fecha->format('d/m/Y'), $pago->medio === 'qr' ? 'QR' : 'Efectivo', Decimal::mostrar($pago->importe, 2)];
            }
            $totales = ['Pagos' => count($filas), 'Recibido en el período (Bs)' => Decimal::mostrar($suma, 2)];
            $nota = 'El período corresponde a la fecha del pago aunque su pedido sea anterior. Se usan los valores vigentes: una corrección puede modificar este resumen.';
        } else {
            $columnas = ['Pedido', 'Cliente', 'Estado del pedido', 'Precio acordado Bs', 'Pagos registrados Bs', 'Saldo actual Bs'];
            $precioTotal = BigDecimal::zero();
            $pagadoTotal = BigDecimal::zero();
            $saldoTotal = BigDecimal::zero();
            foreach (Pedido::with('pagos')->orderByDesc('id')->get() as $pedido) {
                $pagado = BigDecimal::zero();
                foreach ($pedido->pagos as $pago) {
                    $pagado = $pagado->plus($pago->importe);
                }
                $saldo = BigDecimal::of($pedido->precioVigente())->minus($pagado);
                $estado = $filtros['estado'] ?? '';
                if (($estado === 'pendiente' && ! $saldo->isPositive()) || ($estado === 'pagado' && ! $saldo->isZero()) || ($estado === 'sin_pagos' && ! $pagado->isZero())) {
                    continue;
                }
                $precioTotal = $precioTotal->plus($pedido->precioVigente());
                $pagadoTotal = $pagadoTotal->plus($pagado);
                $saldoTotal = $saldoTotal->plus($saldo);
                $filas[] = [$pedido->id, $pedido->datos_cliente_acordados['nombre'], $pedido->estado_nombre, Decimal::mostrar($pedido->precioVigente(), 2), Decimal::mostrar($pagado, 2), Decimal::mostrar($saldo, 2)];
            }
            $totales = ['Pedidos' => count($filas), 'Acordado (Bs)' => Decimal::mostrar($precioTotal, 2), 'Pagos registrados (Bs)' => Decimal::mostrar($pagadoTotal, 2), 'Saldo actual (Bs)' => Decimal::mostrar($saldoTotal, 2)];
            $nota = 'Saldo actual de todos los pedidos seleccionados, incluidos los antiguos y entregados; considera pagos de cualquier fecha. No es un saldo histórico al cierre de un período ni certifica cobros fuera del sistema.';
        }
        if ($tipo !== 'saldos') {
            $etiquetas[] = 'Desde '.Carbon::createFromFormat('Y-m-d', $filtros['desde'])->format('d/m/Y').' hasta '.Carbon::createFromFormat('Y-m-d', $filtros['hasta'])->format('d/m/Y');
        } else {
            $etiquetas[] = 'Saldos actuales al consultar';
        }
        if (! empty($filtros['estado'])) {
            $etiquetas[] = $estados[$filtros['estado']];
        }
        if (! empty($filtros['medio'])) {
            $etiquetas[] = $filtros['medio'] === 'qr' ? 'QR' : 'Efectivo';
        }

        return ['tipo' => $tipo, 'titulo' => self::TIPOS[$tipo], 'columnas' => $columnas, 'filas' => $filas, 'totales' => $totales,
            'filtros' => $filtros, 'estados' => $estados, 'etiquetas' => $etiquetas, 'nota' => $nota, 'generado_at' => now()->format('d/m/Y H:i')];
    }
}
