@if($entrada->estado == config('ctes.estados_entrada.oculta') )
    @include('layouts.partials.components.ajaxmodal-AceptarCancelar', [
    'id' => 'uid_'.uniqid(),
    'img' => asset('/imagenes/pergamino.svg'),
    'modal_width' => '700px',
    'tooltip' => __('diccionario.modal_entrada_anular_tooltip'),
    'titulo' => __('diccionario.modal_entrada_anular_titulo'),
    'mensaje' => __('diccionario.modal_entrada_anular', ['entrada' => $entrada->entrada]),
    'action' => route('aula.entrada.ocultar', [getDicAulaActivo(), $entrada->id]),
    ])
@else
    @include('layouts.partials.components.ajaxmodal-AceptarCancelar', [
    'id' => 'uid_'.uniqid(),
    'img' => asset('/imagenes/pergamino.svg'),
    'modal_width' => '700px',
    'tooltip' => __('diccionario.modal_entrada_anular_tooltip'),
    'titulo' => __('diccionario.modal_entrada_anular_titulo'),
    'mensaje' => __('diccionario.modal_entrada_anular', ['entrada' => $entrada->entrada]),
    'action' => route('aula.entrada.ocultar', [getDicAulaActivo(), $entrada->id]),
    ])
@endif