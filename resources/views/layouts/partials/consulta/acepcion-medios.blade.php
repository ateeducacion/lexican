<div class="dc-entrada-botones-media ">
    <div>
        @if(dpMedio_getAudio($acepcion))
            <img 
                data-toggle="tooltip" data-placement="left"
                title="{{ __('diccionario.oir_audio') }}"
                src="{{ asset('imagenes/ico-audio-on.svg') }}" data-info="'{{ dpMedio_getAudio($acepcion)->url_interna }}', '{{ dpMedio_getAudioPublicURL($acepcion) }}'" alt="audio" class="dc-ico-audio cursor-pointer dc-audio-modal" >

        @else
            <img src="{{ asset('imagenes/ico-audio.svg') }}" alt="audio" class="dc-ico-audio disabled" >
        @endif
    </div>
    <div>
        @if(dpMedio_getVideo($acepcion))
            <img 
            data-toggle="tooltip" data-placement="left"
            title="{{ __('diccionario.ver_video') }}"
            class="cursor-pointer dc-video-modal" data-info="'{{ dpMedio_getVideo($acepcion)->url_interna }}', '{{ dpMedio_getVideoPublicURL($acepcion) }}'" src="{{ asset('imagenes/ico-video-on.svg') }}" alt="video">
        @else
            <img src="{{ asset('imagenes/ico-video.svg') }}" alt="video" class="disabled">
        @endif
    </div>
</div>