@extends('layouts/partials/components/modal-simpleyield')

@section('modal-title')
{{ $titulo }}
@overwrite

    @section('modal-body')
    <p>{!! $mensaje !!}</p>
    @overwrite


        @section('modal-footer')
        <button type="button" class="btn btn-secondary btn-danger" data-dismiss="modal">
            @lang(__('diccionario.boton_cancelar'))
        </button>
        <button type="button" class="btn btn-primary" onclick="{{ 'aceptarCancelar_' . $id }}()">
            @lang(__('diccionario.boton_aceptar'))
        </button>
        @overwrite



            {{-- @section('scripts') --}}
            <script>
            {{ 'function aceptarCancelar_'.$id. '() {' }}
                var me = $(this);

                if (me.data('haciendoLlamada')) {
                    return;
                }

                me.data('haciendoLlamada', true);

                $.get('{{ $action }}')
                    .done(function() {
                        {!! $onDone !!}
                        $('#modal_{!! $id !!}').modal('hide');
                        $('body').removeClass('modal-open');
                        $('.modal-backdrop').remove();
                   
                    })
                    .fail(function() {
                        alert("error");
                    })
                    .always(function() {
                        me.data('haciendoLlamada', false);
                    })
            }
            </script>