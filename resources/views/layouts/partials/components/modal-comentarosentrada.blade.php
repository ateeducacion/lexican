@extends('layouts/partials/components/modal-simpleyield')

@section('modal-title', __('diccionario.icono_vercomentarios'))


@section('modal-body')

<div class="card comentario-modal-card-principal text-center">
    <div class="card-body">
        {{-- <h5 class="card-title"></h5> --}}
        <!-- <p class="card-text">With supporting text below as a natural lead-in to additional content.</p> -->

        <form class="my-form" method="POST" action="{{ route('diccionario.send', ['diccionario_id'=> $datos->diccionario->id]) }}" enctype="multipart/form-data">

            @csrf

            <input type="hidden" id="listaDiccionariosAula" name="listaDiccionariosAula" value="" />

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
                    <ul>
                        @foreach($datos->listaDiccionariosAula as $diccionarioAula)
                        <!--
                        {{-- BORRAME --}}
                        @if( $diccionarioAula->estadoActual == config('ctes.comentarios_visibles.visible') )
                            Comentarios visibles
                        @endif
                        dic aula->comentarios visibles : {{ $diccionarioAula->comentarios_visibles }}
                        -->                        
                            @if($diccionarioAula->estadoActual == config('ctes.comentarios_visibles.visible'))
                                <li class="libroDiccionarioVerde">
                                @else
                                <li class="libroDiccionarioGris" data-toggle="tooltip" title="{{ __('diccionario.tooltip_envios_deshabilitados') }} " data-placement="bottom" >
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
                            <div class="comentario-texto-letra">{{ $diccionarioAula->titulo }}<br /></div>
                            {{-- <div class="comentario-texto-letra">{{ $diccionarioAula->profesor->nombreCompleto() }}</div> --}}
                            </li>
                        @endforeach
                    </ul>
                </div>

            </div>

            <div class="form-row">
                <br />
            </div>


            {{-- Comentarios Entradas --}}
            <div class="row">
                <div class="col-md-1">
                </div>
                <div class="col-md-11 text-left comentario-seccion-letra">
                    {{ $titulo_comentarios }}
                </div>
            </div>

            <div class="form-row">

                <div class=" form-group col-md-1">
                </div>
                <div class="form-group col-md-10">

                    <div class="card bg-light text-left comentario-card-sinborde">
                        <div class="card-body comentario-modal-card-body">

                            @foreach(daGetComentariosEntradasByEntradaByComentariosVisible($datos->entrada) as $comentarioEntrada)
                                <div id="comentarioEntrada_dicAula_{{ $comentarioEntrada->dic_aula_id }}" data-comentario_id="{{ $comentarioEntrada->id }}"" style=" display: none;">
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


        </form>

    </div>
</div>


<script>
    // Función que recorre los checkboxes y si está marcados concatena sus id separándolo por comas y los guarda en el hidden listaDiccionariosAula para que luego se mande esto por POST
    function handleChange(radioButton) {
        ocultarTodosLosDiccionarios();

        var dicId = radioButton.dataset.dataid;
        $('[id^=comentarioEntrada_dicAula_' + dicId + ']').show();
    }

    function ocultarTodosLosDiccionarios() {
        $('[id^=comentarioEntrada_dicAula_]').hide();
    }

    // Si sólo hay un diccionario verde lo marca
    jQuery(function($) {
        if ($('[id^=chk_]').length == 1) {
            $('[id^=chk_]').prop('checked', true);
            handleChange($('[id^=chk_]')[0])
        }
    });
</script>
@overwrite




    @section('modal-footer')

    <button type="button" class="btn btn-primary" data-dismiss="modal">
        @lang(__('diccionario.boton_volver'))
    </button>

    @overwrite