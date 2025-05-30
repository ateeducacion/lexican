@extends('layouts/partials/components/modal-simpleyield')

@section('modal-title')
{{ __('diccionario.modal_enviar_diccionario_titulo') }}
@overwrite

    @section('modal-body')
    <p>@lang(__('diccionario.modal_enviar_diccionario_mensaje', ['diccionario' => $listaDiccionariosAula->titulo]))</p>

    <form id="{{ 'form' . $id }}" class="my-form" method="POST" action={{ route('diccionario.send', ['diccionario_id'=> $diccionario->id]) }} enctype="multipart/form-data">

        @csrf
        <input type="hidden" name="listaDiccionariosAula" value="{{ $listaDiccionariosAula->id }}" />

    </form>
    @overwrite


        @section('modal-footer')
        <button type="button" class="btn btn-primary" onclick="{{ 'aceptar' . $id }}()">
            @lang(__('diccionario.boton_aceptar'))
        </button>
        <button type="button" class="btn btn-secondary btn-danger" data-dismiss="modal">
            @lang(__('diccionario.boton_cancelar'))
        </button>
        @overwrite

            @section('modal-scripts')
            <script>
            {{ 'function aceptar'.$id. '() {' }}
            document.getElementById('{{ 'form' . $id }}').submit();
            }
            </script>
            @append