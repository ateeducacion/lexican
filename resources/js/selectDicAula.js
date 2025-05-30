/**
 * selectDicAula.js
 * Selector de diccionario de aula
 *
 * @author Fernando Ramirez Perez <fernando.ramirez@altia.es>
 * @version 1.0.0
 *
*/
import tinymce from 'tinymce';
import {rutaEnArray, hideMenuBocadillo, quePuedoHacer, cerrarEmergentes} from './utils';

$(() => {

    // solo se ejecuta en estas rutas
    // const rutas = [
    //     'diccionarioaula.get',
    //     'diccionariopersonal.get',
    // ];
    // if ( !rutaEnArray(rutas) ) {
    //     return false;
    // }

    $('body').on('click', '.quepuedohacer[data-event=quepuedohacer]', (ev) => {
        // console.log('click en que puedo hacer (data-event=quepuedohacer');
        // console.log("cerrar otros");
        cerrarEmergentes();
        quePuedoHacer($('[data-cause=menu]'));
        ev.stopPropagation();
    })

    tinymce.init({
        selector: '.textarea-tiny-read-only',
        menubar: false,
        statusbar: false,
        toolbar: false,
        inline_boundaries: false,
        visual: false,
        visual_table_class: 'read-only',
        readonly : 1,
        height : "75vh",
        relative_urls : false,
        remove_script_host : true,
        document_base_url : baseurl
    });
    //console.log('selectDicAuala.js loaded');

    // vincula window.diccionarioAulaActivo al dic en el select
    window.diccionarioAulaActivo = $('.dc-select-dicAula-activo select')[0];
    /// si cambia lo manada por ajax para guardarlo en la sesion

    $('.dc-select-dicAula-activo select').on('change', (ev) => {
        console.log('change to', ev.currentTarget.value);
        console.log('change to', diccionarioAulaActivo.value );
        var url = baseurl + '/aula/dicAulaActivo';
        console.log('url', url );
        var redirectUrl = window.location.href;
        if ( $('#aulaSelectredirectUrl') && $('#aulaSelectredirectUrl').val()!='' ){
            redirectUrl = $('#aulaSelectredirectUrl').val();
            redirectUrl = redirectUrl.replace('-id-', diccionarioAulaActivo.value );
        }

        var datos = {
            "dicActivoId": diccionarioAulaActivo.value,
            "redirectUrl": redirectUrl,
        };
        // token CSRF
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.post( url, datos, ( data ) => {
            //done callback
            console.log('data', data);
            // console.log('se guardo en la sesion el cambio de dic activo');
            // recagra pag
            // location.reload();
            // En lugar de refrescar vamos a url indicada en el include
            // :redirecturl
            location.replace(data.redirectUrl);

        }).fail( (data) => {
            console.log('fallo cambiar de dic');
        });
    });


    /**
     * Muestra/oculta bocadillo con menu para escoger diccionario de aula activo
     * Entra en conficto con los bocadillos que maneja menu-btn-presonal.js
    */
    $('.dc-select-dicAula-activo-show').on('click', (ev) => {
        console.log('click ', $(this) , ev.currentTarget );

        ev.stopPropagation();
        const bocadillo = $('#bocadillo-dic-aula');
        const bocadilloVisible = ($('#bocadillo-dic-aula').css('display') == 'block');
        cerrarEmergentes();
        hideMenuBocadillo(ev);
        
        if ( bocadilloVisible ) bocadillo.hide();
        else bocadillo.show();
        // bocadillo.toggle();

    });

    $('.dc-bocadillo-contenido .dc-sel-aula').click((ev)=>{
        // console.log('click', ev.currentTarget );
        const nuevoid = $(ev.currentTarget).data('id');
        let curid = 0;
        if ( $('.dc-select-dicAula-activo select').val() )
            curid = $('.dc-select-dicAula-activo select').val();

        // console.log('id pciado', nuevoid);
        // console.log('id actual', curid);
        if ( nuevoid != curid  ) {
            $('.dc-select-dicAula-activo select').val( nuevoid );
            // console.log($('.dc-select-dicAula-activo select').val());
            $('.dc-select-dicAula-activo select').trigger('change');
        }

    });

    // cambia view box de unirse a dic para que salga bien dimensiodado
    const unirseEl = $('#dc-select-dicaula-unirsedic svg')[0];
    if ( unirseEl ) unirseEl.setAttribute("viewBox", "0 0 45 45");

    // cerrar menus al picar en "creditos"
    $('#dc-creditos .menu-btn').click(()=>{
        // console.log("cerrar menus emergentes");
        cerrarEmergentes();
    });

    /** 
     * Cerrar emergenets al pulsar en cualquier parte 
    */
    $("#app").click(function(e){
        // console.log('-----click en content', e.target);
        cerrarEmergentes();
    });

    // console.log('selectDicAuala.js fin');
});