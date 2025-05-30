<div>
    @if(daMedio_getAudio($acepcion) && ( $acepcion->envioEntrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id',config('ctes.mst_campos_entrada.audio'))->first()->visible == config('ctes.visibilidad.visible')))
    {{-- audio visible? --}}
    
    <img class="cursor-pointer dc-audio-modal" src="{{ asset('imagenes/ico-audio-on.svg') }}" data-info="'{{ daMedio_getAudio($acepcion)->url_interna }}', '{{ daMedio_getAudioPublicURL($acepcion) }}'" alt="audio" class="dc-ico-audio" >
    
    @else
    <img src="{{ asset('imagenes/ico-audio.svg') }}" alt="audio" class="dc-ico-audio disabled" >
    @endif
</div>
<div>
    @if(daMedio_getVideo($acepcion) && ( $acepcion->envioEntrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id',config('ctes.mst_campos_entrada.video'))->first()->visible == config('ctes.visibilidad.visible')) )
    {{-- video visible: --}}
    
    <img class="cursor-pointer dc-video-modal" data-info="'{{ daMedio_getVideo($acepcion)->url_interna }}', '{{ daMedio_getVideoPublicURL($acepcion) }}'" src="{{ asset('imagenes/ico-video-on.svg') }}" alt="video">
    @else
    <img src="{{ asset('imagenes/ico-video.svg') }}" alt="video" class="disabled">
    @endif
</div>