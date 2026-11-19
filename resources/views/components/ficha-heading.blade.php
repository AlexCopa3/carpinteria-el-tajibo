@props(['title', 'subtitle', 'icon', 'activo' => null])
<div class="record-heading">
    <span class="record-heading-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
    <div class="record-heading-text">
        <h2>{{ $title }}</h2>
        <p>{{ $subtitle }}</p>
    </div>
    @if($activo !== null)
        <span class="record-status estado-registro {{ $activo ? 'is-active' : 'is-inactive' }}">
            <i class="fas {{ $activo ? 'fa-check-circle' : 'fa-minus-circle' }}" aria-hidden="true"></i>
            {{ $activo ? 'Activo' : 'Inactivo' }}
        </span>
    @endif
</div>
