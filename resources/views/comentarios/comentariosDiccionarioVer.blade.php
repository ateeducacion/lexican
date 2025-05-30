@extends('diccionario/home')

@section('title', __('diccionario.comentarios_diccionario_titulo'))
@section('content')
    @parent
{{-- @include('layouts.partials.comentarios.comentariosFormulario', [
'modal_entrada' => false,
'clase_comentario_card_principal' => 'comentario-nomodal-card-principal',
'clase_comentario_card_body'=> 'comentario-nomodal-card-body',
'titulo_comentarios' => __('diccionario.comentarios_diccionario_entradas'),
]) --}}




<div class="row">
    <div class="col-md-12">
        <h5 class="card-title">
            {{-- Mensaje: VER COMENTARIOS DEL DOCENTE --}}
            <strong>@lang('diccionario.comentarios_diccionario_titulo')</strong>
        </h5>
    </div>
</div>
@include('layouts.partials.components.actionbuttons-iconossvg')

<div class="row">
<div class="text-center col-md-8 col-lg-9 col-xl-10">
    <div class="card dc-panel-gris">
        {{-- <h5 class="card-title"></h5> --}}
        <!-- <p class="card-text">With supporting text below as a natural lead-in to additional content.</p> -->

        <form class="my-form" method="POST" action="{{ route('diccionario.send', ['diccionario_id'=> $datos->diccionario->id]) }}" enctype="multipart/form-data">

            @csrf

            <input type="hidden" id="listaDiccionariosAula" name="listaDiccionariosAula" value="" />

            <div class="form-row">
                <br />
            </div>

            {{-- Selecciona los diccionarios --}}
            <div class="form-row">
                <div class="form-group col-md-12 comentario-seccion-letra">
                    @lang('diccionario.comentarios_diccionario_selecciona')<br />
                </div>
            </div>

            <div class="form-row">
                <br />
            </div>

            <div class="form-row">
                <br />


                {{-- Listado de diccionarios de aula a los que el user está unido --}}

                <div class="form-group col-md-12">
                    <ul class="listaDiccionarios">
                        @foreach($datos->listaDiccionariosAula as $diccionarioAula)
                            {{-- Si no tiene comentarios o no estan visibles para el alumno  --}}
                            @if($diccionarioAula->estadoActual == config('ctes.comentarios_visibles.visible'))
                                <li class="libroDiccionarioVerde">
                            @else
                                <li class="libroDiccionarioGris" data-toggle="tooltip" title="{{ __('diccionario.diccionario_sin_comentarios') }} " data-placement="bottom" >
                            @endif

                            @if($diccionarioAula->estadoActual == config('ctes.comentarios_visibles.visible'))
                                <input input type="radio" name="radio" id="chk_{{ $diccionarioAula->id }}" value="chk_{{ $diccionarioAula->id }}" data-dataid="{{ $diccionarioAula->id }}" autocomplete="off" onchange='handleChange(this)' />
                                <label for="chk_{{ $diccionarioAula->id }}">
                                    <img class="ico-libro-verde" src="" alt="{{ $diccionarioAula->titulo }}" />
                                </label>
                            @else
                                <label for="chk_{{ $diccionarioAula->id }}">
                                    <img src="{{ asset('imagenes/ico-libro-gris.svg') }}" />
                                </label>
                            @endif
                            <div class="comentario-texto-letra" 
                            data-toggle="tooltip" title="{{ $diccionarioAula->profesor->nombreCompleto() }}"
                            data-placement="bottom"
                            >{{ $diccionarioAula->titulo }}<br /></div>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>

            <div class="form-row">
                <br />
            </div>


            {{-- Comentarios Generales --}}
            @if( $datos->comentariosGenerales->count() == 0 )
            {{-- @if( daGetComentariosGeneralesAlumnoAula( getSessionPersona(), getDicAulaActivo() )->count()==0 ) --}}
            <div class="row">
                <div class="col-md-10 offset-md-1 text-left comentario-seccion-letra">
                {{ mb_ucfirst( __('diccionario.no_hay_comentarios') )  }} generales
                </div>
            </div>
            @else
            <div class="row">

                <div class="col-md-10 offset-md-1 text-left comentario-seccion-letra">
                    @lang('diccionario.comentarios_diccionario_generales')
                    <div class="float-right dc-fontsize2 " style="top:4px;position: relative;">
                        <button class="dc-round-btn ml-1 dc-btn-ordenar" onclick="ordenarGeneralesPorFecha(this)" type="button" >
                            <div class="d-inline-block mr-2">{{ __('diccionario.por_fecha') }}</div>  
                        </button>
                        
                        <button class="dc-round-btn dc-btn-ordenar" onclick="ordenarGeneralesAlfabeticamente(this)" type="button">
                            <div class="d-inline-block mr-2">{{ __('diccionario.alfabeticamente') }}</div>
                        </button>
                    </div>       
                </div>
            </div>

            <div class="form-row">

                <div class=" form-group col-md-1">
                </div>
                <div class="form-group col-md-10">

                    <div class="text-left">
                        <div id="comentariosGenerales" class="">

                                @foreach( $datos->comentariosGenerales as $index => $comentarioGeneral)
                                    <div 
                                    class="sortme"
                                    data-title="{{ substr($comentarioGeneral->comentario,0,5) }}" 
                                    data-index="{{ $index }}" 
                                    data-date="{{ $comentarioGeneral->fecha_envio->format('Ymd') }}"
                                    id="comentarioGeneral_dicAula_{{ $comentarioGeneral->dic_aula_id }}" data-comentario_id="{{ $comentarioGeneral->id }}">
                                        <div class="card comentario-diccionario-card">
                                            <div class="card-header comentario-diccionario-header">
                                                <div class="comentario-texto-letra">
                                                    {{ $comentarioGeneral->fecha_envio->format('d/m/Y') }}
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
                </div>
                <div class="form-group col-md-1">
                </div>

            </div>
            @endif


            {{-- Comentarios Entradas --}}
            @if( $datos->comentariosEntradas->count() == 0 )
            <div class="row">
                <div class="offset-md-1 col-md-10 text-left comentario-seccion-letra">
                    {{ mb_ucfirst( __('diccionario.no_hay_comentarios') )  }} comentarios a entrada
                </div>
            </div>
            @else
            <div class="row">
                <div class="offset-md-1 col-md-10 text-left comentario-seccion-letra">
                    {{ __('diccionario.comentarios_diccionario_entradas') }}
                    <div class="float-right dc-fontsize2" style="top:4px;position: relative;">
                        <button class="dc-round-btn ml-1 dc-btn-ordenar" onclick="ordenarPorFecha(this)" type="button" >
                            <div class="d-inline-block mr-2">{{ __('diccionario.por_fecha') }}</div>
                        </button>
                        <button class="dc-round-btn dc-btn-ordenar" onclick="ordenarAlfabeticamente(this)" type="button">
                            <div class="d-inline-block mr-2">{{ __('diccionario.alfabeticamente') }}</div>
                        </button>
                    </div>                    
                </div>
            </div>
            <div class="form-row">
                <div class="offset-md-1 form-group col-md-10">

                    <div class="text-left">
                        <div id="listadoComentarios" class="">
                            {{-- {{ dd(daGetComentariosEntradasByComentariosVisible()) }}
                            {{ daGetComentariosEntradasByComentariosVisible()->count() }} --}}
                            
                            @foreach( $datos->comentariosEntradas as $index => $comentarioEntrada )

                            
                                <div 
                                    class="sortme"
                                    id="comentarioEntrada_dicAula_{{ $comentarioEntrada->dic_aula_id }}" data-comentario_id="{{ $comentarioEntrada->id }}" 
                                    data-title="{{ $comentarioEntrada->envioEntrada->entrada }}" 
                                    data-index="{{ $index }}" 
                                    data-date="{{ $comentarioEntrada->fecha_envio->format('Ymd') }}"
                                    style="display: none;"
                                >
                                    
                                    <div class="card comentario-diccionario-card">
                                        <div class="card-header comentario-diccionario-header">
                                            <div class="comentario-texto-letra">
                                                {{ $comentarioEntrada->fecha_envio->format('d/m/Y') }} -
                                                <strong>{{ $comentarioEntrada->envioEntrada->entrada }}</strong>
                                            </div>
                                        </div>
                                        <div class="card-body comentario-diccionario-body" id="comentarioEntrada_{{ $comentarioEntrada->id }}">
                                            <div class="comentario-texto-letra">{!! $comentarioEntrada->comentario !!}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            
                        </div>
                    </div>
                </div>
                <div class="form-group col-md-1">
                </div>
            </div>
            @endif


            {{-- Separador de los Botones del formulario --}}
            {{-- <div class="form-row">
                    <br />
                </div> --}}

            {{-- Botones del formulario --}}
            <div class="text-center mt-2">
                <a href="{{ url()->previous() }}" class="btn btn-primary">@lang('diccionario.boton_volver')</a>
            </div>

        </form>

        <div class="form-row">
            <br />
        </div>

    </div>
