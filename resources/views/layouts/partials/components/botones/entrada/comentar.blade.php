@if (sizeof($entrada->comentariosEntrada) > 0)
    <a
        href="javascript:void(0)" data-event="load-editor" class="icon" 
        data-toggle="tooltip" data-placement="top" title="" 
        data-original-title="@lang('Comentar')">
        <img data-placement="top" title="" data-original-title="@lang('Comentar')"

                data-event="modalComentarEntrada"
                data-entrada-id="{{ $entrada->id }}"
                data-route="{{ route('getComentarEntradaAjax', ['entrada_id' => $entrada->id]) }}"
            {{-- data-toggle="modal" 
            data-target="#comentarModal_{{$entrada->id}}" --}}

            src="{{ asset('/imagenes/ico-bocadillo-comentar-verde.svg') }}" >
    </a>  
@else
    <a href="javascript:void(0)" data-event="load-editor" class="icon" data-toggle="tooltip" data-placement="top" title="" data-original-title="@lang('Comentar')">
        <img data-placement="top" title="" data-original-title="@lang('Comentar')"
        
                data-event="modalComentarEntrada"
                data-entrada-id="{{ $entrada->id }}"
                data-route="{{ route('getComentarEntradaAjax', ['entrada_id' => $entrada->id]) }}"
            
            {{-- data-toggle="modal" data-target="#comentarModal_{{$entrada->id}}"  --}}

            src="{{ asset('/imagenes/ico-bocadillo-comentar-gris.svg') }}" >
    </a> 
@endif
