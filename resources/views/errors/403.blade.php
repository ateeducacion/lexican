@extends('layouts/app')
{{-- @extends('diccionario/home') --}}

@section('title', 'Error 403 ')

@section('content')
<div id="noautorizado" class="row mt-5 pt-5 justify-content-md-center d-none">


<div class="col-md-auto ">
    <img src="{{ asset('imagenes/remove_circle-black-18dp.svg') }}" style="height: 5rem" alt="icono no autorizado">
</div>
<div class="col-md-4 col-lg-3 col-xl-2">
    <h2></h2>
    <p>Acceso no autorizado con su perfil a esta acción.</p>
    {{-- 
        Prueba fuente font-family carlitos
        <p>The quick brown fox jumps over the lazy dog</p> 
    --}}
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