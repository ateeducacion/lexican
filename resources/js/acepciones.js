/** 
 * Funcionalidades de acepciones buscador diccionario, desplegar y pelgar acepciones ocultas
 * 
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 * 
*/
// Carga jquery como un modulo para que funucione al pasarlo por webpack
import $ from 'jquery';

$( document ).ready(function() {

    $(document).on('click','.desplegar', (ev) => {
        // console.log('desplegar');        
        var acepcionesSec = $(ev.target).parents('.dc-entrada-container').find('.dc-acepcion-secundaria');
        // acepcionesSec.delClass('d-flex');
        acepcionesSec.hide();
        acepcionesSec.slideDown('slow');
        
        var iconoDesplegar = $(ev.target).parent();
        iconoDesplegar.removeClass('d-block').addClass('d-none');
        
        var iconoPlegar = $(ev.target).parents('.dc-entrada-container').find('.plegar').parent();
        iconoPlegar.removeClass('d-none').addClass('d-block');
        // expande el texto que esta limitado a aprox 5 lineas 
        // const textos = $(ev.target).parents('.dc-entrada-container').find('.dc-acepcion-texto');
        // textos.css('height', 'auto');

        return false;
    });
    $(document).on('click','.plegar', (ev) => {
        // console.log('plegar');
        var acepcionesSec = $(ev.target).parents('.dc-entrada-container').find('.dc-acepcion-secundaria')
        // acepcionesSec.removeClass('d-flex');
        // acepcionesSec.hide();
        acepcionesSec.slideUp('slow', ()=>{ 
            // acepcionesSec.removeClass('d-flex'); 
            acepcionesSec.hide();
        });

        // Vuellve a limitar el css 
        // const textos = $(ev.target).parents('.dc-entrada-container').find('.dc-acepcion-texto');
        // textos.css('height', '7.7rem');
        
        // oculta icono plegar
        $(ev.target).parent()
            .removeClass('d-block')
            .addClass('d-none');
        // muestra icono desplegar
        $(ev.target).parents('.dc-entrada-container').find('.desplegar').parent()
            .removeClass('d-none').addClass('d-block');



        return false;
    });

    // console.log('cargado acepcionesjs');
});