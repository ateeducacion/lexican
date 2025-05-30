@if($entrada->estado == config('ctes.estados_entrada.oculta') )
    {{-- La entrada está oculta y no puede enviarse. Se muestra ventana de alerta --}}
    @include('layouts.partials.components.modal-simple', [
    'id' => 'uid_'.uniqid(),
    'img' => asset('/imagenes/ico-enviar.svg'),
    'modal_width' => '700px',
    'tooltip' => __('diccionario.modal_enviar_entrada_un_diccionario_tooltip'),
    'title' => __('diccionario.modal_enviar_entrada_title'),
    'body' => __('diccionario.modal_enviar_entrada_oculta_body', ['imagen' => asset('/imagenes/ico-ojo-rojo.svg')]),
    'boton'=> __('diccionario.boton_aceptar'),
    ])
@else

    @if(isset($img))
        <a 
            data-event="modalBotonesEntradaEnviar"
            data-id="{{ $id }}""
            data-route="{{ route('getDiccionariosAulaEntradaAjax') }}"
            data-entrada-id="{{ $entrada->id }}"

            {{-- onclick="aceptar_{{ $id }}('{{ route('getDiccionariosAulaEntradaAjax') }}', {{ $entrada->id }} )" --}}
        >
            <img class="cursor-pointer" data-toggle="tooltip" data-placement="top" src="{{ asset($img) }}" title="{{ $tooltip }}">
        </a>
    @endif

@endif