@extends('diccionario/home')

@section('title', 'Todas las entradas ')

@section('content')

@if( session('noExisteEntrada') )
    <p class="dc-noexiste alert alert-info">
        {{  __("diccionario.buscar_noencotrado") }} "{{$datos->consultaMsg?? $datos->consulta}}"

        datos: {{ $datos->list_tematica_id }}<br>
        list tematica {{ list_tematica_id }}<br>
        @if(!$datos->list_tematica_id)
            <a class="btn btn-sm btn-link btn-primary mx-3" 
                href="{{route('entrada.insert')}}?entrada_entrada={{$datos->consulta}}" >Crear entrada</a>
        @else
            {{ trans_choice('diccionario.buscador_noexisteEnCategoria', $datos->list_tematica_id ) }}
        @endif

        {{-- Si se enta filtradon por categoria agregar "en las catgerio" --}}
    </p>
@endif
@if( session('noResultados') || !$datos->listado || count($datos->listado) == 0)
    <p class="dc-noexiste alert alert-info">
       {{  __("diccionario.buscar_ceroEntradas") }} 
       @if(isset( $datos->consulta ))
       "{{$datos->consulta}}"
       @endif
    </p>
@endif

@parent

{{-- Menu seleccionar diccionario de aula activo --}}
@include('layouts.partials.components.select-DicAula')


{{-- Seleccion letras Abecedario --}}
@include('layouts.partials.components.abecedario', [
    'route'=> 'aula.consulta.byInitial',
    'letraSel'=>$datos->letra ?? ''])

{{-- Buscador --}}
@include('layouts.partials.components.buscador', ['aula'=>true] )

{{-- Numero de entradas --}}
@include('layouts.partials.nentradas')


@if(isset( $datos->consulta ))
@section('title', 'Resultados ' . $datos->consulta )
@endif
@include('layouts.partials.components.actionbuttons-iconossvg')
<div class="row">
    <!-- LISTADO -->
    {{-- ancho completo para el alumno, espacio para los botones para el docente --}}
    @can( 'createDicAula', App\DicAula::class )
    <div class="col-md-8 col-lg-8 col-xl-10">
    @endcan
    @cannot( 'createDicAula', App\DicAula::class )
    <div class="col-md-12">
    @endcannot

        <span id="listadoEntradas">
        @foreach($datos->listado as $item )
            @include('layouts.partials.consulta.entradaAula', [
            'entrada'=> $item->entrada,
            // 'confirm' => $item->confirm,
            'listaDiccionariosAula' => $datos->listaDiccionariosAula,
            // 'centro_denominacion' => $datos->centro_denominacion,
            'curso_escolar' => $datos->curso_escolar,
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
    @can( 'createDicAula', App\DicAula::class )
     
    @include('layouts.partials.components.actionbuttonAula')
@endcan



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
    @case('aula.consulta.byInitial')
        window.routeMasEntradas = '{{ route('aula.consulta.ajaxLetra', [
            'offset'=> ($datos->offset ?? 0),
            'letra' => $datos->letra
        ]) }}';    
    @break
    @case('aula.consulta.palabra')
        window.routeMasEntradas = '{{ route('aula.consulta.ajaxConsulta', [
            'consulta'=> $datos->consulta,
            'offset'=> ($datos->offset ?? 0),
        ]) }}';
    @break

    @case('aula.consulta.palabra.tematica')    
        window.routeMasEntradas = '{{ route('aula.tematica.consulta.ajaxConsulta', [
            'consulta'=> $datos->consulta,
            'offset'=> ($datos->offset ?? 0),
            'tematicasIds' => $datos->tematicasIds
        ]) }}';
    @break

    @case('aula.consulta.tematica')
        window.routeMasEntradas = '{{ route('aula.tematica.ajaxConsulta', [
            'offset'=> ($datos->offset ?? 0),
            'tematicasIds' => $datos->tematicasIds
        ]) }}';
    @break
    
    @default
        window.routeMasEntradas = '{{ route('aula.consulta.ajax', [
            'offset'=> ($datos->offset ?? 0) 
        ]) }}';
@endswitch
    // numero por el que empieza , se actualiza en consultaEntadaScroll.js
    window.offset = {{($datos->offset ?? 0)}};
    // numero de entradas que se carga por vez
    window.nentradas = {{($datos->offset ?? 0)}};
</script>
@append
