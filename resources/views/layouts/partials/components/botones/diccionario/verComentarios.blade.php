@if(isset($bocadillo))
    <a onclick="aceptar_{{ $id }}('{{ route('getComentariosDiccionarioAjax') }}')" class="col dc-actionbutton-menu-btn-enlace cursor-pointer">
        <div class="d-flex flex-column dc-actionbutton-menu-btn">
            <svg width="100%" height="54.545" viewBox="0 0 70 55">
                <use xlink:href="#{{ $bocadillo }}" />
            </svg>
            <span>{{ $caption }}</span>
        </div>
    </a>
@endif

<script>
{{ 'function aceptar_'.$id }}(ruta, entrada_id) {
    var me = $(this);

    if (me.data('haciendoLlamada')) {
        return;
    }

    me.data('haciendoLlamada', true);

    $.get(ruta, { entrada_id })
        .done(response => {
            if (typeof response['redirect'] === 'undefined') {
                $('#padre_modal_ajax').html(response['html']);
                // $("#modal_ajax").modal();
                // Muestra cualquier modal que se haya cargado dentro de #padre_modal_ajax
                $('#padre_modal_ajax').find('.modal').modal();
            } else {
                window.location = response['redirect'];
            }
        })
        .fail(function() {
            alert("error");
        })
        .always(function() {
            me.data('haciendoLlamada', false);
        }
    );
}

</script>