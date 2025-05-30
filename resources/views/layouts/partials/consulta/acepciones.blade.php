{{--
Este archivo no lo estoy usando ahora mismo
ver archivos:
 - entrada.blade.php
 - acepcion.balde.php

--}}
@section('css')
<style>
    .dc-acepcion .left-icon {
        flex: 1;
        align-self: center;
        margin-bottom: 0.5rem;
    }

    .dc-acepcion .icon-column {
        width: 1.4rem;
    }
</style>
@append


    <div class="dc-acepciones">
        @foreach($entrada->dpAcepciones('asc')->get() as $key => $acepcion)

            <div class="dc-acepcion row mt-2 {{ $acepcion->orden == 1 ? "dc-acepcion-principal" : "dc-acepcion-secundaria d-none" }}">
                <div class="col-12">
                    <div class="row p-2">
                        <div class="col-md-1">
                            <div class="d-flex flex-column p-0 icon-column">

                                <a class="left-icon" href="{{ route('acepcion.edit', [$acepcion->dic_entrada_id, $acepcion->id]) }}">
                                    <img src="{{ asset('/imagenes/ico-edit.svg') }}" alt="Editar">
                                </a>

                                @if($entrada->dpAcepciones()->count()==1)
                                    <a class="left-icon" href="{{ route('acepcion.delete', [$acepcion->dic_entrada_id, $acepcion->id]) }}" onclick="return confirm('Al borrar esta acepción se va a borrar la entrada. ¿Está seguro?')">
                                        <img src="{{ asset('/imagenes/ico-trash.svg') }}" alt="Borrar">
                                    </a>
                                @else
                                    <a class="left-icon" href="{{ route('acepcion.delete', [$acepcion->dic_entrada_id, $acepcion->id]) }}" onclick="return confirm('{{ __('diccionario.confirm_acepcion_borrar') }}')">
                                        <img src="{{ asset('/imagenes/ico-trash.svg') }}" alt="Borrar">
                                    </a>
                                @endif
                            </div>
                        </div>
                        {{-- descripcion --}}
                        <div class="col-md-8">
                            <div class="row">
                                <div class="descripcion col-md-10">
                                    <p class="card-text">{{ $acepcion->orden }}. {{ $acepcion->atributos }}{{ $acepcion->definicion }}</p>
                                </div>
                                <div class="media col-md-2">
                                    @if(dpMedio_getAudio($acepcion))
                                        <img style="width: 250px" onclick="crearModalAudio('{{ dpMedio_getAudio($acepcion)->nombre }}', '{{ dpMedio_getAudioStoragePath($acepcion) }}')" src="{{ URL::to('/') }}/imagenes/imagen_audio.jpg" alt="audio" class="dc-ico-audio" >
                                    @endif

                                    @if(dpMedio_getVideo($acepcion))
                                        <img style="width: 250px" onclick="crearModalVideo('{{ dpMedio_getVideo($acepcion)->nombre }}', '{{ dpMedio_getVideoStoragePath($acepcion) }}')" src="{{ dpMedio_getVideoThumbnailStoragePath($acepcion) }}" alt="VIDEO">
                                    @endif
                                </div>
                            </div>
                        </div>
                        {{-- foto --}}
                        <div class="col-md-3 p-0">
                            <div class="foto-principal border">
                                @if(dpMedio_getImagen($acepcion))
                                    <a href='{{ "/storage/" . config("ctes.path_medios.imagen") . '/' .  dpMedio_getImagen($acepcion)->url_interna }}' download='{{ dpMedio_getImagen($acepcion)->nombre }}'>
                                        <img src="{{ '/storage/' . config('ctes.path_medios.imagen') . '/' . dpMedio_getImagen($acepcion)->url_interna }}" alt="IMAGEN" style="height: 100%; width: 100%;">
                                    </a>
                                @else
                                    <img src="{{ URL::to('/') }}/imagenes/icono_no_imagen.png" alt="IMAGEN">
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            @if( $acepcion->orden == 1 && $entrada->dpAcepciones('asc')->count()>1 )
                <div class="row d-block text-right">
                    <img class="desplegar cursor-pointer" src="{{ asset('/imagenes/ico-desplegar.svg') }}" alt="desplegar">
                </div>
            @endif

            {{-- d-none --}}
        @endforeach

        @if( $entrada->dpAcepciones('asc')->count()>1 )
            <div class="row text-right d-block">
                <img class="plegar flip-vertically cursor-pointer" src="{{ asset('/imagenes/ico-desplegar.svg') }}" alt="plegar">
            </div>
        @endif
    </div>



    <!-- Modal -->
    {{-- <div class="modal fade" id="myModal" role="dialog">
        <div class="modal-dialog">

            <!-- Modal content-->
            <div class="modal-content" style="width: 500px;">
                <div class="modal-header">
                    <h4 class="modal-title">titulo</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" style="text-align: center;">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-danger" data-dismiss="modal">@lang('diccionario.boton_cerrar')</button>
                </div>
            </div>
        </div>
    </div> --}}


    {{--
@section('scripts')
    <script>
        function crearModalVideo(titulo, url_interna) {

            var body = '<video id="video" autoplay width=\'401px\' controls>';

            body = body + '<source src=\'' + url_interna + '\' type=\'video/mp4\'>';
            body = body + '<source src=\'' + url_interna + '\' type=\'video/webm\'>';
            body = body + '<source src=\'' + url_interna + '\' type=\'video/ogg\'>';
            body = body + 'Su navegador no soporta el reproductor de vídeos.';

            body = body + '</video>';

            $('.modal-title').html(titulo);
            $('.modal-body').html(body);

            $('#myModal').on('hidden.bs.modal', function() {
                $.each($('audio'), function(index) {
                    $(this)[index].pause();
                });
                $.each($('video'), function(index) {
                    $(this)[index].pause();
                });
            })

            $('#myModal').modal('show');

        };

        function crearModalAudio(titulo, url_interna) {

            var body = '<audio id="audio" autoplay width=\'402px\' controls>';

            body = body + '<source src=\'' + url_interna + '\' type=\'audio/mpeg\'>';
            body = body + '<source src=\'' + url_interna + '\' type=\'audio/ogg\'>';
            body = body + '<source src=\'' + url_interna + '\' type=\'audio/wav\'>';
            body = body + 'Su navegador no soporta el reproductor de vídeos.';

            body = body + '</audio>';

            $('.modal-title').html(titulo);
            $('.modal-body').html(body);

            $('#myModal').on('hidden.bs.modal', function() {
                $.each($('audio'), function(index) {
                    $(this)[index].pause();
                });
                $.each($('video'), function(index) {
                    $(this)[index].pause();
                });
            })

            $('#myModal').modal('show');

        };
    </script> --}}
    <script src="{{ asset('js/acepciones.js') }}"></script>


    @endsection