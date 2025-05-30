{{-- <a href="{{ $entrada_id }}" class="icon" >
<img data-toggle="tooltip" src="{{ asset('/imagenes/ico-coment.svg') }}" alt="@lang('diccionario.icono_vercomentarios')" title="@lang('diccionario.icono_vercomentarios')">
</a> --}}

<!-- 

comentarios Generales By Comentarios Visibles: 
{{ daGetComentariosGeneralesByComentariosVisible()->count() }}

comentarios Entradas By Comentarios Visibles: 
{{ daGetComentariosEntradasByComentariosVisible()->count() }}

-->

@if(daGetComentariosGeneralesByComentariosVisible()->count() + daGetComentariosEntradasByComentariosVisible()->count() == 0 )
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
        <a onclick="aceptar_{{ $id }}('{{ route('getComentariosEntradaAjax') }}', {{ $entrada->id }} )">
            <img data-toggle="tooltip" data-placement="top" src="{{ asset($img) }}" title="{{ $tooltip }}">
        </a>
    @endif

    @section('scripts')
    <script>
    {{ 'function aceptar_'. $id }}(ruta, entrada_id) {

    var me = $(this);

    if (me.data('haciendoLlamada')) {
    return;
    }

    me.data('haciendoLlamada', true);

    $.get(ruta, {entrada_id})
    .done(response => {
    $('#padre_modal_ajax').html(response['html']);
    $("#modal_ajax").modal();
    // Muestra cualquier modal que se haya cargado dentro de #padre_modal_ajax
    $('#padre_modal_ajax').find('.modal').modal();
    })
    .fail(function() {
    alert("error");
    })
    .always(function() {
    me.data('haciendoLlamada', false);
    })

    }

    </script>
    @append
    @endif