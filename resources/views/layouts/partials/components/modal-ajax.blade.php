<!-- Modal -->
<div class="modal fade" id="modal_ajax" tabindex="-1" role="dialog">
    @if(isset($modal_width))
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: {{ $modal_width }};">
        @else
        <div class="modal-dialog modal-dialog-centered" role="document">
            @endif

            <div id="modal-ajax-content" class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <div id="modal-ajax-title">
                        </div>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body text-center">
                    <div id="modal-ajax-body">
                    </div>
                </div>

                <div class="modal-footer text-center">
                    <button type="button" class="btn btn-primary" onclick="aceptarModalAjax()">
                        @lang(__('diccionario.boton_aceptar'))
                    </button>
                    <button type="button" class="btn btn-secondary btn-danger" data-dismiss="modal">
                        @lang(__('diccionario.boton_cancelar'))
                    </button>
                </div>

            </div>
        </div>
    </div>