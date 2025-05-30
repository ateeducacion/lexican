@extends('diccionario/home')

@section('title', $datos->envioEntrada->entrada.' - Diccicionario de aula')

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
        {{-- Entrada --}}
        <div class="dc-entrada-container">
            <div class="dc-entrada-top row">
                <div class="dc-entrada-titulo col-6 col-xs-6 col-sm-8 col-lg-9 col-xl-10"> {{ $datos->envioEntrada->entrada }} </div>
                <div class="dc-entrada-botones-top col-6 col-xs-6 col-sm-4 col-lg-3 col-xl-2 dc-entrada-botones d-flex">
                    @can( 'createDicAula', App\DicAula::class )
                    
                    @if(isset($datos->envioEntrada->id))
                        <a href="{{ route('aula.entrada.edit',[
                            $datos->entrada->dic_aula_id, 
                            $datos->envioEntrada->id ]) }}">
                            <img 
                                data-toggle="tooltip" data-placement="top" title="Editar nombre"
                                src="{{ asset('/imagenes/ico-edit.svg') }}" alt="Editar"
                            >
                        </a>
                    @endif

                    @endcan
                </div>
                <div class="dc-entrada-top-right">
                        
                </div>
            </div>

            {{-- acepciones --}}
            <div class="dc-acepciones">
                @foreach( $datos->envioEntrada->acepciones as $acepcion )
                    @include('layouts.partials.consulta.acepcionAula', $acepcion)
                @endforeach        
            </div>
            {{-- fin acepciones --}}
            <div class="dc-entrada-bottom">
                
            </div>

        </div>
    </div>

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

