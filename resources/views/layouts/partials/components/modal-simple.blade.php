@if(isset($txtBoton))
    <button 
    data-event="modalSimple"     
    @isset($modal_width)
        data-modal-width="{{ $modal_width }}"
    @endisset
    data-title="{{ $title ?? '' }}"
    data-body="{!! $body ?? '' !!}"
    data-boton="{{ $boton }}"
    data-id="{{ $id }}"
    
     type="button" class="btn btn-primary" 
     {{-- data-toggle="modal" data-target="#{{ $id }}" --}}
     >
        <div data-toggle="tooltip" data-placement="top" title="{{ $tooltip }}">{{ $txtBoton }}</a>
    </button>
@endif

@if(isset($img))
    <a 
    data-event="modalSimple"     
    @isset($modal_width)
        data-modal-width="{{ $modal_width }}"
    @endisset
    data-title="{{ $title ?? '' }}"
    data-body="{!! $body ?? '' !!}"
    data-boton="{{ $boton }}"
    data-id="{{ $id }}"
    class="cursor-pointer"
    
    {{-- data-toggle="modal" href="#{{ $id }}" data-target="#{{ $id }}"  --}}
    class="icon">
        <img data-toggle="tooltip" data-placement="top" title="{{ $tooltip }}" src="{{ asset($img) }}" alt="@lang('diccionario.Enviar')">
    </a>
@endif

@if(isset($bocadillo))
    <a 

    data-event="modalSimple"     
    @isset($modal_width)
        data-modal-width="{{ $modal_width }}"
    @endisset
    data-title="{{ $title ?? '' }}"
    data-body="{!! $body ?? '' !!}"
    data-boton="{{ $boton }}"
    data-id="{{ $id }}"

    {{-- data-toggle="modal" href="#{{ $id }}" data-target="#{{ $id }}"  --}}
    class="col dc-actionbutton-menu-btn-enlace ">

        <div class="d-flex flex-column dc-actionbutton-menu-btn cursor-pointer">
            <svg width="100%" height="54.545" viewBox="0 0 70 55">
                <use xlink:href="#{{ $bocadillo }}" />
            </svg>
            <span>{{ $caption }}</span>
        </div>
    </a>
@endif
