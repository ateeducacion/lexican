/** 
 * utils.js
 * Para guardar pequeñas funciones que reutilizemoos
 * 
 * @author Fernando Ramirez Perez <fernando.ramirez@altia.es>
 * @version 1.0.0
 * 
*/
var $ = require('jquery');

var esRuta = function( nombreRuta ) {
    return $('body').hasClass(nombreRuta);
};

var rutaEnArray = function( rutas ){
    rutas.forEach((ruta,i) => {
        ruta = ruta.replace( /\./gi, '-');
        rutas[i] = ruta;
    });    
    existe = rutas.some( (ruta) => $('body').hasClass(ruta) );
    return existe;
};

/**
 * Oculta el menu bocadillo y todos sus botones
 */
var hideMenuBocadillo = (ev=null) => {
    // console.log('hideMenu called');
    $('.dc-bocadillo').hide();
    
    if ( 'null' != ev && $(ev).data('toggle') != 'modal') {
        $('.dc-actionbutton-menu').addClass('d-none');
    }
}

/**
 * Muestra/oculta el menu de quépuedohacer
 */
var quePuedoHacer = (dic) => {

    dic.toggleClass('d-none d-contents')
} 

// Cerrar otros menus o ventanas marcadas como dc-emergente
const cerrarEmergentes = (ev=null) => {
    // console.log('called cerrarEmergentes', 'ev:', ev);
    const emergentes = $(document).find('.dc-emergente');
    $(emergentes).hide();
    // bocadillos de action buttos y selecter de diccionario
    hideMenuBocadillo(ev);
    // ocultar menu quepuedo hacer
    // console.log("cerrar menu 'quepuedohacer' ");
    $('[data-cause=menu]').addClass('d-none d-contents');
    
    // console.log('elementos emergentes', emergentes.length );
}

const fechaStrFormatEs = ( datestr ) => {
    const date = new Date(datestr);
    // const outStr  = date.getDay() + '/' + date.getMonth() + '/' + date.getFullYear();
    // console.log(new Intl.DateTimeFormat('ar-EG').format(date));
    return fechaDateFormatEs(date);
}
const fechaDateFormatEs = ( date ) => {
    const dateFormatOptions = {year: 'numeric', month: '2-digit', day: '2-digit'};
    const dateFormat = new Intl.DateTimeFormat('es-ES', dateFormatOptions)
    const outStr = dateFormat.format(date)
    return outStr;
}

module.exports.esRuta =  esRuta;
module.exports.rutaEnArray = rutaEnArray;
module.exports.hideMenuBocadillo = hideMenuBocadillo;
module.exports.quePuedoHacer = quePuedoHacer;
module.exports.cerrarEmergentes = cerrarEmergentes;
module.exports.fechaStrFormatEs = fechaStrFormatEs;

