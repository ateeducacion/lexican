<a href="{{ route('entrada.delete', [$diccionario_id, $entrada_id]) }}" onclick="return confirm('{{ $confirm }}')" class="icon">
    <img 
        data-toggle="tooltip" data-placement="top"
        src="{{ asset('/imagenes/ico-trash.svg') }}" title="@lang('diccionario.icono_borrar')">
</a>