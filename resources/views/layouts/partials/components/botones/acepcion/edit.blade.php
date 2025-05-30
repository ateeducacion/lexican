<a 
    data-toggle="tooltip" title="{{ __('diccionario.acepcion_editar') }}"
    href="{{ route('acepcion.edit', [$datos->entrada->dic_personal_id, $acepcion->dic_entrada_id, $acepcion->id]) }}" class="icon">
    <img src="{{ asset('/imagenes/ico-edit.svg') }}" alt="@lang('Editar')">
</a>