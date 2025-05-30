@extends('diccionario/home')

@section('title', $datos->entrada->entrada. ' - ' .__('diccionario.daEntradaEdit_titulo') )

@section('content')
@parent


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
        <div>
            {{-- Editar nombre de entrada --}}
            <h5 class="card-title">{{ __('diccionario.daEntradaEdit_titulo') }}</h5>
        </div>

        <div class="dc-panel-gris card" >

            
            <div class="card-body">

                <form method="POST" 
                    action="{{ route('aula.entrada.update', [
                        'diccionario_id' => $datos->entrada->dpEnvio->dic_aula_id, 
                        'entrada_id' => $datos->entrada->id]) 
                }}" >
                    @csrf

                    <input type="hidden" name="entrada_id" value="{{ $datos->entrada->id }}" />

                    <div class="form-row">

                        
                        <div class="form-group col-md-12">
                            <input class="form-control" id="entrada_entrada" name="entrada_entrada" maxlength="{{ config('ctes.constantes_entradas.max_entrada') }}" value="{{ $datos->entrada->entrada }}" />
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">GUARDAR</button>
                        <a href="{{url()->previous()}}" class="btn btn-danger">CANCELAR</a>
                    </div>
                </form>

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

{{-- esto no tiene sentido ya que es para el diccionario presnola y da Entrada solo sale en le diccionario de aula --}}
{{-- 
@section('scripts')
<script>
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
--}}    
