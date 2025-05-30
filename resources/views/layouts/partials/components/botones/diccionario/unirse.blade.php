{{-- tipo bocadillo --}}
{{-- dc-actionbutton-menu-btn-enlace --> Sirve para que se muestre dentro del bocadillo --}}
{{-- d-none --> Sirve para iniciar el icono a no visible --}}
<a 
        onclick="aceptar_{{ $id }}('{{ route('getUnirseDiccionarioAjax') }}')" 
        class="col dc-actionbutton-menu-btn-enlace"
>
    <div class="d-flex flex-column dc-actionbutton-menu-btn cursor-pointer submenu-unirse">
        <div>
            <svg width="100%" height="54.545" viewBox="0 0 70 55">
                <use xlink:href="#{{ $bocadillo }}" />
            </svg>
        </div>
        <div class="dc-actionbutton-submenu-caption">{{ $caption }}</div>
    </div>
</a>

<script>
{{ 'function aceptar_'. $id }}(ruta) {
    var me = $(this);

    if (me.data('haciendoLlamada')) {
        return;
    }

    me.data('haciendoLlamada', true);

    $.get(ruta)
        .done(response => {
            $('#padre_modal_ajax').html(response['html']);
            // Muestra cualquier modal que se haya cargado dentro de #padre_modal_ajax
            $('#padre_modal_ajax').find('.modal').modal();
            // 
            // $("#modal_ajax").modal();
        })
        .fail(function() {
            alert("error");
        })
        .always(function() {
            me.data('haciendoLlamada', false);
        })
}
</script>