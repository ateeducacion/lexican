@if( !Cookie::get('lxcn_acceptcookies') )
<script>
    $( () => {
        // console.log('cookies en cookies blade');
        // var cookies_msg = '{!! __("diccionario.cookies_msg") !!}';
        var cokieshtml  = `<div id="cookieswarning" class="modal fade" tabindex="-1" role="dialog" >
                    <div id="cookiestxt" class="col-12 col-md-10 offset-md-1">
                        <button type="button" class="close" data-dismiss="cookieswarning">&times;</button>

                        <div>{!! __("diccionario.cookies_msg") !!}
                        </div>
                        <div class="mt-3 mx-auto" style="width: fit-content">
                            <button id="cookiesOK" class="btn btn-primary">Aceptar</button>
                        </div>
                    </div>
                </div>`;
        window.routeCookieAccept = '{{ route('aceptarCookies') }}';
        $('#padre_modal_ajax').html(cokieshtml);
        $("#modal_ajax").modal();
        $('#padre_modal_ajax').find('.modal').modal();
    });
</script>
@endif