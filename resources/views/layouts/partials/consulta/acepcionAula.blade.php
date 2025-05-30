<!-- partial.consulta.acepcionAula -->
@if(config('app.debug')) 
<!-- envios_acepciones acepcion->id : '{{ $acepcion->id }}' --> 
@endif
<div class="row dc-entrada-acepcion {{ $orden == 1 ? "dc-acepcion-principal" : "dc-acepcion-secundaria" }}">
    <div class="d-none d-sm-none d-md-block d-lg-block d-xxl-block w-100">
        
        <div class="row" >
            @can( 'createDicAula', App\DicAula::class )
            <div class="col-1 d-inline-flex"  style="flex-basis: 4rem">
                <div 
                    class="dc-entrada-botones dc-entrada-botones-acepcion d-block"
                    id="botones-acepcion-{{$acepcion->id}}"
                >
                    @include( 'layouts.partials.components.botones.acepcion.ocultarAula', ['container'=> "botones-acepcion-$acepcion->id"])
                    <div class="dc-editar-docente"> 
                        {{-- @if( isset($datos) && isset($datos->entrada) ) --}}
                        @can('esCoordinador', $acepcion->envioEntrada->dpEnvio->dicAula)                
                        @include( 'layouts.partials.components.botones.acepcion.editAula', [
                            'acepcion'=>$acepcion,
                            'dicAulaActivo_id'=>$acepcion->envioEntrada->dpEnvio->dicAula->id
                        ])
                        @endcan
                    </div>
                </div>
            </div>
            @endcan
                
            <div class="col-11">
                <div class="float-right" style="padding: 0 10px 2px 22px">
                    <div class="row"> 
                        <div class="dc-entrada-botones-media justify-content-center" style="padding: 0 1.3rem" >
                            @include('layouts.partials.consulta.acepcionAula-medios', [ 'acepcion' => $acepcion ])
                        </div>
                        <div class="dc-entrada-imagen d-inline-block">
                            @include('layouts.partials.consulta.acepcionAula-imagen', [ 'acepcion' => $acepcion ])
                        </div>
                    </div>
                </div>

                @include('layouts.partials.consulta.acepcionAula-texto', [ 'acepcion' => $acepcion ])            
                <div class="dc-acepcion-leermas"> @lang('Leer mas...') </div>            
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
    
    <div class="d-flex d-sm-flex d-md-none d-lg-none d-xxl-none row">
        @can( 'createDicAula', App\DicAula::class )
        <div class="dc-entrada-botones dc-entrada-botones-acepcion col-2 col-sm-2 col-md-1" id="botones-acepcion-{{$acepcion->id}}">
            @include( 'layouts.partials.components.botones.acepcion.ocultarAula', ['container'=> "botones-acepcion-$acepcion->id"])
            <div class="dc-editar-docente"> 
                {{-- @if( isset($datos) && isset($datos->entrada) ) --}}
                @can('esCoordinador', $acepcion->envioEntrada->dpEnvio->dicAula)                
                @include( 'layouts.partials.components.botones.acepcion.editAula', [
                'acepcion'=>$acepcion,
                'dicAulaActivo_id'=>$acepcion->envioEntrada->dpEnvio->dicAula->id
                ])
                @endcan
            </div>
        </div>
        @endcan
        <div class="col-12 col-sm-12" >
            @include('layouts.partials.consulta.acepcionAula-texto', [ 'acepcion' => $acepcion ])
            <div class="dc-acepcion-leermas"> @lang('Leer mas...') </div>
        </div>
        <div class="dc-entrada-botones-media col-2 col-sm-2 col-md-1">
            @include('layouts.partials.consulta.acepcionAula-medios', [ 'acepcion' => $acepcion ])
        </div>
        <div class="dc-entrada-imagen col-10 col-sm-10 col-md-6 col-lg-5 col-xl-4 col-xxl-3">
            @include('layouts.partials.consulta.acepcionAula-imagen', [ 'acepcion' => $acepcion ])            
        </div>
    </div>
</div>




