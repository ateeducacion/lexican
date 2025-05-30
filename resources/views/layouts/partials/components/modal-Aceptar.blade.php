@extends('layouts/partials/components/modal-simpleyield')

@section('modal-title')
{{ $titulo }}
@overwrite

    @section('modal-body')
    <p>{!! $mensaje !!}</p>
    @overwrite

        @section('modal-footer')
        <button type="button" class="btn btn-primary" data-dismiss="modal">
            {{ $boton }}
        </button>
        @overwrite