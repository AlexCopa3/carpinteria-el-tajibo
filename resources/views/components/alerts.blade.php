@if(session('status'))
    <div role="status" class="alert alert-success"><i class="fas fa-check-circle mr-2" aria-hidden="true"></i>{{ session('status') }}</div>
@endif
@if($errors->any())
    <div role="alert" class="alert alert-danger"><strong>Revisa los datos del formulario.</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
