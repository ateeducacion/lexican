import $, { data, error } from 'jquery';

import Swiper, { Navigation, Pagination } from 'swiper';

// configure Swiper to use modules
Swiper.use([Navigation, Pagination]);

import {rutaEnArray, cerrarEmergentes, fechaStrFormatEs} from './utils';
import {addClassTimer} from './dynamicPanels';
/**
 * Funcionalidades del diccionario de aula
 *
 * @author Javier Pérez Batista <javier.perez@altia.es>
 * @version 1.0.0
 *
*/
$( document ).ready(function() {

    tinymce.init({
        selector: '.textarea-tiny',
        language: 'es',
        height: 600,
        plugins: [
            "advlist autolink lists link image charmap print preview anchor",
            "searchreplace visualblocks code fullscreen",
            "insertdatetime media table contextmenu paste imagetools",
        ],
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image ",

        image_title: true,
        automatic_uploads: true,
        images_upload_url: baseurl + '/upload',

        file_picker_types: 'image',
        file_picker_callback: function(cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.onchange = function() {
                var file = this.files[0];

                var reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = function () {
                    var id = 'blobid' + (new Date()).getTime();
                    var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                    var base64 = reader.result.split(',')[1];
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    cb(blobInfo.blobUri(), { title: file.name });
                };
            };
            input.click();
        },
        relative_urls : false,
        remove_script_host : true,
        document_base_url : baseurl
    })

    $('.tox.tox-tinymce').addClass('w-100');

    // solo se ejecuta en estas rutas ( hay que cambiar el punto por - )
    const rutas = [
        'diccionarioaula.index',
        'diccionarioaula.create',
        'diccionarioaula.save',
        'diccionarioaula.entradas',
        'diccionarioaula.get',
        'diccionarioaula.edit',
        'aula.consulta.byInitial',
        'aula.consulta.all',
        'aula.consulta.palabra'

    ];
    if ( !rutaEnArray(rutas) ) {
        // console.log('no se carga dic_aula.js  en ruta: ', $('body')[0].classList.value );
        // console.log('no esta en ', rutas );
        return false;
    }
    // console.log('dic_aula.js cargado');

    if ( document.getElementById('crearButton') ){
        document.getElementById('crearButton').onclick = function() {
            generaCodigoAula()
        };
    }

    const negritaEnvioFecha = () => {
        if ($('#habilitarEnvio').val() > 0 ) {
            $('#envioFecha').prop('disabled',false);
            $('label[for=envioFecha]').css('font-weight','bold');
        } else {
            $('#envioFecha').prop('disabled',true);
            $('label[for=envioFecha]').css('font-weight','normal');
        }
    };
    negritaEnvioFecha();
    $('#habilitarEnvio').on('change', negritaEnvioFecha );


        // Para controlar la activación (o no) del campo fecha de envío de comentarios.
    $('#visibilidadComentarios').on('change', () => {
        $('#comentariosVisibleFecha').prop('disabled', $('#visibilidadComentarios').val() > 1? false:true);
        $('label[for=comentariosVisibleFecha]').css('font-weight',
            $('#visibilidadComentarios').val() > 1? 'bold' : 'normal'
        );

    });

    $('#app').on( 'click', '#btnInvitarDocenteEmail', (ev) => {
        document.getElementById('invitarDocenteEmail').submit(function(e) {
            e.preventDefault();
            // Coding
            $('docentesModal').modal('toggle'); //or $('#IDModal').modal('hide');
                return false;
            });
    });


    var mySwiper = new Swiper('.swiper-container', {
        // Optional parameters
        direction: 'horizontal',
        loop: false,
        slidesPerView: 1,
        watchSlidesVisibility: true,
        centerInsufficientSlides: true,
        spaceBetween: 10,
        breakpoints: {
            // when window width is >= 320px
            320: {
              slidesPerView: 2,
              spaceBetween: 20
            },
            // when window width is >= 480px
            480: {
              slidesPerView: 3,
              spaceBetween: 30
            },
            // when window width is >= 640px
            576: {
              slidesPerView: 3,
              spaceBetween: 40
            },
            // when window width is >= 640px
            768: {
              slidesPerView: 5,
              spaceBetween: 40
            },
            // when window width is >= 640px
            992: {
              slidesPerView: 7,
              spaceBetween: 40
            },// when window width is >= 640px
            1200: {
                slidesPerView: 9,
                spaceBetween: 40
              }
          },

        // If we need pagination
        pagination: {
          el: '.swiper-pagination',
        },

        // Navigation arrows
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },

        // And if we need scrollbar
        scrollbar: {
          el: '.swiper-scrollbar',
        },
      });


    $('#buscarNombre').on( 'keypress',  function(e) {
        // console.log("press buscarnombre");
        var keycode = (e.keyCode ? e.keyCode : e.which);
        if (keycode == '13') {
            console.log("pulsado enter");
            buscarEnvioEntradas(e);
            e.preventDefault();
            return false;
        }
    });

    const buscarEnvioEntradas = (event) => {
        // console.log('Se ha llamado a buscarEnvioEntradas(event)', event );
        /* stop form from submitting normally */
        event.preventDefault();

        // cerrar panel busqueda avanzada si esta abierto
        cerrarEmergentes();
        busquedaAvanzadaHide(event);

        /* get the action attribute from the <form action=""> element */
        const $form = $( 'form#dicAulaPanelBuscador' );
        // lo cambio por data action para que no falle cuando no esta cargado el js
        const url = $form.attr( 'data-action' );

        /* Send the data using post with element id name and name2*/
        const posting = $.post( url, $form.serializeArray() );

        /* Alerts the results */
        $('#loader').show();
        posting.done(function( data ) {
            $('#dicAulaResultados').html(data);
            // cargar tooltips para html cargados con ajax
            $('[data-toggle="tooltip"]').tooltip();

            $('#filtrosActivos').html();
            let htmlFiltrosTxt = '';

            if ( $('#buscarNombre').val()!==''){
                htmlFiltrosTxt += ' Buscando "'+$('#buscarNombre').val()+'"';
            }
            if ( $('#selectedLetra').val()!==''){
                if (htmlFiltrosTxt!='') htmlFiltrosTxt += ',';
                htmlFiltrosTxt += ' empezando por la letra "'+$('#selectedLetra').val()+'"';
            }

            if ( $('#estado').val()!=="0" ){
                if (htmlFiltrosTxt!='') htmlFiltrosTxt += ',';
                htmlFiltrosTxt += ' estado "'+$.trim($( "#estado option:selected" ).text())+'"';
            }
            if ( $('#estudiante').val()!=="0" ){
                if (htmlFiltrosTxt!='') htmlFiltrosTxt += ',';
                htmlFiltrosTxt += ' participante "'+$( "#estudiante option:selected" ).text().trim()+'"';
            }
            if ( $('#desde').val()!=='' || $('#desde').val()!==''){
                if (htmlFiltrosTxt!='') htmlFiltrosTxt += ',';
                htmlFiltrosTxt += ' envíos';
            }
            let fecha;
            if ( $('#desde').val()!==''){
                const dateDesde = new Date($( "#desde" ).val());
                htmlFiltrosTxt += ' desde "'+ fechaStrFormatEs(dateDesde) +'"';
            }
            if ( $('#hasta').val()!==''){
                htmlFiltrosTxt += ' hasta "' + fechaStrFormatEs($( "#hasta" ).val()) +'"';
            }

            if ( htmlFiltrosTxt != '' ){
                // console.log('htmlFiltrosTxt',htmlFiltrosTxt);
                $('#filtrosActivos').html(htmlFiltrosTxt);
                $('#filtrosP').show();
            } else {
                $('#filtrosP').hide();
            }

            // Carga los datos para el boton Publicar todos
            // esta puesto que se cargue dentro de la respuesta de buscar
            // para no sobrecargar esto la primera vez
            // cargarDatosPublicarTodos(
            //     $('#cargarDatosPublicarTodos').data('actionurl'),
            //     $( 'form#dicAulaPanelBuscador' ).serializeArray());

            // ver ordenar.js
            $('#dicAulaResultados #ordernarPorFecha').trigger('click');

            // quita spiner de "cargando..."
            $('#loader').hide();
        });
    };

      // al pulsar buscar en entradasAula.blade.php
      $("#dicAulaPanelBuscador .submit").on('click', (ev) => {$("#dicAulaPanelBuscador").trigger('submit');} )
      $("#dicAulaPanelBuscador").on('submit', (ev) => {buscarEnvioEntradas(ev);} );

      $('.swiper-slide').on('click', 'input[name=avatares]', function(ev){
        if ($('#selectedSliderEstudiante').val() != $(ev.delegateTarget).data('id')) {
            $('#selectedSliderEstudiante').val($(ev.delegateTarget).data('id'))
            $('#estudiante').val($(ev.delegateTarget).data('id'))
        } else {
            $('#selectedSliderEstudiante').val('')
            $(ev.currentTarget).prop('checked', false)
            $('#estudiante').val(0)
        }
      });

      $('#estudiante').on('change', function(ev){
        if ($(ev.currentTarget).val() != $('#selectedSliderEstudiante').val() && $(ev.currentTarget).val() != 0) {
            $('#selectedSliderEstudiante').val($(ev.currentTarget).val())
            $('#chk_'+$(ev.currentTarget).val()).prop('checked', true)
            // console.log($(ev.currentTarget).val());
        } else {
            $('#chk_'+$(selectedSliderEstudiante).val()).prop('checked', false)
            $('#selectedSliderEstudiante').val('')
            $(ev.currentTarget).val(0)
        }
      });

      $('[data-ajax="seleccionaLetra"]').on('click', function(ev) {
          if ($('#selectedLetra').val() == $(ev.currentTarget).data('letra')) {
            $('#selectedLetra').val('');
            $('[data-ajax="seleccionaLetra"] > .letra').removeClass('sel');
          } else {
            $('#selectedLetra').val($(ev.currentTarget).data('letra'));
            $('[data-ajax="seleccionaLetra"] > .letra').removeClass('sel');
            $('.letra', ev.currentTarget).addClass('sel');
          }

      });

      $(document).on('submit', '[data-event=publicar]', function(ev){
        ev.preventDefault();
        let $form = $(ev.currentTarget);
        let url = $form.attr('action');
        if ($form.data('action') == 'anular') {
            var params = {'action' : 'anular'};
            var successBody = 'Publicación anulada con éxito';
            var errorBody = 'Error al anular la publicación';
        } else {
            var params = {'action' : 'publicar'};
            var successBody = 'Entrada publicada con éxito';
            var errorBody = 'Error al publicar la entrada';
        }
        $.post(url, params).done(
            function(result){
                let target = $('input[type=submit]', ev.currentTarget).data('target')
                let view = result.view;
                // console.log('post result',result );
                if (result.codeType == 'error') {
                    if (result.message != '') {
                        errorBody = result.message
                    }
                    $("img[data-target='"+target+"']").closest('div').html(view);
                    mostrarPanelMensaje(
                        $("img[data-target='"+target+"']").closest('div'),
                        errorBody, 'error');


                } else {
                    $("img[data-target='"+target+"']").closest('div').html(view);
                    mostrarPanelMensaje(
                        $("img[data-target='"+target+"']").closest('div'),
                        successBody, 'success');
                }
            }
        );
      });

      $(document).on('submit', '[data-event=comentar]', function(ev){
        ev.preventDefault();
        let $form = $(ev.currentTarget);
        let url = $form.attr('action');
        let data = $form.serializeArray();
        data[1].value = tinymce.activeEditor.getContent();
        // console.log(data);
        if (tinymce.activeEditor.getContent() == '') {
            let target = $('input[type=submit]', ev.currentTarget).data('target')
            let errorBody = 'El comentario no puede estar vacío';
            $('p', '[data-panel=error]').html(errorBody);
            let errorPanel = $('[data-panel=error]')
            errorPanel.appendTo($("img[data-target='"+target+"']").closest('div').parent('div'))
            addClassTimer(errorPanel, ['visibles'], 3000)
        } else {
            //spinner();
            $.post(url, data).done(
                function(view){
                    $('#comentarModal_' +$form.data('parent')+ ' .listado-comentarios').html(view)
                    tinyMCE.activeEditor.setContent('')
                }
            );
        }
      });

      $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('body').on('click', '[data-action="deshabilitarParticipante"]', function(ev){
        // console.log("habilitar participante ", $(ev.currentTarget).data('id'));
        //we will send data and recive data fom our AjaxController
        $.ajax({
           url:location.href+'/deshabilitarParticipante/'+$(ev.currentTarget).data('id'),
           data:{},
           type:'post',
           success: function (response) {
                // $( ev.currentTarget() ).parents('tbody').html(response);
                // console.log($( ev.currentTarget ));
                $( ev.currentTarget ).parents('tbody').html(response);
                $('.tooltip').hide()
                $('[data-toggle="tooltip"]').tooltip({
                    trigger : 'hover'
                })
           },
           statusCode: {
              404: function(response) {
                 alert('web not found');
              }
           },
           error:function(x,xs,xt){
               //nos dara el error si es que hay alguno
               console.log('error intentado deshabilitar participante',x);
               // console.log('xs',xs);
               // console.log('xt',xt);
              // window.open(JSON.stringify(x));
           }
        });
    });

    $('body').on('click', '[data-action="habilitarParticipante"]',function(ev){
        //we will send data and recive data fom our AjaxController
        $.ajax({
           url:location.href+'/habilitarParticipante/'+$(ev.currentTarget).data('id'),
           data:{},
           type:'post',
           success: function (response) {
                // console.log($( ev.currentTarget ));
                $(ev.currentTarget).parents('tbody').html(response);
                $('.tooltip').hide()
                $('[data-toggle="tooltip"]').tooltip({
                    trigger : 'hover'
                })
           },
           statusCode: {
              404: function(response) {
                 alert('web not found');
              }
           },
           error:function(x,xs,xt){
               //nos dara el error si es que hay alguno
               // window.open(JSON.stringify(x));
               console.log('error intentado habilitar participante',x);
           }
        });
    });

    $('#borrarDicButton').click((ev)=>{
        // console.log('click');
        $.get('./testdelete').done(
            (data) => {
                console.log("lanzado");
                console.log('data', data );
                if (!data.borrable) {
                    switch (data.error) {
                        case 'publicadas':
                            // console.log('publicadas');
                            $('#modalDeletePeroPublicadas').modal();
                            break;
                        case 'envios':
                            // console.log('enviadas');
                            $('#modalDeletePeroEnviadas').modal();
                            break;
                    }
                } else {
                    // tiene participantes??
                    if (data.participantes ) {
                        // console.log('tiene participantes');
                        $('#tieneParticipantes').removeClass('d-none');
                    }
                    // console.log('no hay problema');
                    $('#modalDelete').modal();
                }
            }
        )
    })
    $('#BorrarDiccionarioBtnConfirmar').click((ev)=>{
        // var response = Response.redirect('./delete',status);
        window.location.href='./delete';
    })
    $('#BorrarDiccionarioBtnConfirmarOk').click((ev)=>{
        // var response = Response.redirect('./delete',status);
        window.location.href='./delete';
    })

    const busquedaAvanzadaHide = (ev) => {
        $('#buscadorAvanzado .contenidoAvanzado').hide();
        $('#busquedaAvanzadaBtn img').css('transform', '');
        $('#buscadorAvanzado').removeClass('activo');
    }

    const busquedaAvanzadaToogle = (ev) => {
        $('#buscadorAvanzado .contenidoAvanzado').toggle();
        if ( $('#buscadorAvanzado .contenidoAvanzado').css('display') == 'block' ) {
          $('#busquedaAvanzadaBtn img').css('transform', 'scaleY(-1)');
          $('#buscadorAvanzado').addClass('activo');
        } else {
          $('#busquedaAvanzadaBtn img').css('transform', '');
          $('#buscadorAvanzado').removeClass('activo');
        }
    }

    // $("#dicAulaPanelBuscador").on('submit', buscarEnvioEntradas(ev) );
    $('#busquedaAvanzadaBtn').on('click', (ev) => {
        ev.preventDefault();
        busquedaAvanzadaToogle(ev);
    });

    $('.clearSearch').on('click', (ev)=>{
        $('#dicAulaPanelBuscador')[0].reset();
        $('#selectedLetra').val('');
        $('[data-ajax="seleccionaLetra"] > .letra').removeClass('sel');
        $('#dicAulaResultados').html('');
    })
    const onInputChange = (ev) =>{
        $('.clearSearch').show();
    };
    $('#dicAulaPanelBuscador select').on('change', (ev) => {onInputChange(ev)} );
    $('#dicAulaPanelBuscador input').on('change', (ev) => {onInputChange(ev)} );


    $(document).on('click', '[data-event="restablecer-pautas"]', (event) => {
        let icon = event.currentTarget;
        let icono = $(icon)

        if ($('.modal-confirmar', icon.parentElement).length > 0) {
            $('.modal-confirmar', icon.parentElement).show();
            return;
        }
        // cierra otras ventanas emegentes;
        cerrarEmergentes();

        let modal = $('<div/>', {
            'class' : 'modal-confirmar d-block float-right',
        });

        let si = $('<button/>', {
            'class': 'mr-2 btn btn-primary',
            text: 'Restablecer',
            type: "button",
            click: () => {
                tinyMCE.activeEditor.setContent($('#mst_pautas').text())
                modal.remove()
            }
        });

        let no = $('<button/>', {
            'class': 'btn btn-secondary btn-danger',
            text: 'Cancelar',
            type: "button",
            click: () => {
                modal.remove()
            }
        });

        let botones = $('<div/>', {
            'class': 'text-center'
        });

        let text = $('<div/>', {
            'class': 'm-2 mb-3',
            text: 'Confirme el borrado de las pautas actuales y el restablecimiento de las originales'
        });

        si.appendTo(botones);
        no.appendTo(botones);
        text.appendTo(modal);
        botones.appendTo(modal)

        $(modal).appendTo(icon.parentElement);
    });

    /**
     * Cambia el icono y muestra mensaje al usuario
     * si se ha publicado o despublicado la entrada
     *
     * @author Fernando Ramirez <fernado.ramirez@altia.es>
     * @version 1.0.0
     * @param {*} result
     * @param {*} modal
     * @param {*} icono
     * @param {*} successBody
     * @param {*} errorBody
     */
    function muestraStatusPublicacion(result, modal, icono, successBody, errorBody){
        // console.log('done', result);

        // let target = $('input[type=submit]', ev.currentTarget).data('target')
        let view = result.view;
        // console.log('icono', icono);
        const targetEl = icono;
        // console.log('target el ', targetEl );
        const panelTarget = targetEl.parents('.dc-entradaEnviada-entradaAulaListado');
        // console.log( 'panelTarget', panelTarget );
        // .appendTo( targetEl.parents('.dc-entrada-botones-top').parent() );
        // console.log('post result',result );
        if (result.codeType == 'error') {
            if (result.message != '') {
                errorBody = result.message
            }
            $('p', '[data-panel=error]').html(errorBody);
            let errorPanel = $('[data-panel=error]')
            // targetEl.html(view);
            errorPanel.prependTo( panelTarget )
            addClassTimer(errorPanel, ['visibles'], 3000)

            targetEl.replaceWith(view);
        } else {
            // console.log("success");
            // console.log("icon", icono );
            $('p', '[data-panel=success]').html(successBody);
            let successPanel = $('[data-panel=success]')
            // targetEl.html(view);

            // console.log('parent botones top', targetEl.parents('.dc-entrada-botones-top'));
            successPanel.prependTo( panelTarget )
            addClassTimer(successPanel, ['visibles'], 3000)

            targetEl.replaceWith(view);
        }
        $('[data-toggle="tooltip"]').tooltip();
        modal.remove();
    }

    /**
     * Pubilcar entrada
    */
    $(document).on('click', '[data-event="publicar"]', (event) => {
        // console.log('[data-event="publicar"] called');
        // icono que se ha pulsado
        let icon = event.currentTarget;
        let icono = $(icon);
        //console.log('data del icono pulsadao', icono.data() );

        if ($('.modal-confirmar', icon.parentElement).length > 0) {
            $('.modal-confirmar', icon.parentElement).show();
            return;
        }

        let modal = $('<div/>', {
            'class' : 'modal-confirmar dc-emergente text-center',
            // 'style' : 'right: 1rem;top:auto;bottom: 56px; width: 200%;'
            //  height: -moz-fit-content;height: fit-content;'
        });


        let si = $('<button/>', {
            'class': 'mr-2 btn btn-primary',
            text: icono.data('action-text'),
            type: "button",
            click: () => {
                // TODO: esto deberia estar definido en lang/es/diccionario.php
                const successBody = 'Entrada publicada con éxito';
                const errorBody = 'Error al publicar la entrada';
                $.post( icono.data('url'), {  action: 'publicar' } )
                .done( function(result){
                    muestraStatusPublicacion(result, modal, icono, successBody, errorBody);
                    // deshabilitamos botón de eliminar envío
                    let entradaId = icono.data('entradaid');
                    let botonEliminar = $('#dicAulaResultados').find('.botonEliminar[data-entradaid="' + entradaId + '"]');
                    botonEliminar.prop('disabled', true);
                    botonEliminar.find('img').attr('src', rutaImagenGris);
                    // actualizamos datos publicar todos
                    cargarDatosPublicarTodos(
                        $('#cargarDatosPublicarTodos').data('actionurl'),
                        $( 'form#dicAulaPanelBuscador' ).serializeArray());
                })
            }
        });

        let no = $('<button/>', {
            'class': 'btn btn-secondary btn-danger',
            text: icono.data('cancel-text'),
            type: "button",
            click: () => {
                modal.remove()
            }
        });

        let botones = $('<div/>', {
            'class': 'text-center'
        });

        let text = $('<div/>', {
            'class': 'mb-3'
        });
        const bodyhtml = icono.data('body-text');
        text.html( bodyhtml );

        si.appendTo(botones);
        no.appendTo(botones);
        text.appendTo(modal);
        botones.appendTo(modal);
        // $("<hr style='clear:both'>").appendTo(modal);
        cerrarEmergentes();
        $(modal).appendTo(icon.parentElement);

    });
    /**
     * Despublicar entrada
    */
    $(document).on('click', '[data-event="anular-publicacion"]', (event) => {
        // console.log('data-event="anular-publicacion called');

        // icono que se ha pulsado
        let icon = event.currentTarget;
        let icono = $(icon);
        // console.log('data del icono pulsadao', icono.data() );

        if ($('.modal-confirmar', icon.parentElement).length > 0) {
            $('.modal-confirmar', icon.parentElement).show();
            return;
        }

        let modal = $('<div/>', {
            'class' : 'modal-confirmar dc-emergente text-center',
            // 'style' : 'right: 1rem;top:auto;bottom: 56px; width: 200%;'
            //  height: -moz-fit-content;height: fit-content;'
        });


        let si = $('<button/>', {
            'class': 'mr-2 btn btn-primary',
            text: icono.data('action-text'),
            type: "button",
            click: () => {
                //despubilcar entrada
                // <form data-event="publicar" data-action="anular" method="POST" action="{{route('diccionarioaula.publicar', ['id' => $diccionario->id, 'identrada' => $entrada->id])}}"></form>
                // TODO: esto deberia estar definido en lang/es/diccionario.php
                const successBody = 'Publicación anulada con éxito';
                const errorBody = 'Error al anular la publicación';

                $.post( icono.data('url'), {  action: 'anular' } )
                .done( function(result){
                    muestraStatusPublicacion(result, modal, icono, successBody, errorBody);
                    // Habilitamos botón de eliminar envío
                    let entradaId = icono.data('entradaid');
                    let botonEliminar = $('#dicAulaResultados').find('.botonEliminar[data-entradaid="' + entradaId + '"]');
                    botonEliminar.prop('disabled', false);
                    botonEliminar.find('img').attr('src', rutaImagen);
                    // actualizamos datos publicar todos
                    cargarDatosPublicarTodos(
                        $('#cargarDatosPublicarTodos').data('actionurl'),
                        $( 'form#dicAulaPanelBuscador' ).serializeArray());
                });
            }
        });

        let no = $('<button/>', {
            'class': 'btn btn-secondary btn-danger',
            text: icono.data('cancel-text'),
            type: "button",
            click: () => {
                modal.remove()
            }
        });

        let botones = $('<div/>', {
            'class': 'text-center'
        });

        let text = $('<div/>', {
            'class': 'mb-3'
        });
        const bodyhtml = icono.data('body-text');
        text.html( bodyhtml );

        si.appendTo(botones);
        no.appendTo(botones);
        text.appendTo(modal);
        botones.appendTo(modal);
        // $("<hr style='clear:both'>").appendTo(modal);
        cerrarEmergentes();
        $(modal).appendTo(icon.parentElement);

    });

    /**
     * Cambia el icono y muestra mensaje al usuario
     * si se ha eliminado la entrada
     *
     * @author Natalia Moreira <natalia.moreira@altia.es>
     * @version 1.0.0
     * @param {*} result
     * @param {*} modal
     * @param {*} icono
     * @param {*} successBody
     * @param {*} errorBody
     */
    function muestraStatusEliminar(result, modal, icono, successBody, errorBody){
        let view = result.view;
        const targetEl = icono;
        const panelTarget = targetEl.parents('.dc-entradaEnviada-entradaAulaListado');
        
        if (result.codeType == 'error') {
            if (result.message != '') {
                errorBody = result.message
            }
            $('p', '[data-panel=error]').html(errorBody);
            let errorPanel = $('[data-panel=error]')
            errorPanel.prependTo( panelTarget )
            addClassTimer(errorPanel, ['visibles'], 3000)

            targetEl.replaceWith(view);
        } else {
            $('p', '[data-panel=success]').html(successBody);
            let successPanel = $('[data-panel=success]')
            
            successPanel.prependTo( panelTarget )
            addClassTimer(successPanel, ['visibles'], 3000)

            targetEl.replaceWith(view);
        }
        $('[data-toggle="tooltip"]').tooltip();
        modal.remove();
    }

    /**
     * Eliminar envío de entrada
     */
    $(document).on('click', '[data-event="eliminar"]', (event) => {
        let icon = event.currentTarget;
        let icono = $(icon);
    
        if ($('.modal-confirmar', icon.parentElement).length > 0) {
            $('.modal-confirmar', icon.parentElement).show();
            return;
        }
    
        let modal = $('<div/>', {
            'class' : 'modal-confirmar dc-emergente text-center',
        });
    
        let si = $('<button/>', {
            'class': 'mr-2 btn btn-primary',
            text: icono.data('action-text'),
            type: "button",
            click: () => {
                const successBody = 'Entrada eliminada con éxito';
                const errorBody = 'Error al eliminar la entrada';
                $.post(
                    icono.data('url'),
                    {
                        action: 'eliminar'
                        // entradaId: icono.data('entradaid'),
                        // diccionarioId: icono.data('diccionarioid')
                    })
                    .done(function(result) {
                        muestraStatusEliminar(result, modal, icono, successBody, errorBody);
                        //alert('Envío de entrada eliminado con éxito.');
                        $('.submit').trigger('click');
                    })                    
                // ).fail(function (xhr, status, error) {
                //     console.error(error);
                //     alert('Error al eliminar el envío de entrada.');
                // });
            }
        });        
    
        let no = $('<button/>', {
            'class': 'btn btn-secondary btn-danger',
            text: icono.data('cancel-text'),
            type: "button",
            click: () => {
                modal.remove()
            }
        });
    
        let botones = $('<div/>', {
            'class': 'text-center'
        });
    
        let text = $('<div/>', {
            'class': 'mb-3'
        });
    
        const bodyhtml = icono.data('body-text');
        text.html(bodyhtml);
    
        si.appendTo(botones);
        no.appendTo(botones);
        text.appendTo(modal);
        botones.appendTo(modal);
        cerrarEmergentes();
        $(modal).appendTo(icon.parentElement);
    });
    

    // --- Publicar todas las entraadas del listado
    // Mostar ocultar modal-bocadillo publicar todos
    $(document).on('click', '#envios-publicar-todo', (ev)=>{
        cerrarEmergentes();
        $('#modal-publicar-listado').show();
    })
    // cargar datos del modal (boton para forzarol para develop
    $(document).on('click', '#cargarDatosPublicarTodos', (ev)=>{
        // console.log('click cargar dataos ', ev);
        const url = $('#cargarDatosPublicarTodos').data('actionurl');
        const params = $( 'form#dicAulaPanelBuscador' ).serializeArray();
        // console.log('se va a llamar cargarDatosPublicarTodos(url, params ); ', url,params);
        cerrarEmergentes();
        cargarDatosPublicarTodos(url, params );
    });
    $(document).on('click', '#modal-publicar-listado [data-action="close"]', (ev)=>{
        cerrarEmergentes();
        $('#modal-publicar-listado').hide();
    });
    // al picar en  cualquier enlace dentro del modal publicar todos cierra la ventana
    $(document).on('click', '#modal-publicar-listado a', (ev)=>{
        cerrarEmergentes();
        $('#modal-publicar-listado').hide();
    });
    // Boton de Aceptar Publicar todas dentro del modal
    $(document).on('click', '#modal-publicar-listado [data-action="publicar-todas"]', (ev)=>{
        cerrarEmergentes();
        $('#loader').show();
        const url = $(ev.target).data('url');
        const enviosId = $(ev.target).data('enviosid');
        // console.log('data url: ', url);
        // console.log('data envios: ', enviosId , $(ev.target) );
        const successBody = 'Entradas publicada con éxito';
        const errorBody = 'Error al publicar las entradas';

        const data = {
            'enviosId': enviosId
        }
        // console.log('data enviado',data);
        const targetEl = $('.grp-ordenar > span');
        $.post(url,data).done((result) => {
            $('#loader').hide();
            mostrarPanelMensaje( targetEl, successBody, 'success' );
            // recarga las busquedas depsues de mostrar el mensaje
            setTimeout(()=>{
                // console.log('dentro tiempout');
                buscarEnvioEntradas(ev);
            },3000);

        }).fail((result)=>{
            $('#loader').hide();
            mostrarPanelMensaje( targetEl, errorBody, 'error' );
        })
    });

    /**
     * Muestra un pequeño panel con menasaje
     * que ne devanece en 3sg
     *
     * @author Fernando Ramirez <fernado.ramirez@altia.es>
     * @version 1.0.0
     *
     * @param {Element} appendEl Elemente al que se anadira el panel
     * @param {String} msg Texto dentro del panel
     * @param {String} type (success|error)
    */

    const mostrarPanelMensaje = (appendEl, msg, type) =>{
        $('p', '[data-panel='+type+']').html(msg);
        const panel = $('[data-panel='+type+']')
        panel.appendTo( appendEl.parent('div') );
        addClassTimer(panel, ['visibles'], 3000);
    }


    $('.dc-pautas label').on('click', (ev)=>{
        const lbl = ev.currentTarget;
        const labelfor = $(lbl).attr('for');
        console.log('click en ".dc-pautas label"', lbl, labelfor );
        // evita que se llege a cerrarEmergentes, si llega ocuta tanto el 
        // menu como el panel de pautas

        // Toogle checkbox correspondiente
        $('#'+labelfor )[0].checked = !$('#'+labelfor )[0].checked;

        // ev.bubbles = true;
        // ev.stopPropagation();
        return false;
    });

});
// fin document ready

