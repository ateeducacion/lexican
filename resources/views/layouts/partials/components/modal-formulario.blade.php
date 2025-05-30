@extends('layouts/partials/components/modal-simpleyield')

@section('modal-title')
{{ $titulo }}
@overwrite

    @section('modal-body')


    <p>{!! $mensaje !!}</p>


    <form id="{{ 'form' . $id }}" class="my-form" method="{{ $metodo ?? "POST" }}" action={{ $action }} enctype="multipart/form-data">
        @csrf
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



            @section('scripts')
            <script type="text/javascript">
            {{ 'function aceptar'.$id. '() {' }}
            document.getElementById('{{ 'form' . $id }}').submit();
            }
            </script>
            @append