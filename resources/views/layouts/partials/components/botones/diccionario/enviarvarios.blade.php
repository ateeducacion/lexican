{{-- tipo bocadillo --}}
{{-- dc-actionbutton-menu-btn-enlace --> Sirve para que se muestre dentro del bocadillo --}}
{{-- d-none --> Sirve para iniciar el icono a no visible --}}
<a href="{{ route('diccionario.enviar', [$datos->diccionario->id]) }}" class="col-4 dc-actionbutton-menu-btn-enlace ">
    <div class="d-flex flex-column dc-actionbutton-menu-btn">
        <svg width="100%" height="54.545" viewBox="0 0 70 55">
            <use xlink:href="#{{ $bocadillo }}" />
        </svg>
        <span>{{ $caption }}</span>
    </div>
</a>