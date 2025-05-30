@extends('layouts/partials/components/modal-simpleyield')

@section('modal-title')
{{ $titulo }}
@overwrite

@section('modal-body')
<p>{!! $mensaje !!}</p>
@overwrite


@section('modal-footer')
    
    <button type="button" class="btn btn-primary" onclick="{{ 'aceptarCancelar_' . $id }}()">
        @lang(__('diccionario.boton_aceptar'))
    </button>
    &nbsp;
    <button type="button" class="btn btn-secondary btn-danger" data-dismiss="modal">
        @lang(__('diccionario.boton_cancelar'))
    </button>
@overwrite



{{-- @section('scripts') --}}
<script>
{{ 'function aceptarCancelar_'.$id. '() {' }}
window.location = "{{ $action }}";
}
</script>
{{-- @append --}}