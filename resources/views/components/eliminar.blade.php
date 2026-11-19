@props(['registro', 'ruta'])
@can('delete', $registro)
    @php($motivo = app(App\Services\EliminacionService::class)->motivo($registro))
    @if($motivo)
        <span class="delete-unavailable" tabindex="0" title="{{ $motivo }}" aria-label="Eliminar no disponible. {{ $motivo }}">
            <button class="btn btn-danger btn-sm" type="button" disabled><i class="fas fa-trash mr-2" aria-hidden="true"></i>Eliminar</button>
        </span>
    @else
        <form method="POST" action="{{ $ruta }}" class="delete-action" data-confirmar-eliminacion>
            @csrf
            @method('DELETE')
            @if($registro instanceof App\Models\Cotizacion)<input type="hidden" name="version" value="{{ $registro->version }}">@endif
            <button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash mr-2" aria-hidden="true"></i>Eliminar</button>
        </form>
    @endif
@endcan
