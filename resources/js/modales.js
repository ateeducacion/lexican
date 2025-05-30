
import $ from 'jquery';
import tinymce from 'tinymce';

// probablemente el js mas educado de todo el proyecto

/** 
 * modales.js
 * Carga ventanas modales con javascript
 * 
 * @author Fernando Ramirez Perez <fernando.ramirez@altia.es>
 * @version 1.0.0
 * 
*/
$( document ).ready(function() {

    // var rutas = [
    //     'personal.consulta.all',
    //     'personal.consulta.byInitial',
    //     'personal.consulta.palabra',
    // ];

    // if ( !rutaEnArray(rutas) ) {
    //     return false;    
    // }

    $(document).on('click', 'a[data-event="ajaxModalAceptarCancelar"]', (event) => {
        // console.log( 
            // 'consultaEntradaModal.js#onClickDataEventAjaxModalAceptarCancelar',
            // event.target.dataset ,            
        // );
        // console.log(         'event.currentTarget.dataset', event.currentTarget.dataset);
        const data = event.currentTarget.dataset;
        // console.log( 'data.titulo', data.titulo );

        const me = $(this);
        
        if (me.data('haciendoLlamada')) {
            return;
        }

        me.data('haciendoLlamada', true);
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const dataObj = { 
            'titulo': data.titulo, 
            'mensaje': data.mensaje, 
            'action': data.action, 
            'modal_width': data.modal_width, 
            'container': data.container
        };

        // console.log('dataObj', dataObj);
        

        $.post(data.route, dataObj )
            .done((response) => {
                // console.log("response done", response);
                // console.log('data enviado', dataObj );
                let padre;
                if(dataObj.container === undefined) {
                    padre = 'padre_modal_ajax';
                } else {
                    padre = dataObj.container
                }
                // console.log( 'padre', padre );
                $('#'+padre).html(response['html']);
                $("#modal_ajax").modal();
                // Muestra cualquier modal que se haya cargado dentro de #padre_modal_ajax
                $('#padre_modal_ajax').find('.modal').modal();
            })
            .fail(function() {
                console.log('error');
            })
            .always(function() {
                me.data('haciendoLlamada', false);
            });
    });

    $(document).on('click', 'a[data-event="modalBotonesEntradaEnviar"]', (event) => {
    // {{ 'function aceptar_'. $id }}(ruta, entrada_id) {
        const data = event.currentTarget.dataset;
        // console.log(data);
        const ruta = data.route;
        const entrada_id = data.entradaId;

        var me = $(this);
    
        if (me.data('haciendoLlamada')) {
        return;       }
    
        me.data('haciendoLlamada', true);
    
        $.get(ruta, {entrada_id})
        .done(response => {
        $('#padre_modal_ajax').html(response['html']);
        $("#modal_ajax").modal();
        // Muestra cualquier modal que se haya cargado dentro de #padre_modal_ajax
        $('#padre_modal_ajax').find('.modal').modal();
        })
        .fail(function() {
        console.log('error');
        })
        .always(function() {
        me.data('haciendoLlamada', false);
        });
    });

    $(document).on('click', 'a[data-event="modalVerComentariosEntrada"]', (event) => {    
        const data = event.currentTarget.dataset;
        // console.log(data);
        const ruta = data.route;
        const entrada_id = data.entradaId;

        var me = $(this);
        if (me.data('haciendoLlamada')) {
            return;
        }
        me.data('haciendoLlamada', true);

        $.get(ruta, {entrada_id})
            .done(response => {
                $('#padre_modal_ajax').html(response['html']);
                $("#modal_ajax").modal();
                // Muestra cualquier modal que se haya cargado dentro de #padre_modal_ajax
                $('#padre_modal_ajax').find('.modal').modal();
            })
            .fail(function() {
                console.log('error');
            })
            .always(function() {
                me.data('haciendoLlamada', false);
            });
    });    

    $(document).on('click', '[data-event="modalSimple"]', (event) => {    
        console.log('data-event="modalSimple" clck');
        const data = event.currentTarget.dataset;

        // evitar multiples llamados por dobles clicks:
        var me = $(this);
        if (me.data('haciendoLlamada')) {
            return;
        }
        me.data('haciendoLlamada', true);

        let html = `<div 
            class="modal fade" 
            id="${data.id}" 
            tabindex="-1" 
            role="dialog" 
            aria-labelledby="${data.id}Label" 
            aria-hidden="true" 
            >`;

        if( data.modalWidth === undefined){
            html +=`<div class="modal-dialog modal-dialog-centered" role="document" style="max-width: ${data.modalWidth};">`
        } else {
            html +=`<div class="modal-dialog modal-dialog-centered" role="document">`
        }
        
        html +=`
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${data.title}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body text-center">${data.body}                    </div>
                    <div class="modal-footer text-center">
                        <button type="button" class="btn btn-primary" data-dismiss="modal">
                        ${data.boton}
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

        $('#padre_modal_ajax').html(html);
        $("#modal_ajax").modal();
        // Muestra cualquier modal que se haya cargado dentro de #padre_modal_ajax
        $('#padre_modal_ajax').find('.modal').modal();

        me.data('haciendoLlamada', false);

    });

    $(document).on('click', '[data-event="modalComentarEntrada"]', (event) => {
        // console.log('data-event="modalComentarEntrada" click');
        const data = event.currentTarget.dataset;
        const ruta = data.route;
        const entrada_id = data.entradaId;

        // evitar multiples llamados por dobles clicks:
        var me = $(this);
        if (me.data('haciendoLlamada')) {
            console.log('ya se pluslo el boton muy rapido');
            $('#padre_modal_ajax').html('');
            event.stopPropagation();
            event.preventDefault();
            return false;            
        }
        me.data('haciendoLlamada', true);
        
        $.get(ruta, {entrada_id})
            .done(response => {
                $('#padre_modal_ajax').html(response['html']);
                $("#modal_ajax").modal();
                // Muestra cualquier modal que se haya cargado dentro de #padre_modal_ajax
                $('#padre_modal_ajax').find('.modal').modal();
                // activar tinymce
                console.log('activar tinies');
                tinymce.remove('#tinyComentario'+entrada_id);
                tinymce.init({
                    selector: '#tinyComentario'+entrada_id,
                    language: 'es'
                });
                
                
            })
            .fail(function() {
                console.log('error');
            })
            .always(function() {
                me.data('haciendoLlamada', false);
            });      
        
    });

});