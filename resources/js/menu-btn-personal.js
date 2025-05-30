/** 
 * menu-btn-personal.js
 * Mostrar menu desplegable en los actionbutton ( menu bocadillo )
 * 
 * @author Fernando Ramirez Perez <fernando.ramirez@altia.es>
 * @version 1.0.0
 * 
*/
import {rutaEnArray, hideMenuBocadillo, cerrarEmergentes} from './utils';
import $ from 'jquery';
    
/**
     * Oculta el menu bocadillo y todos sus botones
     */
    // const hideMenu = () => {
    //     $('.dc-actionbutton-menu').addClass('d-none');
    //     // $('.dc-actionbutton-menu-bg').addClass('d-none');
    //     // $('.dc-actionbutton-menu-btn-enlace').addClass('d-none');
    // }


$(() => {

    // solo se ejecuta en estas rutas
    // const rutas = [
    //     'diccionarioaula.get',
    //     'diccionariopersonal.get',
    //     'personal-consulta-all',
    //     'personal.consulta.byInitial',
    //     'personal.consulta.palabra',
    //     'personal.consulta.busqueda',
    //     'aula.consulta.all',
    //     'aula.consulta.byInitial',
    //     'aula.consulta.palabra',
    //     'diccionario,modificar.ver',
    //     'diccionario,enviar',
    //     'entrada.buscador',
    //     'entrada.get',
    // ];
    // if ( !rutaEnArray(rutas) ) {
    //     // console.log('no se carga meun-btn-personal.js en ruta: ', $('body')[0].classList.value );
    //     // console.log('no esta en ', rutas );
    //     return false;    
    // }

    /**
     * Para ver informacion de debug del evento
     * @param {event} ev 
     */
    const eventInfo = (ev) => {
        console.log('evento', ev.target, ev.handleObj.type);
        console.dir(ev);
    }

    

    /**
     * Muestra el menu con mas botones
     * @param {event} ev Evento que ha llamado la accion
     * @param {String} actionButtonId Id del boton tipo .actionbutton dosde esta el menu que se av a mostrar
     */
    const showMenu = (ev, actionButtonId) => {
        console.log('showMenu called');
        cerrarEmergentes(ev);

        $('#' + actionButtonId + ' .dc-actionbutton-menu-bg').removeClass('d-none');
        $('#' + actionButtonId + ' .dc-actionbutton-menu-btn-enlace').removeClass('d-none');
        $('#' + actionButtonId + ' .dc-actionbutton-menu').removeClass('d-none');
        $('#' + actionButtonId + ' .dc-bocadillo').show();
        ev.stopImmediatePropagation();        
    };

    /**
     * Al pulsar en envios comentarios muestra el menu
     */
    // $('#envios-comentarios .actionbutton').click( (ev) => {
    //     showMenu(ev, 'envios-comentarios');
    // });

    /**
     * Al pulsar en gestion de diccionario muestra el menu
     */
    // $('#gestion-dic .actionbutton').click( (ev) => {
    //     showMenu(ev, 'gestion-dic');
    // });

    /** 
     * Al pulsar muestor el menu de el boton plusado y esconde los otros
    */
    $('.actionbutton').click( (ev) => {
        // console.log('actionbutton click');
        const buttonId = $(ev.currentTarget).parent('div')[0].id
        // si  ya se muestra el mismo menu, ocultar :
        const menuVisible = ($('#' + buttonId + ' .dc-bocadillo').css('display') == 'block');
        // console.log('menu visible: ', menuVisible, buttonId);

        if (menuVisible) hideMenuBocadillo(ev);
        else {
            // mostra menu 
            showMenu(ev, buttonId);
        }

    });   

    /* Al pulsar en el enlace muestra el modal */
    $('a[data-toggle="modal"]').click( (ev) => {         
        // console.log('enlace modal click');
        $($(ev.currentTarget).data('target')).modal('show');
        hideMenuBocadillo(ev.currentTarget);
        ev.stopImmediatePropagation();
    });

    /* Al pulsar en cualquir parte esconde los menus */
    // $('body').on('click' , (ev) => {
    //     console.log('body click');
    //     hideMenu();
    //     ev.stopImmediatePropagation();
    // });
});


