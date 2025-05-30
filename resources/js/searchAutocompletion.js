/** 
 * serachAutocompletion.js
 * Carga valores para autocompletar en buscador 
 * la ruto donde estan los valores esta escrita en el .blade de la pagina
 * en la variable window.routeSuggestions
 * 
 * @author Fernando Ramirez Perez <fernando.ramirez@altia.es>
 * @version 1.0.0
 * 
 */

// import $ from 'jquery';
import {rutaEnArray} from './utils';
import {typeahead} from 'jquery-typeahead';


$( () => {
    // No se ejecuta en estas rutas 
    const rutas = [
        'diccionario.enviar'
    ];
    if ( rutaEnArray(rutas) ) {
        return false;    
    }

    // console.log('typeahead', typeahead);
    const input = $('.js-typeahead-input');
    if (input) {
        typeof $.typeahead === 'function' && $.typeahead({
            input: ".js-typeahead-input",
            order: "asc",
            source: {
                entradas: {
                    ajax: {
                        url: window.routeSuggestions,
                        path: 'entradas',
                    }
                },
            },
            // callback: {
            //     onClickBefore: function () { 
            //       console.log('click before');
            //     }
            // },
            minLength: 2,
            hint: true,
            accent: true,
            emptyTemplate: 'No se encuentra entrada <i>"{{query}}"</i>',
            cancelButton: true,
        });
    }
    // console.log('typeahead fin');  

    // Funciones al picar y selecionar categorias 
    $('#labelCategorias').on('click', 'a', (ev)=>{
        ev.stopPropagation();
        ev.preventDefault();
        // ocultar/mostrar lista de categorias 
        $('#selectCategorias').toggleClass('d-none');
        const imgEl = $(ev.currentTarget).find(".dpAcepcionAnadirImg");
        // const isrc = imgEl.attr('src');
        // imgEl.attr('src',imgEl.data('imgtoogle'));
        // imgEl.data('imgtoogle',isrc);
        imgEl.toggleClass('flip-vertically');
        // console.log('isrc',isrc);
    } );

    // cargar los que ya esten selecionados
    const mostrarCategoriasSelecionadasEnInfo = (()=>{
        // console.log('mostrarCategoriasSelecionadasEnInfo lanzado');
        const seleccionadas = $('#list_tematica_id').find('option:selected');
        let html = '';
        // console.log('seleccionadas',seleccionadas.length);
        $(seleccionadas).each((index,el) => {
            html +='<span class="cursor-pointer badge badge-primary" data-id="'+el.value+'" >'+el.text+'</span> ';
        });
        if( seleccionadas.length > 0 ) {
            $(".infoLabel").removeClass('d-none');
            $(".noFilter").removeClass('d-none');
        } else {
            $(".infoLabel").addClass('d-none');
            $(".noFilter").addClass('d-none');
        }
        $('.dc-home-buscador .info').empty();
        $('.dc-home-buscador .info').append(html);
    });

    const deselecionarTodasBadge= (() => {
        const todas = $('#selectCategorias').find('.badge');
        todas.addClass('badge-secondary');
        todas.removeClass('badge-primary');
    });

    const cambiarEstadoBadgeCategoriasSelecionadas = (()=>{
        // console.log('cambiarEstadoBadgeCategoriasSelecionadas called');
        const seleccionadas = $('#list_tematica_id').find('option:selected');
        let tematicasIdsStr = $('#tematicas_ids');
        if(tematicasIdsStr === undefined){
            tematicasIdsStr = '';
        }
        tematicasIdsStr.val('');    
        deselecionarTodasBadge();
        $(seleccionadas).each((index,el) => {
            const badge = $('#selectCategorias').find('span:contains("'+el.text+'")');
            badge.addClass('badge-primary');
            badge.removeClass('badge-secondary');
            //agregamos string de ids al campo hidden
            tematicasIdsStr.val( tematicasIdsStr.val( ) + el.value + ',');
        });
        // quitamos la ultima coma:
        const str = tematicasIdsStr.val();
        tematicasIdsStr.val(str.substr(0,str.length-1));

    });

    const deselecionarTodasCategorias = (()=>{
        let seleccionadas = $('#list_tematica_id').find('option:selected');
        $(seleccionadas).each((index,el) => {
            // console.log("elemento selecionado", el);
            el.selected = false;
        });
        seleccionadas = $('#list_tematica_id').find('option:selected');
        //deseleciono las bagdes selecionadas
        $('#tematicas_ids').val('');
        $('#selectCategorias').find('.badge').each((index,el) => {
            $(el).removeClass('badge-primary');
            $(el).addClass('badge-secondary');
        });
        mostrarCategoriasSelecionadasEnInfo();        
    });

    /*
    * Al picar en una categoria "badge" se seleciona o deseleciona en el buscado
    */
    $("#selectCategorias").on('click','.badge', (ev)=>{
        // tematicas_max definido en app.blade.php cambiar en ctes.tematicas_max

        const el = $(ev.currentTarget);
        const id = el.data('id');
        const elOption = $('#list_tematica_id').find('option[value="'+id+'"]');
        // si se va a selecionar una nueva comprueba que no estan selecionadas el maximo
        if ( elOption.prop('selected')== false && $('#list_tematica_id option:selected').length >= tematicas_max){
            // console.log('maximo etiquetas selecionasdas' , tematicas_max );
            $('.alertaMaxEtiquetas').remove();
            const delay = 'data-delay=\'{"show":"5000", "hide":"3000"}\'';
            // $('#alertas');
            const msg = 'Para las búsquedas solo está permitido seleccionar un máximo de '+tematicas_max+' etiquetas';
            $('#alertas').append('<div class="alertaMaxEtiquetas alert alert-danger"><ul> <h4> <li>'+msg+'</li> </h4> </ul> </div>');
            $(".alertaMaxEtiquetas").delay(5000).hide(100,()=>{
                $("#alertaMaxEtiquetas").remove();
            });
            // $("#alertaMaxEtiquetas").remove();
            return;
        }
        elOption.prop('selected', !elOption.prop('selected'));
        el.toggleClass('badge-primary');
        el.toggleClass('badge-secondary');

        cambiarEstadoBadgeCategoriasSelecionadas();
        // mostrar todas las etiquetas selecionadas debajo del input
        mostrarCategoriasSelecionadasEnInfo();
    });

    $(".dc-home-buscador #formBuscador .info").on('click','.badge', (ev)=>{
        // console.log("click en .dc-home-buscador .infoLabel .badge");
        // deselecionar
        const el = $(ev.currentTarget);
        const id = el.data('id');
        const elOption = $('#list_tematica_id').find('option[value="'+id+'"]');
        elOption.prop('selected', !elOption.prop('selected'));                

        cambiarEstadoBadgeCategoriasSelecionadas();
        // mostrar todas las etiquetas selecionadas debajo del input
        mostrarCategoriasSelecionadasEnInfo();

    });

    $(".dc-home-buscador").on('click','.noFilter', (ev)=>{
        ev.stopPropagation();
        ev.preventDefault();
        console.log('noFilter click');
        deselecionarTodasCategorias();
    });

    // ejecuta al cargar la pagina
    cambiarEstadoBadgeCategoriasSelecionadas();
    mostrarCategoriasSelecionadasEnInfo();
});