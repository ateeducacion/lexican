@extends('layouts/partials/components/modal-simpleyield')

@section('modal-title', __('diccionario.modal_unirse_diccionario_title'))


@section('modal-body')
<p>{{ $modal_unirse_body }}
    {{-- @lang('diccionario.modal_unirse_diccionario_body') --}}
</p>

<form id="unirseAlDiccionario" class="my-form text-left" method="POST" action={{ route('aula.unirse') }} enctype="multipart/form-data">

    @csrf
    <div class="form-row">
        
        {{-- <div class="form-group col-md">
            <label for="cursoEscolar">@lang('diccionario.modal_unirse_diccionario_curso_escolar')</label>
            <input type="text" name="cursoEscolar" id="cursoEscolar" value="{{ $curso_escolar }}" readonly class="form-control">
        </div> --}}

        <div class="form-group col-md-6 mx-auto">
            <label for="cursoEscolar">@lang('diccionario.modal_unirse_diccionario_codigo')</label>
            <div class="form-row">
                    <input type="text" name="codigo" id="codigo" value=""  class="form-control">
            </div>
        </div>
    </div>

</form>
@overwrite




    @section('modal-footer')
    <button type="button" class="btn btn-primary" onclick="unirseAlDiccionario()">
        @lang(__('diccionario.boton_aceptar'))
    </button>
    <button type="button" class="btn btn-secondary btn-danger" data-dismiss="modal">
        @lang(__('diccionario.boton_cancelar'))
    </button>
    @overwrite



        @section('modal-scripts')
        <script>
            $(function() {
                $('select[name="estudio"]').change(function() {
                    $('input[name="nivelEstudioFinal"]').val(formatCode(document.getElementById('estudio').options[document.getElementById('estudio').selectedIndex].text))
                });
                $('select[name="grupoLetra"]').change(function() {
                    $('input[name="grupoFinal"]').val($(this, ':selected').val())
                });
            })

            function formatCode(code) {
                return code
                    .replace(/\s/, '')
                    .replace('º', '')
                    .substring(0, 2);
            }

            function unirseAlDiccionario() {
                document.getElementById('unirseAlDiccionario').submit();
            }
        </script>
        @append