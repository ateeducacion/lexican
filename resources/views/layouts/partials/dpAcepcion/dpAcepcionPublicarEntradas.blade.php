<!-- dpAcepcionPublicarEntradas -->
{{-- -acepciones en  --}}
<div class="row dc-entrada-acepcion {{ $orden == 1 ? "dc-acepcion-principal" : "dc-acepcion-secundaria" }}">
    <div class="dc-entrada-botones dc-entrada-botones-acepcion col-2 col-sm-2 col-md-1">
        @include( 'layouts.partials.components.botones.acepcion.ocultar', ['id'=> $acepcion->id])
    </div>
    <div class="flex-grow-1 col-10 col-sm-10 col-md-4 col-lg-5 col-xl-6 col-xxl-7">
        <div class="dc-acepcion-texto">
            <p>
                {{-- {{ $acepcion->dpCategoria->descripcion }} --}}
                {{ $acepcion->orden }}. 
                {{-- en atribuutos: --}}
                {{--{{ $acepcion->atributos }}--}}
                {{-- Categoría gramatical: --}}
                @isset($acepcion->dpCategoria)
                    @if ( $entrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id', $acepcion->dpCategoria->mst_campo_entrada_id)->first()->visible == config('ctes.visibilidad.visible'))
                        <span class="dc-cat-gramatical"> {{ strtolower($acepcion->dpCategoria->descripcion) }}. </span> 
                    @endif
                @endisset
                @isset($acepcion->dpGenero)
                    {{-- Género  --}}
                    @if ($entrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id', $acepcion->dpGenero->mst_campo_entrada_id)->first()->visible == config('ctes.visibilidad.visible'))
                    <span class="dc-genero" >{{ strtolower($acepcion->dpGenero->descripcion) }}. </span>
                    @endif
                @endisset
                @isset($acepcion->dpNumero)
                    {{-- Número  --}}
                    @if ($entrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id', $acepcion->dpNumero->mst_campo_entrada_id)->first()->visible == config('ctes.visibilidad.visible'))
                    <span class="dc-numero" > {{ strtolower($acepcion->dpNumero->descripcion) }}. </span>
                    @endif
                @endisset
                <br>
                {{ $acepcion->definicion }}
            </p>
            <p class="dc-campos">
               {{-- Tematicas Generales Visibles --}}               
                @if( $acepcion->dpAcepcionTematicas && $acepcion->dpAcepcionTematicas->count()>0 ) 
                    @if ($entrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id', $acepcion->dpAcepcionTematicas->first()->mst_campo_entrada_id)->first()->visible) 
                        <div class="tematicas py-1" >
                            Tematica:                     
                            @foreach($acepcion->dpAcepcionTematicas as $key => $tematica)
                                <span class="dc-tematica"> {{ $tematica->descripcion }} </span>
                                @if(!$loop->last)
                                    , 
                                    @endif
                                    {{-- Estado: {{ $tematica->estado }} </li> --}}
                            @endforeach                    
                        </div>
                    @endif
                @endif

                {{-- Frase ejemplo visible --}}
                @if ( $acepcion->frase_ejemplo  && $acepcion->frase_ejemplo != '')
                    @if ($entrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id', config('ctes.mst_campos_entrada.frase_ejemplo'))->first()->visible == config('ctes.visibilidad.visible'))
                        <div><span class="dc-frase-ejemplo">{{ $acepcion->frase_ejemplo }}</span></div>
                    @endif
                @endif

                
                {{-- lengua-idioma --}}
                @if ( $acepcion->dpIdioma && $acepcion->dpIdioma->descripcion && $acepcion->dpIdioma->descripcion != '' && $acepcion->idioma_palabra !='' )
                    @if ($entrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id', $acepcion->dpIdioma->mst_campo_entrada_id)->first()->visible == config('ctes.visibilidad.visible')) 
                        Traducción a <span class="dc-idioma">{{ $acepcion->dpIdioma->descripcion  }}:</span>
                        <span class="dc-idioma-palabra">{{ $acepcion->idioma_palabra }}</span>
                    @endif
                @endif

            </p>
                
        </div>

        <div class="dc-acepcion-leermas"> @lang('Leer mas...') </div>
    </div>

    <div class="dc-entrada-botones-media col-2 col-sm-2 col-md-1">
        <div>
            @if ($entrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id', config('ctes.mst_campos_entrada.audio'))->first()->visible == config('ctes.visibilidad.visible'))
                @if(dpMedio_getAudio($acepcion))
                    <img 
                        data-toggle="tooltip" data-placement="left"
                        title="{{ __('diccionario.oir_audio') }}"
                        class="" src="{{ asset('imagenes/ico-audio-on.svg') }}" data-info="'{{ dpMedio_getAudio($acepcion)->nombre }}', '{{ dpMedio_getAudioPublicURL($acepcion) }}'" alt="audio" class="dc-ico-audio cursor-pointer dc-audio-modal" 
                    >

                @else
                    <img src="{{ asset('imagenes/ico-audio.svg') }}" alt="audio" class="dc-ico-audio disabled" >
                @endif
            @endif
        </div>
        <div>
            @if($entrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id', config('ctes.mst_campos_entrada.video'))->first()->visible == config('ctes.visibilidad.visible'))
                @if(dpMedio_getVideo($acepcion))
                    <img 
                    data-toggle="tooltip" data-placement="left"
                    title="{{ __('diccionario.ver_video') }}"
                    class="cursor-pointer dc-video-modal" data-info="'{{ dpMedio_getVideo($acepcion)->nombre }}', '{{ dpMedio_getVideoPublicURL($acepcion) }}'" src="{{ asset('imagenes/ico-video-on.svg') }}" alt="video">
                @else
                    <img src="{{ asset('imagenes/ico-video.svg') }}" alt="video" class="disabled">
                @endif
            @endif
        </div>
    </div>
    <div class="dc-entrada-imagen col-10 col-sm-10 col-md-6 col-lg-5 col-xl-4 col-xxl-3">
        {{-- @if( dpMedio_getImagen($acepcion) )
            <a href='{{ "/storage/" . config("ctes.path_medios.imagen") . '/' .  dpMedio_getImagen($acepcion)->url_interna }}' download='{{ dpMedio_getImagen($acepcion)->nombre }}'>
        <div style="background-image:url( {{ '/storage/' . config('ctes.path_medios.imagen') . '/' . dpMedio_getImagen($acepcion)->url_interna }} )" class="dc-entrada-imagen-img"></div>
        </a>
    @else
        <div class="dc-entrada-imagen-img"></div>
        @endif--}}
        @if ($entrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id', config('ctes.mst_campos_entrada.imagen'))->first()->visible == config('ctes.visibilidad.visible'))
            @if( dpMedio_getImagen($acepcion) )
                <a href='{{ URL::to('/') . "/storage/" . config("ctes.path_medios.imagen") . '/' .  dpMedio_getImagen($acepcion)->url_interna }}' download='{{ dpMedio_getImagen($acepcion)->nombre }}'>
                    <div style="background-image:url( {{ URL::to('/') .'/storage/' . config('ctes.path_medios.imagen') . '/' . getNombreFichero(dpMedio_getImagen($acepcion)->url_interna) . config('ctes.video_thumbnail_sufijo') . ".jpg" }} )" class="dc-entrada-imagen-img"></div>
                </a>
            @else
                <div class="dc-entrada-imagen-img"></div>
            @endif
        @endif
    </div>
</div>