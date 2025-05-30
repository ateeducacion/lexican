@extends('diccionario/home')
@section('title', __('diccionario.comentarios_add_diccionario_generales')  )
@section('content')
@parent

{{-- Menu seleccionar diccionario de aula activo --}}
{{--
     No funciona por que al redireccionar deberia cambiar el id del diccionario de aula  
     /aula/{id}/comentarios
    --}}
@include('layouts.partials.components.select-DicAula', 
[ 'aulaSelectredirectUrl'=> route(Route::currentRouteName(), ['id'=>'-id-']) ])

<div class="row">
    <div class="col-md-12">
        <h5 class="card-title">
            {{-- Mensaje: COMENTARIOS GENERALES --}}
            <strong>{{ __('diccionario.comentarios_add_diccionario_generales') }}</strong>
        </h5>
    </div>
</div>


<div class="card dc-panel-gris">
    {{-- <div class="card-body"> --}}

        <div class="form-row">
            <div class="form-group offset-md-1 col-md-10">
                
            </div>
        </div>

        <form id="comentariosDiccioanrioEnviar" class="my-form" method="POST" action={{ route('comentarios.save', ['id'=> $datos->dicAula->id]) }} enctype="multipart/form-data">

            @csrf

            <input type="hidden" id="dic_aula_id" name="dic_aula_id" value="{{ $datos->dicAula->id }}" />

            <div class="form-row">
                <div class="form-group offset-1 col-10 col-11-sm">
                    
                    <label class="comentario-texto-letra" for="estudiante">
                        {{-- @lang('diccionario.comentarios_add_estudiante')&nbsp;</label> --}}
                        {{-- Participantes: {{ getDicAulaActivo()->participantes->pluck('persona.nombre') }} --}}
                        @if( $datos->participantes->count()>0 )
                            <div class="row">

                            <div class="form-group col-md-5">
                                <label for="estudiante">
                                    @lang('diccionario.comentarios_add_estudiante')
                                </label>
                                <div class="dc-select-grp">
                                    <select 
                                        id="estudiante" name="estudiante" class="form-control input-group-append"
                                        onchange="filtrarComentarios(this)" 
                                        onload="filtrarComentarios(this)">
                                        
                                        <option value="">@lang('diccionario.acepcion_seleccione_valor')</option>
                                        @foreach( $datos->participantes as $estudiante )
                                            <option value="{{ $estudiante->persona->id }}"
                                            @if( !is_null(session('lastEstudiante'))  )
                                                
                                                {{ (session('lastEstudiante')==$estudiante->persona->id) ? 'selected="selected"': '' }}
                                            @endif
                                            >
                                            {{ $estudiante->persona->nombre }} {{ $estudiante->persona->apellidos }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div id="btnNuevoComentario" class="dpAcepcionAnadirBtn align-middle cursor-pointer d-none" >
                                    <div class="d-inline-block mr-2">{{ __('diccionario.comentarios_add_opt_nuevo_comentario') }}</div>
                                    <img class="dpAcepcionAnadirImg" src="{{ asset('imagenes/ico-bocadillo-plus.svg') }}" alt="+">
                                </div>
                            </div>

                            </div>                            
                        @else
                        <span style="color:#000">No hay datos de estudiantes relacionados con este diccionario</span>
                        @endif
                </div>
            </div>

            <input type="hidden" name="radio" id="opt_nuevo_comentario" value="opt_nuevo_comentario" >
            <div id="grpNuevoComentario" class="d-none ">

                {{-- Área del Tiny --}}
                <div class="form-row">
                    <div class="form-group offset-1 col-10 col-11-sm mb-0">
                        <textarea class="textarea-tiny" 
                        name="tinyComentario" id="tinyComentario" rows="10"></textarea>
                        
                    </div>
                    <div class="offset-1 col-10 col-11-sm text-center font-size-10 mt-0 font-italic">
                        {{-- antes estaba la clase comentario-seccion-letra --}}
                        @lang('diccionario.comentarios_add_config_visibilidad', ['config_visibilidad' => $datos->mensaje_visibilidad])
                        {{-- @lang('en el diccionario') "{{ getDicAulaActivo()->titulo }}" --}}
                    </div>
                </div>

                {{-- Botones Descartar y Guardar --}}
                {{-- Botones del formulario --}}
                <div class="text-center mt-3">
                    <button type="submit" class="btn btn-primary  mr-3">@lang('diccionario.acepcion_guardar')</button>
                    <button type="button" class="btn btn-danger" onclick="descartar()">@lang('diccionario.boton_descartar')</button>
                    {{-- <a href="{{url()->previous() }}" class="btn btn-danger">@lang('diccionario.acepcion_cancelar')</a> --}}
                    {{-- <a href="{{ route('entrada.get', ['diccionario_id' => $datos->entrada->dic_personal_id, 'entrada_id' => $datos->acepcion->dic_entrada_id]) }}" class="btn btn-danger">@lang('diccionario.acepcion_cancelar')</a> --}}
                </div>
            </div>

            <div class="form-row">
                <br />
            </div>



            {{-- Lista de comentarios guardados --}}
            <div class="row d-none" id="lbComentariosGuardadosVacio">
                <div class="col-md-10 offset-md-1 text-left comentario-seccion-letra">
                {{ mb_ucfirst( __('diccionario.no_hay_comentarios') )  }} 
                </div>
            </div>
            <div class="row d-none mb-2" id="lbComentariosGuardados">
                <div class="col-md-10 offset-md-1 text-left comentario-seccion-letra">
                    {{-- @lang('diccionario.comentarios_add_comentarios_guardados') --}}
                    {{ __('diccionario.comentarios_add_comentarios_guardados') }}
                    <div class="float-right dc-fontsize2" style="top:4px;position: relative;">
                        <button class="dc-round-btn ml-1 dc-btn-ordenar" onclick="ordenarGeneralesPorFecha(this)" type="button" >
                            <div class="d-inline-block mr-2">{{ __('diccionario.por_fecha') }}</div>  
                        </button>

                    </div>       
                </div>
            </div>

            <div class="form-row">
                
                <div class="form-group col-md-10 offset-md-1">

                    <div class="text-left">
                        @if( daGetComentariosGeneralesProfesorByDicAula($datos->dicAula->id)->first() )
                        <div id="comentariosGenerales" class="">

                            <input type="hidden" id="hiddenComentarioOriginalId" name="hiddenComentarioOriginalId" value="" />
                            <input type="hidden" id="hiddenComentarioOriginalComentario" name="hiddenComentarioOriginalComentario" value="{{ daGetComentariosGeneralesProfesorByDicAula($datos->dicAula->id)->first()->comentario }}" />

                            @foreach(daGetComentariosGeneralesProfesorByDicAula($datos->dicAula->id) as 
                                $index => $comentarioGeneral)
                                <div class="div_comentario sortme" 
                                    id="comentarioGeneral_id_{{ $comentarioGeneral->id }}" 
                                    data-comentario_id="{{ $comentarioGeneral->id }}" 
                                    data-persona_id="{{ $comentarioGeneral->dpDiccionario->persona_id }}" 
                                    style="display: none"

                                    
                                    data-title="{{ substr($comentarioGeneral->comentario,0,5) }}" 
                                    data-index="{{ $index }}" 
                                    data-date="{{ $comentarioGeneral->fecha_envio->format('Ymd') }}"
                                    >
                                    <div class="card comentario-diccionario-card">
                                        <div class="card-header comentario-diccionario-header">
                                            <div class="comentario-texto-letra">
                                                <!-- Comentario por {{ $comentarioGeneral->persona->nombre }} 
                                                A {{ $comentarioGeneral->dpDiccionario->persona->nombre }}  -->
                                                {{ $comentarioGeneral->fecha_envio->format('d/m/Y') }}
                                                @include('layouts.partials.components.modal-AceptarCancelarAjax', [
                                                'id' => 'uid_'.uniqid(),
                                                'img' => asset('/imagenes/ico-trash.svg'),
                                                'modal_width' => '700px',
                                                'tooltip' => __('diccionario.icono_borrar'),
                                                'titulo' => __('diccionario.confirm_titulo'),
                                                'mensaje' => __('diccionario.comentarios_add_confirm_borrar'),
                                                'action' => route('comentario.delete', [$datos->dicAula->id, $comentarioGeneral->id]),
                                                'onDone' => "$('#comentarioGeneral_id_" . $comentarioGeneral->id . "').remove();",
                                                'class' => 'comentarioIco'
                                                ])
                                            </div>
                                        </div>
                                        <div class="card-body comentario-diccionario-body" id="comentarioGeneral_{{ $comentarioGeneral->id }}">
                                            <div class="comentario-texto-letra">{!! $comentarioGeneral->comentario !!}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                <div class="form-group col-md-1">
                </div>

            </div>


            {{-- Botón Volver --}}
            <div class="form-group offset-1 col-10 col-11-sm">
            <div class="text-center" id="botonesFooter">

                {{-- <button type="submit" class="btn btn-primary">@lang('diccionario.acepcion_guardar')</button> --}}
                <a href="{{ route('diccionarioaula.get') }}" class="btn btn-primary">
                    @lang('diccionario.boton_volver')</a>
                {{-- <a href="{{ route('entrada.get', ['diccionario_id' => $datos->entrada->dic_personal_id, 'entrada_id' => $datos->acepcion->dic_entrada_id]) }}" class="btn btn-danger">@lang('diccionario.acepcion_cancelar')</a> --}}

            </div>
            </div>
        </form>

    {{-- </div> --}}
</div>

<script>
    // Función que recorre los checkboxes y si está marcados concatena sus id separándolo por comas y los guarda en el hidden listaDiccionariosAula para que luego se mande esto por POST
    function handleChange(radioButton, idOriginal) {
        if (radioButton.value == 'opt_nuevo_comentario') {
            $('#tinyComentario').val('');
            $('#hiddenComentarioOriginalId').val('');
        } else {
            let comentarioOriginal = $('#hiddenComentarioOriginalComentario').val();
            $('#tinyComentario').val(comentarioOriginal);
            $('#hiddenComentarioOriginalId').val(idOriginal);
        }
    }

    function checkEstudiante() {
        var estudianteVal = $('#estudiante').val();
        // console.log("val", estudianteVal )
        ocultarTodosLosComentarios();
        if ( $('#estudiante').val()  && $('#estudiante').val()!=''){
            $('#btnNuevoComentario').removeClass('d-none');
            $('#lbComentariosGuardados').removeClass('d-none');
            
            var idEstudiante = $('#estudiante').val();
            $('div[data-persona_id="' + idEstudiante + '"]').show();
        } else {
            $('#btnNuevoComentario').addClass('d-none');
            $('#lbComentariosGuardados').addClass('d-none');
            $('#grpNuevoComentario').addClass('d-none');
            
        }
    }

    function descartar() {
        // let radioButton = $("input[name=radio]:checked").val();
        // if (radioButton == 'opt_nuevo_comentario') {
        $('#tinyComentario').val('');
        tinyMCE.activeEditor.setContent('');
        $('#grpNuevoComentario').addClass('d-none');
        $('#lbComentariosGuardados').addClass('d-none');
        $('#botonesFooter').show();

        // } else {
        //     let comentarioOriginal = $('#hiddenComentarioOriginalComentario').val();
        //     $('#tinyComentario').val(comentarioOriginal);
        // }
    }

    function filtrarComentarios(optionbutton) {
        console.log('filtrar comentarios', optionbutton.value);
        idEstudiante = optionbutton.value;
        ocultarTodosLosComentarios()
        if (idEstudiante && idEstudiante!==''){            
            $('div[data-persona_id="' + idEstudiante + '"]').show();
        }        
    }

    function ocultarTodosLosComentarios() {
        $('.div_comentario').hide();
        $('#botonesFooter').show();
    }

    $('#btnNuevoComentario').click( (ev) => {
        $('#grpNuevoComentario').removeClass('d-none');
        $('#lbComentariosGuardados').removeClass('d-none');
        $('#botonesFooter').hide();
    })

    $('#estudiante').change( (ev) => {
        checkEstudiante();
    })

    checkEstudiante();


    var orden = {
        'original': 0,
        'alfabetico': 1,
        'fecha': 2,
        'alfabeticoDescendente': 3,
        'fechaDescendente': 4,
    }
    var ordenEntradas = orden.original;
    var ordenGenerales = orden.original;

    var imgAsc = $('<img />', {
    src: '{{ asset('/imagenes/icono-bajar.svg') }}',
        width: '17px',
        height: '17px'
    });
    var imgDesc = $('<img />', {
    src: '{{ asset('/imagenes/icono-subir.svg') }}',
        width: '17px',
        height: '17px'
    });

    function ordenarGeneralesPorFecha(el) {
        console.log( 'ordenar comentarios generales por fecha ', ordenGenerales );
        // console.log( 'ordenar alfabeticamente ', ordenEntradas );
        $(el).find('img').remove();
        if ( ordenGenerales != orden.fecha ) {
            imgAsc.appendTo(el);
            ordenGenerales = orden.fecha;
            $('#comentariosGenerales div.sortme').sort(function(a, b) {                
                return (a.dataset.date < b.dataset.date)? -1 : 1;
            }).appendTo('#comentariosGenerales');
        } else {
            imgDesc.appendTo(el);
            ordenGenerales = orden.fechaDecendente;
            $('#comentariosGenerales div.sortme').sort(function(a, b) {                
                return (a.dataset.date < b.dataset.date)? 1 : -1;
            }).appendTo('#comentariosGenerales');
        }        
    }

</script>

@endsection