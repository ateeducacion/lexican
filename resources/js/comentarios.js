import tinymce from 'tinymce';
import $ from 'jquery';
// import Swiper from 'swiper';
import { rutaEnArray } from './utils';

/** 
 * Funcionalidades de comentarios
 * 
 * @author julio.buenadicha@altia.es
 *
 * @version 1.0.0
 * 
*/
$(document).ready(function () {

    // solo se ejecuta en estas rutas ( hay que cambiar el punto por - )
    const rutas = [
        'comentarios.enviar',
        'diccionarioaula.entradas'
    ];
    if (!rutaEnArray(rutas)) {
        // console.log('no se carga comentario.js  en ruta: ', $('body')[0].classList.value);
        // console.log('no esta en ', rutas);
        return false;
    }

    $(document).on('click', '[data-event="load-editor"]',() => {
        tinymce.remove();
        tinymce.init({
            selector: '.textarea-tiny',
            language: 'es'
        })
    })

    $(document).on('click', '[data-event="remove-editor"]',() => {
        tinymce.remove();
    })

    $(document).on('click', '[data-event="borrar-comentario"]', (event) => {
        let icon = event.currentTarget;
        let icono = $(icon)
        
        if ($('.modal-confirmar', icon.parentElement).length > 0) {            
            return;
        }

        let modal = $('<div/>', {
            'class' : 'modal-confirmar d-block col-12',
            'style' : 'top: -6.8rem; --pico-confirmar: 18.5rem; text-align: center; display: inline-block; width: 80%; height: -moz-fit-content; margin-right: 19px;'
            
        });

        let si = $('<button/>', {
            'class': 'mr-2 btn btn-primary',
            text: 'Si',
            type: "button",
            click: () => {
                let url = icono.data('action');
                let params = {};
                $.post(url, params).done(
                    (result) => {
                        $('#comentarModal_' +icono.data('parent')+ ' .listado-comentarios').html(result.view)
                    }
                );
            }
        });

        let no = $('<button/>', {
            'class': 'btn btn-secondary btn-danger',
            text: 'No',
            type: "button",
            click: () => {
                modal.remove()
            }
        });        

        let botones = $('<div/>', {
            'class': 'text-center'
        });

        let text = $('<div/>', {
            'class': 'mb-2',
            text: '¿Quiere borrar el comentario?'
        });

        si.appendTo(botones);
        no.appendTo(botones);
        text.appendTo(modal);        
        botones.appendTo(modal)

        $(modal).appendTo(icon.parentElement);
    })
    

    $(document).on('click', '[data-event="editar-comentario"]', (event) => {
        let icon = event.currentTarget;
        let icono = $(icon)

        if ($('.editar-comentario', icon.parentElement.parentElement.parentElement).length > 0) {            
            return;
        }

        let idcomentario = icono.data('element')
        
        let text = icono.parent().parent().siblings('.text-comentario-'+idcomentario);

        tinymce.init({
            selector: '.text-comentario-'+idcomentario,
            language: 'es'
        })

        let guardar = $('<button/>', {
            text: 'Guardar',
            'class': 'mr-2 btn btn-primary',
            type: "button",
            click: () => {
                tinymce.triggerSave()
                let url = icono.data('action');                
                let params = {'text': text.html()};                
                $.post(url, params).done(
                    (result) => {                        
                        $('#comentarModal_' +icono.data('parent')+ ' .listado-comentarios').html(result.view)
                    }
                );
            }
        });

        let descartar = $('<button/>', {
            text: 'Descartar',
            'class': 'btn btn-secondary btn-danger',
            type: "button",
            click: () => {
                tinymce.remove('.text-comentario-'+idcomentario);
                modal.remove();                
            }
        });

        let modal = $('<div/>', {
            'class': 'editar-comentario p-1'
        })

        guardar.appendTo(modal);
        descartar.appendTo(modal);
        modal.appendTo(icon.parentElement.parentElement.parentElement);
    })
});