<a 
    data-event="ajaxModalAceptarCancelar"
    data-aceptarid="{{ $id }}"
    data-route="{{ route('getAceptarCancelarAjax') }}"
    data-titulo="{{ $titulo }}"
    data-mensaje="{{ $mensaje }}"
    data-action="{{ $action }}"
    data-modal_width="{{ $modal_width }}"
    @isset($container)
        data-container="{{ $container }}"    
    @endisset
    
    >
    <img class="cursor-pointer" data-toggle="tooltip" data-placement="top" src="{{ $img }}" title="{{ $tooltip }}">
</a>
