@if ($entrada->estado != config('ctes.estados_envios.publicado'))
{{-- $entrada es enviosEntrada --}}
    <a href="javascript:void(0)" class="icon botonEliminar" id="botonEliminar"
        data-event="eliminar"
        data-url="{{ route('diccionarioaula.eliminar', ['id' => $diccionario->id, 'identrada' => $entrada->id]) }}"
        data-entradaid="{{ $entrada->id }}" 
        data-diccionarioid="{{ $diccionario->id }}"
        data-entrada="{{ $entrada->entrada }}"
        data-participante="{{ $entrada->dpEnvio->dpDiccionario->persona->nombreCompleto() }}"
        data-action-text="{{ __('diccionario.modal_entrada_eliminar_tooltip') }}"
        data-cancel-text="{{ __('diccionario.boton_cancelar') }}"
        data-body-text="{{ __('diccionario.entradaEliminar_confirmar', [
            'entrada' => $entrada->entrada, 
            'participante' => $entrada->dpEnvio->dpDiccionario->persona->nombreCompleto() ]) }}"
        class="icon" data-toggle="tooltip" data-placement="top" title="@lang('Eliminar envío')">
        <img src="{{ asset('/imagenes/ico-trash.svg') }}" data-original-title="@lang('Eliminar envío')">
    </a>
@else
    <a href="javascript:void(0)" class="icon botonEliminar" id="botonEliminar" data-entradaid="{{ $entrada->id }}" data-diccionarioid="{{ $diccionario->id }}" disabled>
        <img src="{{ asset('/imagenes/ico-trash-disabled.svg') }}">
    </a>
@endif
