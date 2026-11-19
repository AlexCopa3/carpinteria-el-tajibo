@if (session()->has('swal'))
    <script>
        Swal.fire(@json(session('swal')));
    </script>
@elseif (session()->has('mensaje') && session()->has('icono'))
    <script>
        Swal.fire({
            position: 'top-end',
            text: @json(session('mensaje')),
            icon: @json(session('icono')),
            showConfirmButton: false,
            timer: 3000
        });
    </script>
@endif
