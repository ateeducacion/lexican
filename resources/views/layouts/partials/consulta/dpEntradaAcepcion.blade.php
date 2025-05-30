{{-- -acepciones en  --}}
<!-- dpEntradaAcepcion -->
<div class="row dc-entrada-acepcion ">

    <div class="d-none d-sm-none d-md-block d-lg-block d-xxl-block w-100">
        <div class="row">
            <div class="col-1 d-inline-flex" style="flex-basis: 3.9rem;padding:0">
                <div class="dc-entrada-botones dc-entrada-botones-acepcion  col-2 col-sm-2 col-md-1">
                    {{-- Botón Subir Acepción --}}
                    @if($acepcion->orden != 1)
                        @include('layouts.partials.components.botones.acepcion.subir', [
                        'diccionario_id' => $datos->entrada->dic_personal_id,
                        'entrada_id'=> $acepcion->dic_entrada_id,
                        'acepcion_id'=> $acepcion->id,
                        ])
                    @endif
            
                    {{-- Botón Bajar Acepción --}}
                    @if($acepcion->orden != ($datos->entrada->dpAcepciones()->count()))
                        @include('layouts.partials.components.botones.acepcion.bajar', [
                        'diccionario_id' => $datos->entrada->dic_personal_id,
                        'entrada_id'=> $acepcion->dic_entrada_id,
                        'acepcion_id'=> $acepcion->id,
                        ])
                    @endif
            
                    {{-- Botón Editar Acepción --}}
                    @include('layouts.partials.components.botones.acepcion.edit', [
                        'diccionario_id' => $datos->entrada->dic_personal_id,
                        'entrada_id'=> $acepcion->dic_entrada_id,
                        'acepcion_id'=> $acepcion->id,
                        'confirm'=> __('diccionario.confirm_acepcion_borrar_entrada'),
                        'tooltip' => __('diccionario.editar_acepcion'),
                    ])


                    {{-- Boton Ocultar acepcion --}}
                    @include( 'layouts.partials.components.botones.acepcion.ocultar', ['id'=> $acepcion->id, 'entrada'=> $acepcion->dpEntrada ])
                    
            
                    {{-- Botón Borrar Acepción --}}
                    @if($datos->entrada->dpAcepciones()->count()==1)
                        @include('layouts.partials.components.modal-AceptarCancelar', [
                        'id' => 'uid_'.uniqid(),
                        'img' => asset('/imagenes/ico-trash.svg'),
                        'modal_width' => '700px',
                        'tooltip' => __('diccionario.acepcion_borrar'),
                        'titulo' => __('diccionario.confirm_titulo'),
                        'class' => 'ico-papelera-fix',
                        'mensaje' => __('diccionario.confirm_acepcion_borrar_entrada') ,
                        'action' => route('acepcion.delete', [$datos->entrada->dic_personal_id, $acepcion->dic_entrada_id, $acepcion->id]),
                        ])
                    @else
                        @include('layouts.partials.components.modal-AceptarCancelar', [
                        'id' => 'uid_'.uniqid(),
                        'img' => asset('/imagenes/ico-trash.svg'),
                        'modal_width' => '700px',
                        'tooltip' => __('diccionario.acepcion_borrar'),
                        'titulo' => __('diccionario.confirm_titulo'),
                        'class' => 'ico-papelera-fix',
                        'mensaje' => __('diccionario.confirm_acepcion_borrar_info', ['numero' => $acepcion->orden, 'entrada' => $acepcion->dpEntrada->entrada]),
                        'action' => route('acepcion.delete', [$datos->entrada->dic_personal_id, $acepcion->dic_entrada_id, $acepcion->id]),
                        ])
                    @endif
                </div>
            </div>
            <div class="col-11">
                <div class="float-right" style="padding: 0 10px 2px 22px">
                    <div class="row"> 
                        <div class="dc-entrada-botones-media justify-content-center" style="padding: 0 1.3rem" >
                            @include('layouts.partials.consulta.acepcion-medios', [ 'acepcion' => $acepcion ])
                        </div>
                        <div class="dc-entrada-imagen d-inline-block">
                            @include('layouts.partials.consulta.acepcion-imagen', [ 'acepcion' => $acepcion ])
                        </div>
                    </div>
                </div>

                @include('layouts.partials.consulta.acepcion-texto', [ 'acepcion' => $acepcion ])
                <div class="dc-acepcion-leermas"> @lang('Leer mas...') </div>
            </div>
        </div>

        <div class="clearfix"></div>
    </div>
    
    <div class="d-flex d-sm-flex d-md-none d-lg-none d-xxl-none row">   
    
        <div class="flex-grow-1 col-10 col-sm-10 col-md-4 col-lg-5 col-xl-6 col-xxl-7">
            <div class="dc-acepcion-texto">
                <p>
                    {{-- {{ $acepcion->dpCategoria->descripcion }} --}}
                    {{ $acepcion->orden }}. 
                    {{-- Categoria genero y numero en atributos: --}}
                    {{ $acepcion->getAtributos() }}

                    {{-- @isset($acepcion->dpCategoria)
                            <span class="dc-cat-gramatical"> {{ $acepcion->dpCategoria->descripcion }}. </span> 
                    @endisset
                    @isset($acepcion->dpGenero)
                        <span class="dc-genero" >{{ $acepcion->dpGenero->descripcion }}. </span>
                    @endisset
                    @isset($acepcion->dpNumero)
                        <span class="dc-numero" > {{ $acepcion->dpNumero->descripcion }}. </span>
                    @endisset --}}
                    <br>
                    {{ $acepcion->definicion }}
                </p>
                <p class="dc-campos">
                    {{-- Mas datos visible --}}
                    @if ( $acepcion->frase_ejemplo  && $acepcion->frase_ejemplo != '')
                    <div class="tematicas py-1" >{{ __('diccionario.ver_acepcion_frase_ejemplo') }}<span class="dc-frase-ejemplo">{{ $acepcion->frase_ejemplo }}</span>
                    </div>
                    @endif

                    {{-- frasde de ejemplo ejemplo2 --}}
                    <p>ejemplo2:</p>
                    @if ( $acepcion->ejemplo2  && $acepcion->ejemplo2 != '')
                    <div class="tematicas py-1" >{{ __('diccionario.ver_acepcion_ejemplo2') }}<span class="dc-ejemplo2">{{ $acepcion->ejemplo2 }}</span>
                    </div>
                    @endif

                    
                    {{-- lengua-idioma --}}
                    @if ( $acepcion->dpIdioma && $acepcion->dpIdioma->descripcion && $acepcion->dpIdioma->descripcion != '' && $acepcion->idioma_palabra !='' )
                    <div class="tematicas py-1" >
                        <span class="dc-idioma">{{  mb_convert_case($acepcion->dpIdioma->descripcion, MB_CASE_TITLE, 'UTF-8') }}:</span> <span class="dc-idioma-palabra">{{ $acepcion->idioma_palabra }}</span>
                    </div>
                    @endif

                    {{-- Tematicas Generales Visibles --}}
                    @if( $acepcion->dpAcepcionTematicas && $acepcion->dpAcepcionTematicas->count()>0 ) 
                    <div class="tematicas py-1" >
                        @lang('diccionario.ver_acepcion_tematica'): 
                        @foreach($acepcion->dpAcepcionTematicas as $key => $tematica)
                            <span class="dc-tematica"> {{ $tematica->descripcion }} </span>
                            @if(!$loop->last)
                                , 
                                @endif
                        {{-- Estado: {{ $tematica->estado }} </li> --}}
                        @endforeach                    
                    </div>
                    @endif
                </p>
                    
            </div>        
        </div>
        
        <div class="dc-entrada-botones-media col-2 col-sm-2 col-md-1">
            <div>

                @if(daMedio_getAudio($acepcion))
                {{-- audio visible? --}}
                    
                    <img data-toggle="tooltip" data-placement="left" src="{{ asset('imagenes/ico-audio-on.svg') }}" data-info="'{{ daMedio_getAudio($acepcion)->nombre }}', '{{ daMedio_getAudioPublicURL($acepcion) }}'" alt="audio" class="dc-ico-audio cursor-pointer dc-audio-modal" >
                    
                @else
                    <img src="{{ asset('imagenes/ico-audio.svg') }}" alt="audio" class="dc-ico-audio disabled" >
                @endif
            </div>
            <div>
                @if(daMedio_getVideo($acepcion) )
                    {{-- video visible: --}}
                    
                    <img class="cursor-pointer dc-video-modal" data-info="'{{ daMedio_getVideo($acepcion)->nombre }}', '{{ daMedio_getVideoPublicURL($acepcion) }}'" src="{{ asset('imagenes/ico-video-on.svg') }}" alt="video">
                @else
                    <img src="{{ asset('imagenes/ico-video.svg') }}" alt="video" class="disabled">
                @endif
            </div>
        </div>
        
        <div class="dc-entrada-imagen col-10 col-sm-10 col-md-6 col-lg-5 col-xl-4 col-xxl-3">
            @if( dpMedio_getImagen($acepcion) )
                <a href='{{ URL::to('/') . "/storage/" . config("ctes.path_medios.imagen") . '/' .  dpMedio_getImagen($acepcion)->url_interna }}' download='{{ dpMedio_getImagen($acepcion)->nombre }}'>
                    <div style="background-image:url( {{ URL::to('/') .'/storage/' . config('ctes.path_medios.imagen') . '/' . getNombreFichero(dpMedio_getImagen($acepcion)->url_interna) . config('ctes.video_thumbnail_sufijo') . ".jpg" }} )" class="dc-entrada-imagen-img"></div>
                </a>
            @else
                <div class="dc-entrada-imagen-img"></div>
            @endif
        </div>
    </div>

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


