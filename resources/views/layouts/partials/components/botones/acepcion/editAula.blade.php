
<a 
    data-toggle="tooltip" data-placement="left" title="{{ __('diccionario.acepcion_editar') }}"    
    href={{ route('aula.acepcion.edit', [ $dicAulaActivo_id, $acepcion->envioEntrada->id, $acepcion->id ]) }}
    {{-- href="{{ route('acepcion.edit', [ --}}
         {{-- $datos->entrada->dic_personal_id,  --}}
         {{-- $acepcion->dic_entrada_id, $acepcion->id --}}
        {{-- ]) }}"  --}}
        class="icon">
    <img src="{{ asset('/imagenes/ico-edit.svg') }}" alt="@lang('Editar')">
</a>