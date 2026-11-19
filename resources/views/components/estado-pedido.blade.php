@props(['pedido'])
@php
    $estilos = [
        'aceptado' => ['is-accepted', 'far fa-clock'],
        'en_fabricacion' => ['is-working', 'fas fa-hammer'],
        'terminado' => ['is-finished', 'fas fa-check-circle'],
        'entregado' => ['is-delivered', 'fas fa-truck'],
    ];
    [$clase, $icono] = $estilos[$pedido->estado] ?? ['is-accepted', 'fas fa-info-circle'];
@endphp
<span class="order-summary-state {{ $clase }}"><i class="{{ $icono }}" aria-hidden="true"></i>{{ $pedido->estado_nombre }}</span>
