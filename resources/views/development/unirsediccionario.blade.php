@extends('diccionario/home')

@section('content')


<form id="unirseAlDiccionario" class="my-form" method="POST" action={{ route('aula.unirse') }} enctype="multipart/form-data">

    @csrf
    {{-- <div class="form-row">
        <div class="form-group col-md-12">
            <label for="cursoEscolar">@lang('diccionario.modal_unirse_diccionario_curso_escolar')</label>
            <input type="text" name="cursoEscolar" id="cursoEscolar" value="{{ $curso_escolar }}" readonly class="form-control">
        </div>
    </div> --}}


    <div class="form-row">
        <div class="form-group col-md-4">
            <label for="estudio">@lang('diccionario.modal_unirse_diccionario_curso')</label>
            <div class="dc-select-grp">
                <select id="estudio" name="estudio" class="form-control input-group-append">
                    <option value="">@lang('diccionario.acepcion_seleccione_valor')</option>
                    @foreach($nivelEstudios as $nivelEstudio)
                        <option value="{{ $nivelEstudio->id }}">{{ $nivelEstudio->descripcion }}</option>
                    @endforeach
                </select>
                <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
            </div>


        </div>
        <div class="form-group col-md-1">
        </div>
        <div class="form-group col-md-3">
            <label for="grupoLetra">@lang('diccionario.modal_unirse_diccionario_grupo')</label>
            <div class="dc-select-grp">
                <select id="grupoLetra" name="grupoLetra" class="form-control">
                    <option value="">@lang('diccionario.acepcion_seleccione_valor')</option>
                    @foreach(range('A', 'Z') as $letter)
                        <option value="{{ $letter }}">{{ $letter }}</option>
                    @endforeach
                </select>
                <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
            </div>
        </div>
        <div class="form-group col-md-1">
        </div>
        <div class="form-group col-md-3">
            {{-- <label for="cursoEscolar">@lang('diccionario.modal_unirse_diccionario_codigo')</label> --}}
            <div class="form-row">
                <div class="form-group col-md-4">
                    <input type="text" name="nivelEstudioFinal" id="nivelEstudioFinal" value="" readonly class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <input type="text" name="grupoFinal" id="grupoFinal" value="" readonly class="form-control">
                </div>
                <div class="form-group col-md-4">
                    <input type="text" name="codigoProfe" id="codigoProfe" value="" maxlength="4" class="form-control">
                </div>
            </div>
        </div>
    </div>

</form>

<button type="button" class="btn btn-primary" onclick="unirseAlDiccionario()">
    @lang(__('diccionario.boton_aceptar'))
</button>
<button type="button" class="btn btn-secondary btn-danger" data-dismiss="modal">
    @lang(__('diccionario.boton_cancelar'))
</button>

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
@endsection