{{-- Botón Añadir Acepción --}}
<a class="text-decoration-none" href="{{ route('acepcion.create', [$datos->entrada->dic_personal_id, $datos->entrada->id]) }} ">
<div class="dpAcepcionAnadirBtn align-middle">
    <div class="d-inline-block mr-2">
        @lang('diccionario.acepcion_anadir')
    </div>
    <img
        class="dpAcepcionAnadirImg" 
        src="{{ asset('/imagenes/ico-bocadillo-plus.svg') }}" 
        alt="@lang('diccionario.acepcion_anadir')">
</div>
</a>