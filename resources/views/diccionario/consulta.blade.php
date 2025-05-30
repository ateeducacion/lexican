@extends('diccionario/home')

@section('title', 'Todas las entradas ')

@section('content')

@if( session('noExisteEntrada') )
    <p class="dc-noexiste alert alert-info">
        {{  __("diccionario.buscar_noencotrado") }} 
         "{{$datos->consultaMsg?? $datos->consulta }}"
        @if($datos->list_tematica_id)        
            {{ trans_choice('diccionario.buscador_noexisteEnCategoria', count($datos->list_tematica_id )) }}
        @endif
        <a class="btn btn-sm btn-link btn-primary mx-3" 
            href="{{route('entrada.insert')}}?entrada_entrada={{$datos->consulta}}" >Crear entrada</a>
    </p>
@else 
    {{-- Si no existe pero no podomes crear entrada --}}
    {{-- Muestra tambien el " No se encontró ningún resultado "Por Letra E" " --}}
    @if( session('noResultados') || !$datos->listado || count($datos->listado) == 0)
        <p class="dc-noexiste alert alert-info">
        {{  __("diccionario.buscar_ceroEntradas") }} 
        @if(isset( $datos->consulta ))
        "{{$datos->consulta}}"
        @endif
        </p>
    @endif
@endif



@parent


{{-- Seleccion letras Abecedario --}}
@include('layouts.partials.components.abecedario', [
    'route'=> 'personal.consulta.byInitial', 
    'letraSel'=>$datos->letra ?? ''
])
{{-- Buscador --}}
@include('layouts.partials.components.buscador')

{{-- Numero de entradas --}}
@include('layouts.partials.nentradas', [ 'texto' => 'Ver todas las entradas de '. $datos->diccionario->titulo ])


@if(isset( $datos->consulta ))
@section('title', 'Resultados ' . $datos->consulta )
@endif

@include('layouts.partials.components.actionbuttons-iconossvg')
<div class="row">
    <!-- LISTADO -->
    <div class="col-md-8 col-lg-9 col-xl-10">
        <span id="listadoEntradas">
        @foreach($datos->listado as $item )
            @include('layouts.partials.consulta.entrada', [
            'entrada'=> $item->entrada,
            'confirm' => $item->confirm,
            'listaDiccionariosAula' => $datos->listaDiccionariosAula,
            'curso_escolar' => $datos->curso_escolar,
            'nivelEstudios' => $datos->nivelEstudios,
            ])
        @endforeach
        </span>
        <div id="loader" class="text-center">
            <div class="spinner-border" role="status">
                <span class="sr-only">Cargando...</span>
              </div>        
        </div>
    </div>

    {{-- Botones acciones --}}
    <div class="col-md-4 col-lg-3 col-xl-2">
        @include('layouts.partials.components.actionbuttonPersonal')
    </div>

    

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
<script>
var rutaActual = '{{ Route::currentRouteName() }}';
@switch( Route::currentRouteName() ) 
    @case('personal.consulta.byInitial')
        window.routeMasEntradas = '{{ route('personal.consulta.ajaxLetra', [
            'offset'=> ($datos->offset ?? 0),
            'letra' => $datos->letra
        ]) }}';
    
        @break

    @case('personal.consulta.palabra')
        window.routeMasEntradas = '{{ route('personal.consulta.ajaxConsulta', [
            'consulta'=> $datos->consulta,
            'offset'=> ($datos->offset ?? 0),
        ]) }}';
        @break

    @case('personal.consulta.palabra.tematica')    
        window.routeMasEntradas = '{{ route('personal.tematica.consulta.ajaxConsulta', [
            'consulta'=> $datos->consulta,
            'offset'=> ($datos->offset ?? 0),
            'tematicasIds' => $datos->tematicasIds
        ]) }}';
    @break

    @case('personal.consulta.tematica')
        window.routeMasEntradas = '{{ route('personal.tematica.ajaxConsulta', [
            'offset'=> ($datos->offset ?? 0),
            'tematicasIds' => $datos->tematicasIds
        ]) }}';
    @break

    @default
        window.routeMasEntradas = '{{ route('personal.consulta.ajax', [
            'offset'=> ($datos->offset ?? 0) 
        ]) }}';

@endswitch     
    
    // numero por el que empieza , se actualiza en consultaEntadaScroll.js
    window.offset = {{($datos->offset ?? 0)}};
    // numero de entradas que se carga por vez
    window.nentradas = {{($datos->offset ?? 0)}};
</script>
@append
