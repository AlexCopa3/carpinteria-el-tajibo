@props(['placeholder' => 'Buscar por nombre o contacto'])
<form method="GET" class="search-toolbar mb-3">
    <label for="q" class="sr-only">Buscar</label>
    <input class="form-control" id="q" name="q" value="{{ request('q') }}" maxlength="200"
        placeholder="{{ $placeholder }}">
    <button class="btn btn-primary btn-search" type="submit">
        <i class="fas fa-search mr-2" aria-hidden="true"></i>
        Buscar
    </button>
    <a class="btn btn-light btn-clear" href="{{ url()->current() }}">
        <i class="fas fa-eraser mr-2" aria-hidden="true"></i>
        Limpiar
    </a>
</form>
