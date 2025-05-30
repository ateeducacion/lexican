@isset($entradas)
    <div class="row">
        <div class="col-12 col-sm-12 col-md-11 mx-auto ">
            {{-- Numero de entradas --}}
            <div class="row mt-3 mb-5">
                <div class="col-md-7" id="nentradas">
                    <h5>{{__('Entradas enviadas por').' '.($estudiante?$estudiante->nombre:__('el alumnado')).' ('.count($entradas).' '.__('entradas').')'}}</h5>
                    {{-- mostar filtros activos --}}
                    <p id="filtrosP">{{ __('diccionario.filtros_activos') }}: <span id="filtrosActivos"></span></p>
                </div>

                <div class="col-md-5 text-left grp-ordenar" style="padding:1rem 0">
                    <span class="dc-fontsize1 float-right" >
                        {{-- Pubilcar todas las entradas --}}
                        @include('layouts.partials.components.botones.entrada.publicarListadoEntradas', [
                                            'dicAula' => $dicAula,
                                            'entradas' => $entradas
                                        ])

                        <button class="dc-round-btn dc-btn-ordenar" id="ordernarPorFecha" type="button" >
                            <div class="d-inline-block">{{ __('diccionario.por_fecha') }}</div>  
                        </button>
                        
                        <button class="dc-round-btn dc-btn-ordenar" id="ordenarAlfabeticamente" type="button">
                            <div class="d-inline-block">{{ __('diccionario.alfabeticamente') }}</div>
                        </button>
                    </span>       
                </div>
            </div>
        <div id="entradasEnviadas">
            @if( config('app.debug') ) <!-- entradasAulaListado --> @endif
            @foreach($entradas as $index => $entrada)
            
            <div id='{{ $entrada->entrada }}' class="row sortme"
                data-title="{{ substr($entrada->entrada,0,5) }}" 
                data-index="{{ $index }}" 
                data-date="{{ $entrada->created_at->format('Ymd') }}{{ sprintf('%04d', $index) }}"
                >
                @if( config('app.debug') )<!-- entrada  {{ $entrada->id }} -->@endif
                    <div class="col-12 col-sm-12 dc-entradaEnviada-entradaAulaListado" id="entrada-{{ $entrada->entrada }}">

                            <div class="row mt-5">
                                <div class="dc-entrada-titulo col-6 col-xs-6 col-sm-8 col-lg-9 col-xl-10">
                                        {{ $entrada->entrada }}
                                </div>                                
                                <div class="dc-entrada-botones-top col-6 col-xs-6 col-sm-4 col-lg-3 col-xl-2 dc-entrada-botones">
                                    <div class="dc-entrada-botones botones-diccionario-aula" style="margin-left: 1.5rem">
                                        @include('layouts.partials.components.botones.entrada.eliminarAula', [
                                            'diccionario' => $entrada->dpEnvio->dicAula,
                                            'entrada'=> $entrada,
                                            'estudiante' => $estudiante
                                        ])
                                    </div>
                                    <div class="dc-entrada-botones botones-diccionario-aula" style="margin-left: 1rem">
                                        @include('layouts.partials.components.botones.entrada.comentar', [
                                            'diccionario' => $entrada->dpEnvio->dicAula,
                                            'entrada'=> $entrada,
                                            'estudiante' => $estudiante
                                        ])
                                    </div>
                                    <div class="dc-entrada-botones botones-diccionario-aula" style="margin-left: 1rem">
                                        @include('layouts.partials.components.botones.entrada.publicar', [
                                            'diccionario' => $entrada->dpEnvio->dicAula,
                                            'entrada'=> $entrada,
                                            'estudiante' => $estudiante
                                        ])
                                    </div>
                                    
                                </div>
                                <span class="align-self-center">
                                    {{ __('diccionario.entradaAulaListado_creadorYfecha', 
                                        [
                                            'nombre'=> $entrada->dpEnvio->dpDiccionario
                                                    ->persona->nombre,
                                            'apellidos'=> $entrada->dpEnvio->dpDiccionario
                                                    ->persona->apellidos,
                                            'fecha'=>$entrada->created_at->format('d/m/Y')
                                        ]) }}
                                    @if( $entrada->updated_by_persona_id !== 0 )
                                        <img class="dc-ico-modificado" data-toggle="tooltip" data-placement="bottom" title="{{ __('diccionario.tooltip_entrada_modificada') }}" src={{ asset('imagenes/ico-modificado.svg') }} >
                                    @endif
                                </span>
                            </div>
                            <div class="row">
                                <div class="col-12 col-sm-12">
                                    @foreach($entrada->envioAcepciones as $acepcion)
                                        @include('layouts.partials.consulta.acepcionAula', [
                                            'acepcion' => $acepcion,
                                            'orden' => 1,
                                            'entrada' => $entrada,
                                        ])    
                                    @endforeach
                                </div>
                            </div>                                                        

                    </div>
                </div>                
            @endforeach
        </div>
            <!-- Success Panel -->
            @include('layouts.partials.alerts.dynamicPanels', [
                'type' => 'both',
                'titleSuccess' => 'Success',
                'bodySuccess' => 'Entrada publicada con éxito',
                'titleError' => 'Error',
                'bodyError' => 'Error al publicar la entrada'
            ])
            <!-- End Success Panel -->
        </div>
    </div>
    <script>
        var rutaImagen = "{{ asset('imagenes/ico-trash.svg') }}";
        var rutaImagenGris = "{{ asset('imagenes/ico-trash-disabled.svg') }}";
    </script>
@endisset