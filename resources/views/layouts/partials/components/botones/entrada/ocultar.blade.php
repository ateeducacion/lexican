@if($entrada->estado == config('ctes.estados_entrada.oculta') )
    @include('layouts.partials.components.ajaxmodal-AceptarCancelar', [
    'id' => 'uid_'.uniqid(),
    'img' => asset('/imagenes/ico-ojo-rojo.svg'),
    'modal_width' => '700px',
    'tooltip' => __('diccionario.modal_entrada_mostrar_tooltip'),
    'titulo' => __('diccionario.modal_entrada_mostrar_titulo'),
    'mensaje' => __('diccionario.modal_entrada_mostrar', [
        'entrada' => $entrada->entrada,
        'imagen' => asset('/imagenes/ico-ojo-rojo.svg')
    ]),
    'action' => route('entrada.ocultar', [$entrada->dic_personal_id, $entrada->id]),
    ])
@else
    @include('layouts.partials.components.ajaxmodal-AceptarCancelar', [
    'id' => 'uid_'.uniqid(),
    'img' => asset('/imagenes/ico-p-ojo.svg'),
    'modal_width' => '700px',
    'tooltip' => __('diccionario.modal_entrada_ocultar_tooltip'),
    'titulo' => __('diccionario.modal_entrada_ocultar_titulo'),
    'mensaje' => __('diccionario.modal_entrada_ocultar', ['entrada' => $entrada->entrada]),
    'action' => route('entrada.ocultar', [$entrada->dic_personal_id, $entrada->id]),
    ])
@endif