function generaCodigoAula() {
    // **** No se usa ya estudio y grupoletra para el codigo
    // if (document.getElementById('estudio').value > 0) {
    //     document.getElementById('nivelEstudioFinal').value = formatCode(document.getElementById('estudio').options[document.getElementById('estudio').selectedIndex].text);
    // } else {
    //     document.getElementById('nivelEstudioFinal').value = '';
    // }

    // if (document.getElementById('grupoLetra').value > '0') {
    //     document.getElementById('grupoFinal').value = document.getElementById('grupoLetra').value;
    // } else {
    //     document.getElementById('grupoFinal').value = '';
    // }

    if (document.getElementById('codigoAleatorio').value == 0) {
        document.getElementById('codigoAleatorio').value = Math.random().toString(36).substr(2, 6).toUpperCase();
    } else {
        console.log(document.getElementById('codigoAleatorio').value);
        document.getElementById('codigoAleatorio').value = document.getElementById('codigoAleatorio').value;
        // .slice(-4);
    }

    // if (document.getElementById('codigo').value == 0) {
        // const codigo =
        //         document.getElementById('nivelEstudioFinal').value +
        //         document.getElementById('grupoFinal').value +
        //         document.getElementById('codigoAleatorio').value;
        document.getElementById('codigo').value = document.getElementById('codigoAleatorio').value;
        // console.log('codigo', codigo);
    // }
}

