/** 
 * Ordenar listado por javascript usando data-title y data-date como indices
 * 
 * @author Fernando Ramírez Pérez <fernando.ramirez@altia.es>
 * @version 1.0.0
 * 
 */

import {rutaEnArray} from './utils';


$( document ).ready(function() {

    // solo se ejecuta en estas rutas ( hay que cambiar el punto por - )
    const rutas = [
        'diccionarioaula-entradas',
    ];
    if ( !rutaEnArray(rutas) ) {
        return false;    
    }

    var orden = {
        'original': 0,
        'alfabetico': 1,
        'fecha': 2,
        'alfabeticoDescendente': 3,
        'fechaDescendente': 4,
    }
    var ordenEntradas = orden.fecha;

    /**
     * Ordenar alfabéticamente entradas
     * @param {Element} elemento del DOM donde estan los botones y se van a poner
     *     las flechas
     */
    function ordenarAlfabeticamente(el){
        // Se esta ordenado por index ya que el index viene ordenado alfabéticamente, y data-title
        // daba problemas con acentos y eñes
        console.log('ordenarAlfabeticamente(el)', el );
        const botones =$(el).parents('.dc-btn-ordenar').parent().find('.dc-btn-ordenar')
        botones.find('img').remove();

        if ( ordenEntradas != orden.alfabetico ) {
            imgAsc.appendTo(el);
            ordenEntradas = orden.alfabetico;
            $('#dicAulaResultados div.sortme').sort(function(a, b) {  
                return a.dataset.index - b.dataset.index;              
                // return (a.dataset.index < b.dataset.index)? -1 : 1;
            }).appendTo('#entradasEnviadas');
        } else {
            imgDesc.appendTo(el);
            ordenEntradas = orden.alfabeticoDescendente;
            $('#dicAulaResultados div.sortme').sort(function(a, b) {
                return b.dataset.index - a.dataset.index;              
                // return (a.dataset.index < b.dataset.index)? 1 : -1;
            }).appendTo('#entradasEnviadas');
        }        
    }

    /**
     * Ordenar entradas por fecha
     * @param {Element} elemento del DOM donde estan los botones y se van a poner
     *     las flechas
     */
    function ordenarPorFecha(el) {
        console.log('ordenarPorFecha(el)', el );
        const botones =$(el).parents('.dc-btn-ordenar').parent().find('.dc-btn-ordenar')
        botones.find('img').remove();

        if ( ordenEntradas !== orden.fecha  ) {
            imgAsc.appendTo(el);
            ordenEntradas = orden.fecha;
            $('#dicAulaResultados #entradasEnviadas div.sortme').sort(function(a, b) {
                // return (a.dataset.date < b.dataset.date)? -1 : 1;
                return a.dataset.date - b.dataset.date;
            }).appendTo('#entradasEnviadas');
        } else {
            imgDesc.appendTo(el);
            ordenEntradas = orden.fechaDescendente;
            $('#dicAulaResultados #entradasEnviadas div.sortme').sort(function(a, b) {
                // return (a.dataset.date < b.dataset.date)? 1 : -1;
                return b.dataset.date - a.dataset.date;
            }).appendTo('#entradasEnviadas');
        }        
    }

    $('#dicAulaResultados').on('click', '#ordernarPorFecha', (ev) => {
        // console.log('clicked', ev);
        ordenarPorFecha(ev.target);
    });
    $('#dicAulaResultados').on('click', '#ordenarAlfabeticamente', (ev) => {
        // console.log('clicked', ev);
        ordenarAlfabeticamente(ev.target);
    });


});
