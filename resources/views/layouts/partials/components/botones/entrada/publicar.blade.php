@if (isset($entrada->publicadas) && $entrada->publicadas->estado == config('ctes.estados.activo'))

    <a href="javascript:void(0)" 
        data-event="anular-publicacion" 
        data-action="anular-publicacion"
        data-url="{{ route('diccionarioaula.publicar', ['id' => $diccionario->id, 'identrada' => $entrada->id]) }}" 
        data-entradaid="{{ $entrada->id }}" 
        data-diccionarioid="{{ $diccionario->id }}"
        data-entrada="{{ $entrada->entrada }}"
        data-participante="{{ $entrada->dpEnvio->dpDiccionario->persona->nombreCompleto() }}"
        data-diccionario="{{ $diccionario->titulo }}"
        data-action-text="{{ __('diccionario.modal_entrada_anular_tooltip') }}"
        data-cancel-text="{{ __('diccionario.boton_cancelar') }}"
        
        data-body-text="{{ __('diccionario.entradaPublicar_yaEnviadaPor', [
            'entrada' => $entrada->entrada, 
            'participante' => $entrada->dpEnvio->dpDiccionario->persona->nombreCompleto(), 
            'diccionario'=> $diccionario->titulo ]) }}<br>{{ __('diccionario.entradaPublicar_deseaAnular') }}" 

        class="icon" data-toggle="tooltip" data-placement="left" title="@lang('Anular publicación')" >
        <img data-placement="left" title="" data-original-title="@lang('Anular publicación')"
            src="{{ asset('/imagenes/pergamino.svg') }}"            
            >
    </a>

@else

    <a href="javascript:void(0)" 
        data-event="publicar"
        data-action="publicar"
        data-url="{{ route('diccionarioaula.publicar', ['id' => $diccionario->id, 'identrada' => $entrada->id]) }}" 
        data-entradaid="{{ $entrada->id }}" 
        data-entrada="{{ $entrada->entrada }}"
        data-participante="{{ $entrada->dpEnvio->dpDiccionario->persona->nombreCompleto() }}" 
        data-diccionario="{{ $diccionario->titulo }}"
        data-action-text="{{ __('diccionario.modal_entrada_publicar_tooltip') }}"
        data-cancel-text="{{ __('diccionario.boton_cancelar') }}"
        
        data-body-text="{{ __('diccionario.entradaPublicar_publicarPor', [
            'entrada' => $entrada->entrada, 
            'participante' => $entrada->dpEnvio->dpDiccionario->persona->nombreCompleto(), 
            'diccionario'=> $diccionario->titulo ]) }}"
        
        class="icon" 
        data-toggle="tooltip" 
        data-placement="left" 
        title="{{ __('diccionario.modal_entrada_publicar_tooltip') }}">

        <img 
            data-placement="left" title="" data-original-title="@lang('Publicar')"            
            src="{{ asset('/imagenes/pergamino-gris.svg') }}" >
    </a>

@endif