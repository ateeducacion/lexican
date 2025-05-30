@foreach ($entrada->comentariosEntrada->sortByDesc('fecha_envio') as $comentario)
    <div class="elemento-lista-comentario position-relative">
        <div class="d-inline">
            {{Carbon\Carbon::parse($comentario->fecha_envio)->format('d/m/Y')}}
        </div>
        <div class="d-inline">
            <a href="javascript:void(0)" class="icon" data-toggle="tooltip" data-placement="top" title="" data-original-title="@lang('Editar')">
                <img style="text-decoration: none" data-placement="top" title="" data-original-title="@lang('Editar')"
                data-event="editar-comentario" data-element="{{$comentario->id}}" data-parent="{{$entrada->id}}"
                data-action="{{route('diccionarioaula.comentario.edit', ['id' => $entrada->dicAula->id, 'identrada' => $entrada->id, 'idcomentario' => $comentario->id])}}"
                    src="{{ asset('/imagenes/ico-edit.svg') }}" >
            </a>
            <a href="javascript:void(0)" class="icon" data-toggle="tooltip" data-placement="top" title="" data-original-title="@lang('Eliminar')">
                <img data-placement="top" title="" data-original-title="@lang('Eliminar')"
                    data-event="borrar-comentario" data-element="{{$comentario->id}}" data-parent="{{$entrada->id}}"
                    data-action="{{route('diccionarioaula.comentario.delete', ['id' => $entrada->dicAula->id, 'identrada' => $entrada->id, 'idcomentario' => $comentario->id])}}"
                    src="{{ asset('/imagenes/ico-trash.svg') }}" >
            </a>                                    
        </div>   
        <div class="text-comentario-{{$comentario->id}}">
            {!!$comentario->comentario!!}
        </div>                             
    </div>
@endforeach