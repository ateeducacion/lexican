<a href="{{ route('acepcion.delete', [$datos->entrada->dic_personal_id, $acepcion->dic_entrada_id, $acepcion->id]) }}" onclick="return confirm('{{ $confirm }}')">
    <img src="{{ asset('/imagenes/ico-trash.svg') }}" alt="@lang('Borrar')">
</a>