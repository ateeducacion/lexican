<!-- 
comentarios Entradas By Comentarios Visibles: 
{{ $nComentariosEntrada = daGetComentariosEntradasByEntradaByComentariosVisible($entrada)->count() }} 
-->

@if( $nComentariosEntrada == 0 )
    @include('layouts.partials.components.modal-simple', [
        'id' => 'uid_'.uniqid(),
        'img' => asset('/imagenes/ico-coment.svg'),
        'modal_width' => '700px',
        'tooltip' => __('diccionario.comentarios_entrada_tooltip'),
        'title' => __('diccionario.comentarios_entrada_title'),
        'body' => __('diccionario.comentarios_entrada_sincomentarios'),
        'boton'=> __('diccionario.boton_aceptar'),
    ])
@else

    @if(isset($img))
        <a 
        data-event="modalVerComentariosEntrada"
        data-id="{{ $id }}"
        data-route="{{ route('getComentariosEntradaAjax') }}"
        data-entrada-id="{{ $entrada->id }}"

                {{-- onclick="aceptar_{{ $id }}('{{ route('getComentariosEntradaAjax') }}', {{ $entrada->id }} )" --}}
        >
            <img data-toggle="tooltip" data-placement="top" src="{{ asset($img) }}" title="{{ $tooltip }}">
        </a>
    @endif
@endif