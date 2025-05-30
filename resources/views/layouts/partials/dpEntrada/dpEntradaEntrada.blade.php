<!-- dpEntradaEntrada -->
<div class="dc-entrada-container">
    <div class="dc-entrada-top row">
        <div class="dc-entrada-titulo col-6 col-xs-6 col-sm-8 col-lg-9 col-xl-10"> {{ $datos->entrada->entrada }} </div>
        <div class="dc-entrada-botones-top col-6 col-xs-6 col-sm-4 col-lg-3 col-xl-2 dc-entrada-botones d-flex">
            @if(isset($datos->entrada->id))
                <a href="{{ route('entrada.edit',[$datos->entrada->dic_personal_id, $datos->entrada->id]) }}">
                    <img
                        data-toggle="tooltip" data-placement="top" title="Editar nombre"
                        src="{{ asset('/imagenes/ico-edit.svg') }}" alt="Editar"
                    >
                </a>

                {{-- Botón Ocultar Entrada --}}
                @if($datos->entrada->estado == config('ctes.estados_entrada.oculta') )
                    @include('layouts.partials.components.ajaxmodal-AceptarCancelar', [
                    'id' => 'uid_'.uniqid(),
                    'img' => asset('/imagenes/ico-ojo-rojo.svg'),
                    'modal_width' => '700px',
                    'tooltip' => __('diccionario.modal_entrada_mostrar_tooltip'),
                    'titulo' => __('diccionario.modal_entrada_mostrar_titulo'),
                    'mensaje' => __('diccionario.modal_entrada_mostrar', [
                        'entrada' => $datos->entrada->entrada,
                        'imagen' => asset('/imagenes/ico-ojo-rojo.svg')
                    ]),
                    'action' => route('entrada.ocultar', [$datos->entrada->dic_personal_id, $datos->entrada->id]),
                    ])
                @else
                    @include('layouts.partials.components.ajaxmodal-AceptarCancelar', [
                    'id' => 'uid_'.uniqid(),
                    'img' => asset('/imagenes/ico-p-ojo.svg'),
                    'modal_width' => '700px',
                    'tooltip' => __('diccionario.modal_entrada_ocultar_tooltip'),
                    'titulo' => __('diccionario.modal_entrada_ocultar_titulo'),
                    'mensaje' => __('diccionario.modal_entrada_ocultar', ['entrada' => $datos->entrada->entrada]),
                    'action' => route('entrada.ocultar', [$datos->entrada->dic_personal_id, $datos->entrada->id]),
                    ])
                @endif
                
                {{-- Botón Borrar Entrada --}}
                @include('layouts.partials.components.modal-AceptarCancelar', [
                    'id' => 'uid_'.uniqid(),
                    'class' => 'ico-papelera-fix',
                    'img' => asset('/imagenes/ico-trash.svg'),
                    'modal_width' => '700px',
                    'tooltip' => __('diccionario.entrada_borrar'),
                    'titulo' => __('diccionario.confirm_titulo'),
                    'mensaje' => $datos->confirm,
                    'action' => route('entrada.delete', [$datos->entrada->dic_personal_id, $datos->entrada->id]),
                ])
            @endif

        </div>
        <div class="dc-entrada-top-right">
                
        </div>
    </div>

    {{-- acepciones --}}
    <div class="dc-acepciones">
        @foreach( $datos->entrada->dpAcepciones('asc')->get() as $acepcion )
            @include('layouts.partials.consulta.dpEntradaAcepcion', $acepcion)
        @endforeach 
    </div>

    {{-- fin acepciones --}}
    <div class="dc-entrada-bottom">
        
    </div>

</div>

{{-- Boton Anadir acepcion --}}
<div class="row justify-content-center">
    @include('layouts.partials.dpAcepcion.dpAcepcionAnadir')        
</div>