
import $ from 'jquery';
import {rutaEnArray} from './utils';

/** 
 * consultaEntradaScroll.js
 * Carga ruta en window.routeMasEntradas con datos de las nuevas entradas... 
 * 
 * @author Fernando Ramirez Perez <fernando.ramirez@altia.es>
 * @version 1.0.0
 * 
*/
$( document ).ready(function() {

    var rutas = [
        'personal.consulta.all',
        'personal.consulta.byInitial',
        'personal.consulta.palabra',
        'personal.consulta.palabra.tematica',
        'personal.consulta.tematica',
        'aula.consulta.all',
        'aula.consulta.byInitial',
        'aula.consulta.palabra',
        'aula.consulta.palabra.tematica',
        'aula.consulta.tematica'
    ];

    if ( !rutaEnArray(rutas) ) {
        return false;    
    }
    window.scrollLastRow = 10;

    const scrollLoad = function () {
        console.log("scroll load ")
        if ($(window).scrollTop() >= $(document).height() - $(window).height() - 10) {
            $(window).off('scroll');
            cargarEntradas();
        }
    };
    
    
    const cargarEntradas = () => {
        console.log('cargar entradas');
        $('#loader').show();

        $.get( window.routeMasEntradas, (data) => {
            if (data.status == false) {
                $('#loader').hide();
                return false;
            }
            console.log('cargar mas entradas apartir de ', window.offset );
            console.log('ultimos actualizados apartir de:', window.scrollLastRow );
            var lines = data.split("\n");
            window.scrollLastRow = lines[0];
            
            if ( window.scrollLastRow !== window.offset ) {
                var dataHtml='';
                for (let i = 1, len = lines.length; i < len; i++) {
                    if (lines[i] !== ''){
                        dataHtml += lines[i];
                        // console.log('linea ', i , lines[i] );
                    }                    
                }
                
                $('#listadoEntradas').append(dataHtml);
                window.offset = window.offset + window.nentradas;
                var indexfin = window.routeMasEntradas.lastIndexOf('/');
                window.routeMasEntradas = window.routeMasEntradas.substr(0, indexfin+1) + offset;
                // activa lo tooltips para las nuevas entradas
                $('[data-toggle="tooltip"]').tooltip();
                // ocultamos circulo de cargando
                $('#loader').hide();
                // reactivamos el scroll 
                // console.log('reactivamos el scroll');
                $(window).scroll( () => { scrollLoad();} );
            }
        })
    };
    
    
    $('#btnMasEntradas').click( (ev) => {
        ev.preventDefault();
        ev.stopPropagation();
        cargarEntradas();
        
        return false;
    });

    $(window).scroll( () => { scrollLoad();} );

    // console.log('consultaEntradaScorll.js cargado');
    
});