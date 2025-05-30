@switch( $name )

    @case('entrada.delete')
    <a href="{{ route('entrada.delete', $id) }}" onclick="return confirm('{{ $confirm }}')" class="icon">
        <img src="{{ asset('/imagenes/ico-trash.svg') }}" alt="@lang('diccionario.icono_borrar')">
    </a>
    @break

    @case('entrada.edit')
    <a href="{{ route('entrada.edit', $id) }}" class="icon">
        <img src="{{ asset('/imagenes/ico-edit.svg') }}" alt="@lang('diccionario.icono_editar')">
    </a>
    @break

    @case('entrada.enviar')
    <a href="{{ $id }}" class="icon">
        <img src="{{ asset('/imagenes/ico-enviar.svg') }}" alt="@lang('diccionario.icono_enviar')">
    </a>
    @break

    @case('entrada.comentar')
    <a href="{{ $id }}" class="icon">
        <img src="{{ asset('/imagenes/ico-coment.svg') }}" alt="@lang('diccionario.icono_vercomentarios')">
    </a>
    @break

    @case('acepcion.ocultar')
    <a href="{{ $id }}" class="icon">
        <img src="{{ asset('/imagenes/ico-p-ojo.svg') }}" alt="@lang('Ocultar')">
    </a>
    @break

    @default
    <h1>ERROR no existe icono</h1>
    @break

@endswitch