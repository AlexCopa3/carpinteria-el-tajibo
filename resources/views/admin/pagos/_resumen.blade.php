<div class="row payment-summary" aria-label="Resumen de pagos del pedido">
    @foreach(['precio' => 'Precio acordado', 'pagado' => 'Pagos registrados', 'saldo' => 'Saldo según registros'] as $campo => $titulo)
        <div class="col-12 col-md-4 mb-3">
            <div class="payment-summary-item border rounded p-3 h-100"><h3 class="h6">{{ $titulo }}</h3><p class="h4 mb-0" data-pago-{{ $campo }}>Bs {{ App\Support\Decimal::mostrar($resumen[$campo], 2) }}</p></div>
        </div>
    @endforeach
</div>
<p class="small text-muted">Estado de cobro: <strong>{{ $resumen['estado'] }}</strong>. El saldo considera únicamente los pagos registrados en el sistema.</p>
