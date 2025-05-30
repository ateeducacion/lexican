@extends('layouts/partials/components/modal-simpleyield')

@section('modal-title')
{{ __('diccionario.modal_enviar_entrada_title') }}
@overwrite

@section('modal-body')
@if ( aceptaEnvios($listaDiccionariosAula) )
    <p>@lang( __( 'diccionario.modal_enviar_entrada_un_diccionario', [
            'entrada' => $entrada->entrada, 
            'diccionario'=> $listaDiccionariosAula->titulo, 
            'nombreprofe'=> $listaDiccionariosAula->profesor->nombreCompleto()
        ])
    )</p>
@else
<p>    
    "{{ mb_ucfirst($listaDiccionariosAula->titulo) }}": {{ __('diccionario.tooltip_envios_deshabilitados') }}
</p>
@endif
    {{-- {{ dd( 
        ($enviosADic[0]->updated_at)->format('d/m/Y') ,
        'first', ($enviosADic->first()->updated_at)->format('d/m/Y') ,
        'last', ($enviosADic->last()->updated_at)->format('d/m/Y') ,
    ) }} --}}
    <p class="dc-info-ultimo-envio" data-dicId="{{ $listaDiccionariosAula->id }}">
        @if ($enviosADic->count()>0)
            {{ __('diccionario.envio_entrada_fecha_anterior') }} 
            {{ \Carbon\Carbon::parse($enviosADic->last()->updated_at)->format('d/m/Y') }}.
        @endif
    </p>

    <form id="form-modal-enviarEntradaUnDiccionario" class="my-form" method="POST" action={{ route('entrada.send', ['diccionario_id'=> $entrada->dic_personal_id, 'entrada_id'=> $entrada->id]) }} enctype="multipart/form-data">

        @csrf
        <input type="hidden" name="listaDiccionariosAula" value="{{ $listaDiccionariosAula->id }}" />

    </form>
@overwrite


@section('modal-footer')
    @if ( aceptaEnvios($listaDiccionariosAula) )
        <button type="button" class="btn btn-primary" onclick="aceptar()">
        @lang(__('diccionario.boton_aceptar'))
        </button>
        <button type="button" class="btn btn-secondary btn-danger" data-dismiss="modal">
        @lang(__('diccionario.boton_cancelar'))
        </button>
    @else
        <button type="button" class="btn btn-secondary btn-danger" data-dismiss="modal">
            @lang(__('diccionario.boton_volver'))
        </button>
    @endif
@overwrite

@section('modal-scripts')
    <script>
        function aceptar() {
            document.getElementById('form-modal-enviarEntradaUnDiccionario').submit();
        }
    </script>
@append