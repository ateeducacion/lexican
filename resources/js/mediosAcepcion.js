var $ = require('jquery');

/** 
 * mediosAcepcion.js
 * Crear y mostrar modales de audio y video en cada acepcion
 * 
 * @author Fernando Ramirez Perez <fernando.ramirez@altia.es>
 * @version 1.0.0
 * 
*/

function crearModalAudio(titulo, url_interna) {
    var body = '<audio id="audio" autoplay width=\'400px\' controls>';

    body = body + '<source src=\'' + url_interna + '\' type=\'audio/mpeg\'>';
    body = body + '<source src=\'' + url_interna + '\' type=\'audio/ogg\'>';
    body = body + '<source src=\'' + url_interna + '\' type=\'audio/wav\'>';
    body = body + 'Su navegador no soporta el reproductor de vídeos.';

    body = body + '</audio>';

    $('#mediaModal .modal-title').html(titulo);
    $('#mediaModal .modal-body').html(body);

    $('#mediaModal').on('hidden.bs.modal', function() {
        console.log($('audio'))
        $.each($('audio'), function(index) {
            $(this)[index].pause();
        });
        $.each($('video'), function(index) {
            $(this)[index].pause();
        });
    })

    $('#mediaModal').modal('show');

};

function crearModalVideo(titulo, url_interna) {

    var body = '<video id="video" autoplay width=\'400px\' controls>';

    body = body + '<source src=\'' + url_interna + '\' type=\'video/mp4\'>';
    body = body + '<source src=\'' + url_interna + '\' type=\'video/webm\'>';
    body = body + '<source src=\'' + url_interna + '\' type=\'video/ogg\'>';
    body = body + 'Su navegador no soporta el reproductor de vídeos.';

    body = body + '</video>';

    $('#mediaModal .modal-title').html(titulo);
    $('#mediaModal .modal-body').html(body);

    $('#mediaModal').on('hidden.bs.modal', function() {
        $.each($('audio'), function(index) {
            $(this).remove();
            // $(this)[index].pause();
        });
        $.each($('video'), function(index) {
            $(this).remove();
            $(this)[index].pause();
        });
    });

    $('#mediaModal').modal('show');

};

$( document ).ready(function() {

    
    $( document ).on( 'click', '.dc-audio-modal', (ev) => {
        // if(clickOnce){
            console.log(ev);
            clickOnce = false;
            var args = $(ev.target).data('info').split(', ');
            crearModalAudio(
                args[0].substr( 1, args[0].length-2 ),
                args[1].substr( 1, args[1].length-1 )
            );
        // }
    });

    $( document ).on( 'click', '.dc-video-modal', (ev) => {
        // if(clickOnce){
            console.log(ev);
            var args = $(ev.target).data('info').split(', ');
            crearModalVideo(
                args[0].substr(1,args[0].length-2),
                args[1].substr(1,args[1].length-1)
            );
        // }
    });

});