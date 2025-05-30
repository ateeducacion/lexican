<!DOCTYPE html>
@extends('diccionario.home')
@section('title', __('diccionario.diccionario_aula') . ' ' . __('diccionario.ver_publicar_entradas') )
<link rel="stylesheet" href="{{ asset('css/swiper-bundle.min.css') }}">

@section('content')
    @parent
    <div id="alertas"></div>
    @if(!isset($entradas) || count($entradas) == 0)
        <div class="alert alert-info">
            {{ __('diccionario.entradasAula_no_enviado_entrada') }}
        </div>
    @endif
    <h5>{{__('diccionario.ver_publicar_entradas')}} "{{ getDicAulaActivo()->titulo?? '' }}" </h5>
    {{-- Panel del buscador y filtros --}}
    <div class="panel-buscador card dc-panel-gris position-relative">
        <form method="POST" id="dicAulaPanelBuscador" action="{{ url()->current() }}">
            @csrf
            
            <div id="buscadorAvanzado" class="position-absolute col-11 col-md-6" >
                <div class="row">
                    <div class="col-12">
                        <div id="buscarNombreContainer" class="input-group dc-input-group ">
                            <input autocomplete="off" name="buscarNombre" id="buscarNombre" type="text" class="form-control" placeholder="{{__('diccionario.Buscar')}}" aria-label="{{__('diccionario.Buscar')}}">
                            
                            {{-- <input name="buscarNombre" id="buscarNombre" type="text"> --}}
                            {{-- limpiar filtros busqueda --}}
                            <div class="clearSearch">
                                <i data-toggle="tooltip" 
                                title="{{ __('diccionario.entradasAula_limpiarFiltros') }}"
                                data-placement="top" class="bi bi-x"></i>
                                
                                {{-- <i data-toggle="tooltip" 
                                title="{{ __('diccionario.entradasAula_limpiarFiltros') }}"
                                data-placement="top" class="bi bi-x-circle-fill"></i> --}}
                            </div>
                                

                            {{-- <div class="input-group-append" > --}}
                                <div>
                                    <img class="opacity-1" src="{{ asset('imagenes/search.svg') }}">
                                </div>
                                    
                                <button id="busquedaAvanzadaBtn" class="m-0 p-0" >
                                    <img 
                                        style="position: relative"
                                        src="{{ asset('imagenes/ico-flecha.svg') }}" data-toggle="tooltip" 
                                        title="{{ __('diccionario.buscadorAvanzado_tooltip') }}"
                                        data-placement="right">
                                </button>
                                {{-- <button class="submit" type="button" >
                                    <img src="{{ asset('imagenes/lupa.svg')}}"></button> --}}
                            {{-- </div> --}}
                            
                        </div>
                    </div>
                </div>
                <div class="contenidoAvanzado ">
                    <div class="row espacio-top">
                        <div class="col-sm-12 col-md-2">
                            <span>{{__('Estado')}}</span>
                        </div>
                        <div class="col-12 col-md">
                            <div class="dc-select-grp d-inline-block float-none p-0 mx-md-0 mx-1">
                                <select style="padding: 0px 0px 0 10px;" class="form-control input-group-append" name="estado" id="estado">
                                    <option value="0">-- {{__('Seleccionar')}} --</option>                               
                                    <option value="{{ config('ctes.estados_envios.publicado') }}">
                                        {{ __('diccionario.estadoEnvio_publicado') }}
                                    </option>
                                    <option value="{{ config('ctes.estados_envios.enviado') }}">
                                        {{ __('diccionario.estadoEnvio_noPublicado') }}
                                    </option>
                                    {{-- es lo mismo que dejarla en sellecionar por eso dejo el mismo id  --}}
                                    <option value="0">
                                        {{ __('diccionario.estadoEnvio_todas') }}
                                    </option>
                                </select>
                                <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}" style="right: 0">
                            </div>
                        </div>
                    </div>
                    <div class="row espacio-top">
                        <div class="col-sm-12 col-md-2">
                            <span>{{__('Participante')}}</span>
                        </div>
                        <div class="col-12 col-md">
                            <div class="dc-select-grp d-inline-block float-none p-0 mx-md-0 mx-1">
                                <select style="padding: 0px 0px 0 10px;" class="form-control input-group-append" name="estudiante" id="estudiante">
                                    <option value="0">-- {{__('Seleccionar')}} --</option>
                                    
                                    @if(isset($entradas) && count($entradas) > 0)
                                    @foreach($entradas->first()->dpEnvio->dicAula->participantes->sortBy('persona.nombre') as $estudiante)
                                    <option value="{{$estudiante->persona->id}}">{{$estudiante->persona->nombre}} {{$estudiante->persona->apellidos}}</option>
                                    @endforeach
                                    @endif
                                </select>
                                <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}" style="right: 0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row espacio-top">
                        <div class="col-sm-12 col-md-2">
                            <span>{{__('diccionario.Envio')}}</span>
                        </div>
                        <div class="col-12 col-md" id="buscadorFechas">
                            <div class="row">
                                <div class="col-2 col-md-2">
                                    {{ __('diccionario.Desde') }}
                                </div>
                                <div class="col-10 col-md-4">
                                    <input type="date" name="desde" id="desde" class="form-control input-group-append" >
                                </div>
                                <div class="col-2 col-md-2">
                                    {{ __('diccionario.Hasta') }}
                                </div>
                                <div class="col-10 col-md-4">
                                    <input type="date" name="hasta" id="hasta" class="form-control input-group-append" >
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>

            @if(isset($entradas) && count($entradas) > 0)
                <input id="nParticipantes" type="hidden" value="{{count($entradas->first()->dpEnvio->dicAula->participantes) }}">
            @endif

            <div class="row">
                <div class="swiper-container">
                    <!-- Additional required wrapper -->
                    <div class="swiper-wrapper">
                        @if(isset($entradas) && count($entradas) > 0)                        
                            @foreach($entradas->first()->dpEnvio->dicAula->participantes->sortBy('persona.nombre') as $estudiante)                        
                            <!-- Slides -->
                            <div class="swiper-slide" data-id="{{$estudiante->persona->id}}">
                                <!-- Checkbox -->
                                <input class="green-checker-check d-none" type="radio" name="avatares" id="chk_{{ $estudiante->persona->id }}" />
                                <label class="green-checker-label" for="chk_{{ $estudiante->persona->id }}">
                                    <div>
                                    </div>
                                    {{-- <img src="{{asset('imagenes/'.$estudiante->persona->personaUser->avatar)}}" alt=""> --}}
                                    <img src="{{ URL::to('/') . '/storage/avatares/oficiales/' . $estudiante->persona->avatar_URL }}" alt="">
                                </label>
                                <div class="nombre-y-apellidos small" data-toggle="tooltip" data-placement="right" title="{{$estudiante->persona->nombre}} {{$estudiante->persona->apellidos}}" >
                                    {{$estudiante->persona->nombre}}
                                </div>
                            </div>
                            @endforeach
                        @endif
                    </div>
                    <!-- If we need pagination -->
                    <!--<div class="swiper-pagination"></div>-->
                
                    <!-- If we need navigation buttons -->
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                
                    <!-- If we need scrollbar -->
                    <!--<div class="swiper-scrollbar"></div>-->
                </div>
                <input name="selectedSliderEstudiante" id="selectedSliderEstudiante" type="hidden">                
            </div>
            <div class="row" style="margin: 0">
                {{-- Seleccion letras Abecedario --}}
                @include('layouts.partials.components.abecedario', [
                    'route'=> 'personal.consulta.byInitial',
                    'event' => 'seleccionaLetra',
                    'ajax'=> 1])
                <input name="selectedLetra" id="selectedLetra" type="hidden">
            </div>
            <div class="row" style="margin-top: 1rem">
                <div class="col-12 div-sm-12 text-center">
                    <button class="btn btn-primary mx-auto submit" type="button" >{{ __('Buscar') }}</button>
                </div>
            </div>
        </form>
    </div>
    <div id="loader" class="text-center">
        <div class="spinner-border" role="status">
            <span class="sr-only">Cargando...</span>
        </div>
    </div>
    <div id="dicAulaResultados"> </div>

    <!-- Modal play media -->
    <div class="modal fade" id="mediaModal" role="dialog">
        <div class="modal-dialog">
    
            <!-- Modal content-->
            <div class="modal-content" style="">
                <div class="modal-header">
                    <h4 class="modal-title">titulo</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="text-align: center;">
    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-danger" data-dismiss="modal">@lang('diccionario.boton_cerrar')</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('scripts')
{{-- variables para imagenes fechas en  ordenar.js --}}
<script>

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

</script>
@endsection