</div>
<div class="col-md-4 col-lg-3 col-xl-2">
    @include('layouts.partials.components.actionbuttonPersonal')
</div>


<script>
    // Función que recorre los checkboxes y si está marcados concatena sus id separándolo por comas y los guarda en el hidden listaDiccionariosAula para que luego se mande esto por POST
    function handleChange(radioButton) {
        ocultarTodosLosDiccionarios();

        var dicId = radioButton.dataset.dataid;
        $('[id^=comentarioGeneral_dicAula_' + dicId + ']').show();
        $('[id^=comentarioEntrada_dicAula_' + dicId + ']').show();
    }

    function ocultarTodosLosDiccionarios() {
        $('[id^=comentarioGeneral_dicAula_]').hide();
        $('[id^=comentarioEntrada_dicAula_]').hide();
    }

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

    function ordenarAlfabeticamente(el){
        // console.log( 'ordenar alfabeticamente ', ordenEntradas );
        // $(el).find('img').remove();
        // $(el).parent().find('img').remove();
        // console.log( 'elemento', $(el).parent() );
        var botones = $(el).parent();
        botones.find('img').remove();

        if ( ordenEntradas != orden.alfabetico ) {
            imgAsc.appendTo(el);
            ordenEntradas = orden.alfabetico;
            $('#listadoComentarios div.sortme').sort(function(a, b) {                
                return (a.dataset.title < b.dataset.title)? -1 : 1;
            }).appendTo('#listadoComentarios');
        } else {
            imgDesc.appendTo(el);
            ordenEntradas = orden.alfabeticoDescendente;
            $('#listadoComentarios div.sortme').sort(function(a, b) {                
                return (a.dataset.title < b.dataset.title)? 1 : -1;
            }).appendTo('#listadoComentarios');
        }        
    }

    function ordenarPorFecha(el) {
        // console.log( 'ordenar por fecha  ', ordenEntradas );
        // console.log('orden fecha orden.fechaDecendente', orden.fechaDescendente );
        // console.log( 'elemento', $(el).parent() );
        // $(el).parent().find('img').remove();
        var botones = $(el).parent();
        botones.find('img').remove();

        console.log( '( ordenEntradas != orden.fecha )', ( ordenEntradas !== orden.fecha ) );

        if ( ordenEntradas !== orden.fecha  ) {
            console.log('no');
            imgAsc.appendTo(el);
            ordenEntradas = orden.fecha;
            $('#listadoComentarios div.sortme').sort(function(a, b) {                
                return (a.dataset.date < b.dataset.date)? -1 : 1;
            }).appendTo('#listadoComentarios');
        } else {

            imgDesc.appendTo(el);
            ordenEntradas = orden.fechaDescendente;
            console.log( 'orden entradas: ',ordenEntradas, orden.fechaDescendente );
            $('#listadoComentarios div.sortme').sort(function(a, b) {                
                return (a.dataset.date < b.dataset.date)? 1 : -1;
            }).appendTo('#listadoComentarios');
        }        
    }

    function ordenarGeneralesAlfabeticamente(el) {
        // console.log( 'ordenar comentarios generales alfabeticamente ', ordenGenerales );
        // $(el).parent().find('img').remove();
        var botones = $(el).parent();
        botones.find('img').remove();
        console.log( 'elemento', $(el).parent() );
        if ( ordenGenerales != orden.alfabetico ) {
            imgAsc.appendTo(el);

            ordenGenerales = orden.alfabetico;
            $('#comentariosGenerales div.sortme').sort(function(a, b) {                
                return (a.dataset.title < b.dataset.title)? -1 : 1;
            }).appendTo('#comentariosGenerales');
        } else {
            imgDesc.appendTo(el);

            ordenGenerales = orden.alfabeticoDescendente;
            $('#comentariosGenerales div.sortme').sort(function(a, b) {                
                return (a.dataset.title < b.dataset.title)? 1 : -1;
            }).appendTo('#comentariosGenerales');
        }        
    }

    function ordenarGeneralesPorFecha(el) {
        // console.log( 'ordenar comentarios generales por fecha ', ordenGenerales );
        // console.log( 'elemento', $(el).parent() );        
        var botones = $(el).parent();
        botones.find('img').remove();

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

    // Si sólo hay un diccionario verde lo marca
    jQuery(function($) {
        if ($('[id^=chk_]').length == 1) {
            $('[id^=chk_]').prop('checked', true);
            handleChange($('[id^=chk_]')[0])
        }
    });

    ocultarTodosLosDiccionarios();
</script>

@endsection