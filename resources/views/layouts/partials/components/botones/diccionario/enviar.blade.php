@if(isset($bocadillo))
    <a 
        onclick="aceptar_{{ $id }}('{{ route('getDiccionariosAulaDiccionarioAjax') }}')" 
        class="col-4 dc-actionbutton-menu-btn-enlace"
    >
        <div class="d-flex flex-column dc-actionbutton-menu-btn cursor-pointer">
            <svg width="100%" height="54.545" viewBox="0 0 70 55">
                <use xlink:href="#{{ $bocadillo }}" />
            </svg>
            <span>{{ $caption }}</span>
        </div>
    </a>
@endif

<script>
{{ 'function aceptar_'. $id }}(ruta) {

    // console.log('ruta modal:', ruta);

	// $.get(ruta)
	// 	.then(response => {
	// 		if (typeof response['redirect'] === 'undefined'){
	// 			$('#padre_modal_ajax').html(response['html']);
	// 			$("#modal_ajax").modal();
	// 		} else {
	// 			window.location = response['redirect'];
	// 		}
	// 	})

    var me = $(this);

    if (me.data('haciendoLlamada')) {
        return;
    }

    me.data('haciendoLlamada', true);

    $.get(ruta)
        .done(response => {
			if (typeof response['redirect'] === 'undefined'){
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
        })
}
</script>