function formatCode(code) {
    return code
        .replace(/\s/, '')
        .replace('º', '')
        .substring(0, 2);
}

function invitarDocenteEmail() {
    document.getElementById('invitarDocenteEmail').submit(function(e) {
        e.preventDefault();
        // Coding
        $('docentesModal').modal('toggle'); //or $('#IDModal').modal('hide');
            return false;
        });
}

const spinner = spinner => {
    let spinner_div = $('<div/>', {
        id: 'spinner',
        'class': 'spinner-border',
        role: 'status'
    })

    let span = $('<span/>', {
        'class': 'sr-only'
    })
    span.appendTo(spinner_div);
    spinner_div.appendTo($('#app'));
};

  $(document).on('click', '[data-event=guardarImportar]', function(ev){
    ev.preventDefault();
    const importar = $('input[type=checkbox]:checked', '.form-enviar-diccionarios');
    const importarId = importar.data('dataid');
    const importarTitle = importar.data('title');
    const txt = $('.form-enviar-diccionarios').find('#lang_importarDesde').val();
    // console.log('iportar object', importar);
    // console.log('importarTitel',importarTitle, importarId);

    // Entradas importadas desde "diccionario"
    $('.selected-element').html(txt + '"'+importarTitle+ '"');

  });

/**
 * Cargar datos acutaliazdos en el modal de pbulicar todos
 * @param {*} url
 * @param {*} params parametros de busqueda
 */
const cargarDatosPublicarTodos = (url, params) => {
    const tag = '[cargarDatosPublicarTodos]';
    // const btnPublicarTodos = $('#envios-publicar-todo');
    // console.log(tag, 'called', 'url', url, 'params', params );
    // Recarga el interior de modal-publicar-listado para reflejar los camibos que se hayan hecho
    // si se ha despublicado pubilcado uno suelto
    $.post(url, params).done(
        function(result){
            // console.log(tag,'result', result);
            const target = $('#modal-publicar-listado');
            const view = result.view;
            if (result.codeType == 'error') {
                console.log(tag,"error la cargar la vista", result.message);
                // target.html(view);
            } else {
                target.html(view);
            }
        }
    );

}

// console.log('dic_aula.js cargado');
