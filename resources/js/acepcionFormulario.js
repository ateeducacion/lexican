// import tinymce from 'tinymce';
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
        'acepcion.edit',
        'acepcion.create',
    ];
    if (!rutaEnArray(rutas)) {
        console.log('no se carga acepcionFomulario.js  en ruta: ', $('body')[0].classList.value);
        console.log('no esta en ', rutas);
        return false;
    }

});
