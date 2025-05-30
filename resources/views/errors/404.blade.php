{{-- @extends('layouts/app') --}}
@extends('diccionario/home')

@section('title', 'Error 403 ')

@section('content')
<div id="noautorizado" class="row mt-5 pt-5 justify-content-md-center d-none">


<div class="col-md-auto ">
    <img src="{{ asset('imagenes/find_in_page-24px.svg') }}" style="height: 10rem" alt="icono pagina 404">
</div>
<div class="col-md-4 col-lg-3 col-xl-2">
    <h2>Error 404 </h2>
    <p>Página no encontrada</p>
    <p>La página solicitada puede que no se encuentre disponible, haber cambiado de dirección o no existir. Perdone las molestias.</p>
</div>

</div>

@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var mi = document.getElementById("noautorizado");
        mi.classList.remove('d-none');
    });
</script>
@endsection