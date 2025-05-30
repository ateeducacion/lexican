$( document ).ready(function() {
    // console.log('cookies js');
    $('body').on('click','#cookieswarning .close', (ev)=>{
            $('#cookieswarning').css('display','none');
            $(".modal-backdrop").hide();
    });

    $('body').on('click','#cookiesOK', (ev)=>{
        // llamar por js a modificar cokies en php 
        // console.log('ruta',window.routeCookieAccept );
        $.get( window.routeCookieAccept, () => {
            $('#cookieswarning').css('display','none');
            $(".modal-backdrop").hide();
        });
    });
    // console.log('cookies jsfin');
});
