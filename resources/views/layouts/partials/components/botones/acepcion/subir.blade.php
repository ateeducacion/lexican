<a 
    href="{{ route('acepcion.up', [$datos->entrada->dic_personal_id, $acepcion->dic_entrada_id, $acepcion->id]) }}"
    class="icon"
    data-toggle="tooltip" data-placement="top" title="@lang('Subir')"
    >
    <img src="{{ asset('/imagenes/icono-subir.svg') }}" alt="@lang('Subir')">
</a>