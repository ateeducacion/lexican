/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */
require('jquery');
require('./bootstrap');
// require('bootstrap-table');

// Importar tinymce
require('tinymce');
require('tinymce/plugins/paste/plugin');
require('tinymce/plugins/link/plugin');
require('tinymce/plugins/autoresize/plugin');
require('tinymce/plugins/image/plugin');
require('tinymce/plugins/advlist/plugin');
require('tinymce/plugins/autolink/plugin');
require('tinymce/plugins/lists/plugin');
require('tinymce/plugins/charmap/plugin');
require('tinymce/plugins/print/plugin');
require('tinymce/plugins/anchor/plugin');
require('tinymce/plugins/preview/plugin');
require('tinymce/plugins/visualblocks/plugin');
require('tinymce/plugins/searchreplace/plugin');
require('tinymce/plugins/code/plugin');
require('tinymce/plugins/fullscreen/plugin');
require('tinymce/plugins/insertdatetime/plugin');
require('tinymce/plugins/table/plugin');
require('tinymce/plugins/imagetools/plugin');
require('tinymce/plugins/media/plugin');
require('tinymce/plugins/contextmenu/plugin');

// Default icons are required for TinyMCE 5.3 or above
require('tinymce/icons/default');
// A theme is also required
require('tinymce/themes/silver');

require('./langs/es');

global.$ = global.jQuery = window.$ = window.jQuery = require('jquery');

require('dropify');
require('./dynamicPanels');

require('./acepciones');

require('./consultaEntradaScroll');
require('./modales');
require('./menu-btn-personal');
require('./searchAutocompletion');
require('./mediosAcepcion');
require('./dic_aula');
require('./comentarios');  
require('./selectDicAula');
require('./modificarDicPersonal');
require('./ordenar');
require('./cookies');

$( () => {
    // Poner en cursiva los selects cuando no se ha escogido nada
    // console.log("cargado select on change");
    $('body').on('change','select', (ev) => {
        // console.log("change");
        // console.log( 'val:', ev.target.value);
        if ( !ev.target.value || ev.target.value==0 ){
            // console.log(ev.target.style['font-style']);
            ev.target.style['font-style'] = 'italic';
        } else {
            // console.log('estilo sin cursiva');
            ev.target.style['font-style'] = 'normal';
        }
    }); 
    
    // console.log('tooltip');
    $('[data-toggle="tooltip"]').tooltip({
        trigger : 'hover'
    });
    
    // console.log('tinymceinit');
    // activo tinymc para todol los textarea con esta clase:
    tinymce.init({
        selector: '.textarea-tiny',
        language: 'es',
        height: 500,
        relative_urls : false,
        remove_script_host : true,
        document_base_url : baseurl
    });
});
