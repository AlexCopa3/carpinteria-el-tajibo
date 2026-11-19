@props(['registro'])
@php
    $creacion = $registro->auditorias()->with('usuario')->where('accion', 'creado')->oldest('id')->first();
    $primera = $registro
        ->auditorias()
        ->with('usuario')
        ->whereIn('accion', ['modificado', 'permisos_actualizados', 'contrasena_restablecida'])
        ->oldest('id')
        ->first();
    $ultima = $registro
        ->auditorias()
        ->with('usuario')
        ->whereIn('accion', ['modificado', 'permisos_actualizados', 'contrasena_restablecida'])
        ->latest('id')
        ->first();
@endphp
<section class="card dashboard-card record-audit mt-4" aria-label="Responsables del registro">
    <div class="card-body">
        <div class="record-section-title">
            <i class="fas fa-user-clock" aria-hidden="true"></i>
            <div>
                <h2>Responsables del registro</h2>
                <p>Creación y modificaciones registradas en el sistema.</p>
            </div>
        </div>
        <div class="record-audit-grid">
            <div class="record-audit-item">
                <span class="record-audit-icon" aria-hidden="true"><i class="fas fa-user-plus"></i></span>
                <div>
                    <h3>Registrado por</h3>
                    <p>{{ $creacion ? $creacion->usuario?->name ?? 'Proceso del sistema' : 'No registrado anteriormente' }}</p>
                    <small><i class="far fa-calendar-alt mr-1" aria-hidden="true"></i>{{ ($creacion?->created_at ?? $registro->created_at)?->format('d/m/Y H:i') }}</small>
                </div>
            </div>
            <div class="record-audit-item">
                <span class="record-audit-icon" aria-hidden="true"><i class="fas fa-pen"></i></span>
                <div>
                    <h3>Primera modificación por</h3>
                    <p>{{ $primera ? $primera->usuario?->name ?? 'Proceso del sistema' : 'Sin modificaciones registradas' }}</p>
                    @if($primera)
                        <small><i class="far fa-calendar-alt mr-1" aria-hidden="true"></i>{{ $primera->created_at->format('d/m/Y H:i') }}</small>
                    @endif
                </div>
            </div>
            <div class="record-audit-item">
                <span class="record-audit-icon" aria-hidden="true"><i class="fas fa-sync-alt"></i></span>
                <div>
                    <h3>Última modificación por</h3>
                    <p>{{ $ultima ? $ultima->usuario?->name ?? 'Proceso del sistema' : 'Sin modificaciones registradas' }}</p>
                    @if($ultima)
                        <small><i class="far fa-calendar-alt mr-1" aria-hidden="true"></i>{{ $ultima->created_at->format('d/m/Y H:i') }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
