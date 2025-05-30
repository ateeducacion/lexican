@if($acepcion->estado == config('ctes.estados_entrada.oculta') )
    @include('layouts.partials.components.ajaxmodal-AceptarCancelar', [
    'id' => 'uid_'.uniqid(),
    'img' => asset('/imagenes/ico-ojo-rojo.svg'),
    'modal_width' => '550px',
    'tooltip' => __('diccionario.modal_acepcion_mostrar_tooltip'),
    'titulo' => __('diccionario.modal_acepcion_mostrar_titulo'),
    'mensaje' => __('diccionario.modal_acepcion_mostrar', ['numero' => $acepcion->orden, 'entrada' => $entrada->entrada]),
    'action' => route('acepcion.ocultar', [$entrada->dic_personal_id, $entrada->id, $acepcion->id]),
    ])
@else
    @include('layouts.partials.components.ajaxmodal-AceptarCancelar', [
    'id' => 'uid_'.uniqid(),
    'img' => asset('/imagenes/ico-p-ojo.svg'),
    'modal_width' => '700px',
    'tooltip' => __('diccionario.modal_acepcion_ocultar_tooltip'),
    'titulo' => __('diccionario.modal_acepcion_ocultar_titulo'),
    'mensaje' => __('diccionario.modal_acepcion_ocultar', ['numero' => $acepcion->orden, 'entrada' => $entrada->entrada]),
    'action' => route('acepcion.ocultar', [$entrada->dic_personal_id, $entrada->id, $acepcion->id]),
    ])
@endif