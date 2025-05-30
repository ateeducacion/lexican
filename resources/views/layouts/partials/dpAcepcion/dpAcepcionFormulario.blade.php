@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('/css/dropify.min.css') }}">
<style>
    
.dropify-wrapper .dropify-message span.file-icon p{
    font-size: 0.9rem;
    color: rgb(119, 119, 119)
}

.btnMargin{
    margin: 10px;
}

</style>

@append


@section('content')
    @include('layouts.partials.components.quehacer')

    @include('layouts.partials.alerts.alertas')
    
    @include('layouts.partials.components.breadcrumb', ['breadcrumbs' => $datos->breadcrumb] )
    <div class="row">
        <div class="col-md-12">
            <h5 class="card-title">
                {{-- Mensaje: CREACIÓN DE LA ACEPCIÓN --}}
                <strong>{{ $datos->mensaje }}</strong>
                para "{{ $datos->entrada->entrada }}"
            </h5>
        </div>
    </div>


    <div class="dc-panel-gris card" > 
            @if(config('app.debug')) 
            <!-- filename:  dcApcepcionFormulario.blade.php -->
            @endif
            <h5 class="card-title">
                {{-- mensaje orden acepción 1 a la izquierda 
                <div style="float: left;">
                    @lang('diccionario.acepcion_orden') {{ $datos->acepcion->orden }}
                </div>
                --}}
                {{-- botón otro lenguaje a la derecha --}}
                <div style="float: right" class="cursor-pointer dc-round-btn">
                    <a onclick="mostrarOtroLenguaje()">
                        @lang('diccionario.acepcion_otro_idioma')
                            <img class="" src="{{ URL::to('/') }}/imagenes/boton_idioma.svg" />
                    </a>
                </div>
                {{-- necesario para limpiar los float --}}
                <div style="clear:both;"></div>
            </h5>
            <!-- <p class="card-text">With supporting text below as a natural lead-in to additional content.</p> -->

            @if(isset($datos->acepcion->id))
                <form id="formularioAcepcion" class="my-form" method="POST" action={{ route('acepcion.update', ['diccionario_id' => $datos->entrada->dic_personal_id  , 'entrada_id' => $datos->entrada->id, 'acepcion_id' => $datos->acepcion->id]) }} enctype="multipart/form-data">
                @else
                    {{-- <form  id="formularioAcepcion" class="my-form" method="POST"  enctype="multipart/form-data"> --}}
                    <form  id="formularioAcepcion" class="my-form" method="POST" action={{ action('DiccionarioPersonalController@dpInsertAcepcionPOST') }} enctype="multipart/form-data">
            @endif

            @csrf

            @if(isset($datos->acepcion->id))
                <input type="hidden" name="entrada_id" value="{{ $datos->entrada->id }}" />
                <input type="hidden" name="acepcion_id" value="{{ $datos->acepcion->id }}" />
                <input type="hidden" name="orden" value="{{ $datos->acepcion->orden }}" />
            @else
                <input type="hidden" name="entrada_entrada" value="{{ $datos->entrada->entrada }}" />
                <input type="hidden" name="orden" value="{{ $datos->acepcion->orden }}" />
            @endif

            @if($datos->acepcion->dpAcepcionMedios()->where('tipo_medio', '=',
                config('ctes.tipos_medios.imagen'))->first())
                <input type="hidden" name="imagen_filename_storage" value="{{ dpMedio_getImagen($datos->acepcion)->nombre }}" />
            @else
                <input type="hidden" name="imagen_filename_storage" value="" />
            @endif

            @if($datos->acepcion->dpAcepcionMedios()->where('tipo_medio', '=',
                config('ctes.tipos_medios.audio'))->first())
                <input type="hidden" name="audio_filename_storage" value="{{ dpMedio_getAudio($datos->acepcion)->nombre }}" />
            @else
                <input type="hidden" name="audio_filename_storage" value="" />
            @endif

            @if($datos->acepcion->dpAcepcionMedios()->where('tipo_medio', '=',
                config('ctes.tipos_medios.video'))->first())
                <input type="hidden" name="video_filename_storage" value="{{ dpMedio_getVideo($datos->acepcion)->nombre }}" />
            @else
                <input type="hidden" name="video_filename_storage" value="" />
            @endif

            @include('layouts/partials/dpAcepcion/acepcionFormFieldComun')
            
            {{-- línea de medios --}}
            <div class=" form-row">

                <div class="form-group col-md-4">
                    <div class="card text-success border-success mb-4">
                        <div class="card-header" style="height: 35px; padding-left: 20px; padding-top: 3px; padding-bottom: 0px;  ">
                            <div class="row">
                                <div class="col-md-2">
                                    @if(dpMedio_getImagen($datos->acepcion))
                                        @include('layouts.partials.components.modal-AceptarCancelar', [
                                        'id' => 'uid_'.uniqid(),
                                        'img' => asset('/imagenes/ico-trash.svg'),
                                        'modal_width' => '700px',
                                        'tooltip' => __('diccionario.acepcion_borrar_imagen'),
                                        'titulo' => __('diccionario.confirm_titulo'),
                                        'mensaje' => __('diccionario.confirm_imagen_borrar'),
                                        'action' => route('medio.delete', [$datos->entrada->dic_personal_id, $datos->acepcion->dic_entrada_id, $datos->acepcion->id, dpMedio_getImagen($datos->acepcion)->id ]),
                                        'imgstyle' => "height: 100%; float: left; "
                                        ])
                                        @else 
                                        <img id="borrarImg" class="card-img-top" data-toggle="tooltip" data-placement="top" data-original-title="Borrar Imagen"
                                        src="{{ URL::to('/') }}/imagenes/ico-trash.svg" alt="Borrar"> 
                                    @endif
                                </div>
                                @if(dpMedio_getImagen($datos->acepcion))
                                    <div class="col-md-10 d-inline-block text-truncate" title="{{ dpMedio_getImagen($datos->acepcion)->nombre }}">
                                        {{ dpMedio_getImagen($datos->acepcion)->url_interna }}
                                    </div>
                                @else
                                    <div class="col-md-10 d-inline-block text-truncate">
                                        <br />
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-body medio_imagen" style="padding-bottom: 0;">
                            {{-- <h5 class="card-title"></h5>
                                <p class="card-text"><br /></p> --}}
                                <div class="grab_foto" style="display: none; height: 164px;">
                                    
                                </div>
                            <input type="file" id="medio_imagen" name="medio_imagen" accept="{{ getTiposMediosMimeType(config('ctes.tipos_medios.imagen')) }}" class="dropify" data-height="150" data-height="150" data-max-file-size-preview="{{ config('ctes.dropify.image_max-file-size-preview') }}" data-max-file-size="{{ config('ctes.dropify.image_data-max-file-size') }}" data-allowed-file-extensions="{{ config('ctes.extensiones_medios.imagen') }}"

                                    {{-- vista previa de la imagen subida --}}
                                    @if(dpMedio_getImagen($datos->acepcion))
                                        data-default-file="{{ URL::to('/') .'/storage/' . config('ctes.path_medios.imagen') . '/' . getNombreFichero(dpMedio_getImagen($datos->acepcion)->url_interna) . config('ctes.video_thumbnail_sufijo') . ".jpg" }}"
                                        @endif
                            />
                        </div>
                        <div class="text-img-reemplazar" style="padding-left: 1.25em; visibility:hidden;"><span style="color:#777; font-size: 14px;">Al usar "Capturar" se desactiva "Arrastrar y soltar"</span></div>
                    </div>
                    
                    <div style=" display:flex; justify-content:center; align-items:center; flex-wrap: wrap;">
                            <div>
                            <button type="button" class="" id= "btnFoto"
                            style="font-size: large;"  data-mediaType="foto">Capturar<img src="{{asset('/imagenes/ico-camara.svg')}}"/></button>
                            </div>
                    </div>
                </div>

                <div class="form-group col-md-4">
                    <div class="card text-success border-success mb-4">
                        <div class="card-header" style="height: 35px; padding-left: 20px; padding-top: 3px; padding-bottom: 0px; ">
                            <div class="row">
                                <div class="col-md-2">
                                    @if(dpMedio_getAudio($datos->acepcion))
                                        @include('layouts.partials.components.modal-AceptarCancelar', [
                                        'id' => 'uid_'.uniqid(),
                                        'img' => asset('/imagenes/ico-trash.svg'),
                                        'modal_width' => '700px',
                                        'tooltip' => __('diccionario.acepcion_borrar_audio'),
                                        'titulo' => __('diccionario.confirm_titulo'),
                                        'mensaje' => __('diccionario.confirm_audio_borrar'),
                                        'action' => route('medio.delete', [$datos->entrada->dic_personal_id, $datos->acepcion->dic_entrada_id, $datos->acepcion->id, dpMedio_getAudio($datos->acepcion)->id ]),
                                        'imgstyle' => "height: 100%; float: left; "
                                        ])
                                        @else
                                        <img id="borrarAudio" class="card-img-top" data-toggle="tooltip" data-placement="top" data-original-title="Borrar Audio" 
                                        src="{{ URL::to('/') }}/imagenes/ico-trash.svg" alt="Borrar">
                                    @endif
                                </div>
                                @if(dpMedio_getAudio($datos->acepcion))
                                    <div class="col-md-10 d-inline-block text-truncate" title="{{ dpMedio_getAudio($datos->acepcion)->nombre }}">
                                        {{ dpMedio_getAudio($datos->acepcion)->url_interna }}
                                    </div>
                                @else
                                    <div class="col-md-10 d-inline-block text-truncate">
                                        <br />
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-body medio_audio" style="padding-bottom: 0;">
                            {{-- <h5 class="card-title"></h5>
                                    <p class="card-text"><br /></p> --}}
                            <div class="grab_audio" style="display: none; height: 164px;">
                                
                            </div>
                            
                            <input type="file" id="medio_audio" name="medio_audio" accept="{{ getTiposMediosMimeType(config('ctes.tipos_medios.audio')) }}" class="dropify" data-height="150" data-height="150" data-max-file-size="{{ config('ctes.dropify.audio_data-max-file-size') }}" data-allowed-file-extensions="{{ config('ctes.extensiones_medios.audio') }}" />
                        </div>
                        <div class="text-audio-reemplazar" style="padding-left: 1.25em; visibility:hidden;"><span style="color:#777; font-size: 14px;">Al usar "Grabar" se desactiva "Arrastrar y soltar"</span></div>
                    </div>
                    <div style=" display:flex; justify-content:center; align-items:center; flex-wrap: wrap;">
                            <div> 
                                <button type="button" class="" id= "btnAudio"
                                style="font-size: large;"  data-mediaType="audio">Grabar<img src="{{asset('/imagenes/ico-micro.svg')}}"/></button>   
                            </div> 
                    </div>
                </div>

                <div class="form-group col-md-4">
                    <div class="card text-success border-success mb-4">
                        <div class="card-header" style="height: 35px; padding-left: 20px; padding-top: 3px; padding-bottom: 0px;  ">
                            <div class="row">
                                <div class="col-md-2">
                                    @if(dpMedio_getVideo($datos->acepcion))
                                        @include('layouts.partials.components.modal-AceptarCancelar', [
                                        'id' => 'uid_'.uniqid(),
                                        'img' => asset('/imagenes/ico-trash.svg'),
                                        'modal_width' => '700px',
                                        'tooltip' => __('diccionario.acepcion_borrar_video'),
                                        'titulo' => __('diccionario.confirm_titulo'),
                                        'mensaje' => __('diccionario.confirm_video_borrar'),
                                        'action' => route('medio.delete', [$datos->entrada->dic_personal_id, $datos->acepcion->dic_entrada_id, $datos->acepcion->id, dpMedio_getVideo($datos->acepcion)->id ]) ,
                                        'imgstyle' => "height: 100%; float: left; "
                                        ])
                                        @else
                                        <img id="borrarVideo" class="card-img-top" data-toggle="tooltip" data-placement="top" data-original-title="Borrar Video" 
                                        src="{{ URL::to('/') }}/imagenes/ico-trash.svg" alt="Borrar">
                                    @endif
                                </div>
                                @if(dpMedio_getVideo($datos->acepcion))
                                    <div class="col-md-10 d-inline-block text-truncate" title="{{ dpMedio_getVideo($datos->acepcion)->nombre }}">
                                        {{ dpMedio_getVideo($datos->acepcion)->url_interna }}
                                    </div>
                                @else
                                    <div class="col-md-10 d-inline-block text-truncate">
                                        <br />
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-body medio_video" style="padding-bottom: 0;">
                            {{-- <h5 class="card-title"></h5>
                                <p class="card-text"><br />wwww</p> --}}
                                <div class="grab_video" style="display: none; height: 164px;">
                                    
                                </div>
                            <input type="file" id="medio_video" name="medio_video" accept="{{ getTiposMediosMimeType(config('ctes.tipos_medios.video')) }}" class="dropify" data-height="150" data-max-file-size="{{ config('ctes.dropify.video_data-max-file-size') }}" data-allowed-file-extensions="{{ config('ctes.extensiones_medios.video') }}" />
                        </div>
                        <div class="text-video-reemplazar" style="padding-left: 1.25em; visibility:hidden;"><span style="color:#777; font-size: 14px;">Al usar "Grabar" se desactiva "Arrastrar y soltar"</span></div>
                    </div>
                    <div style=" display:flex; justify-content:center; align-items:center; flex-wrap: wrap;">
                            <div> 
                            <button type="button" class="" id="btnVideo"
                            style="font-size: large;" data-mediaType="video">Grabar<img src="{{asset('/imagenes/ico-cam-video.svg')}}"/></button>   
                            </div> 
                    </div>
                </div>
            </div>

            {{-- Botones del formulario --}}
            <div class="text-center btnsForm">
                <button id="btnGuardarForm"  type="submit" class="btn btn-primary">@lang('diccionario.acepcion_guardar')</button>
                {{-- <a href="{{url()->previous() }}" class="btn btn-danger">@lang('diccionario.acepcion_cancelar')</a> --}}
                {{-- si es la primera acepcion cancelar redirije a listado  --}}
                @if ( $datos->entrada->dpAcepciones->count() == 0 )
                    <a href="{{ route('personal.consulta.all') }}" class="btn btn-danger">
                        @lang('diccionario.acepcion_cancelar')</a>
                @else                 
                    <a href="{{ route('entrada.get', [
                            'diccionario_id' => $datos->entrada->dic_personal_id, 
                            'entrada_id' => $datos->entrada->id
                        ]) }}" class="btn btn-danger">
                        @lang('diccionario.acepcion_cancelar')</a>
                @endif
            </div>
            </form>

        {{-- </div> --}}
    </div>


    <!---------------------------------------------------------------------------------------------
    //        MODAL DE VIDEO Y AUDIO
    //------------------------------------------------------------------------------------------- -->
    
    <div class="modal fade bd-example-modal-lg" id="modal-grabacion" tabindex="-1" role="dialog" aria-labelledby="modal-grabacionLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"> </h5>
                </div>
                <div class="modal-body">
                
                    <div id="containerGrabacion" style=" display:flex; justify-content:center; align-items:center; flex-wrap: wrap;">
                        <video muted id="video" autoplay></video>
                        <canvas id="canvasFoto" style="margin: 50px; border: 1px solid gray;"></canvas>
                        <img id="imgAudio" style="display: none;" src="{{asset('/imagenes/ondas-sonoras.png')}}">
                    </div>
                    <div class="contenedorTextoGrabacion">
                        <div id="icon-record" style="display:none;"><img style="width: 30px;" alt="Grabando" src="{{asset('/imagenes/ico-record.svg')}}"></div>
                        <div id="msg" class="text-center" style="font-size:25px; margin-left: 1rem;"></div>
                    </div>
                    <div id="textTiempoMaxAudio" style="text-align:center; display: none; margin-top: 20px;"><p style="font-size: 16px;"><i>Recuerde que el tamaño máximo permitido de la grabación es {{ config('ctes.dropify.audio_data-max-file-size') }}B, que corresponde a una duración aproximada de 5 minutos.</i></p></div>
                    <div id="textTiempoMaxVideo" style="text-align:center; display: none; margin-top: 20px;"><p style="font-size: 16px;"><i>Recuerde que el tamaño máximo permitido de la grabación es {{ config('ctes.dropify.video_data-max-file-size') }}B, que corresponde a una duración aproximada de 5 minutos.</i></p></div>
                </div>
                <div id="btnsContainer" class="modal-footer"> 
                    <div id="botonesAccion"></div>
                    <div id="botonesModal"></div>
                </div>
            </div>
        </div>
    </div>


    @append {{-- fin section content --}}


    @section('scripts')
    <script>
        jQuery(function($) {
            $('.dropify').dropify({
                messages: {
                    default: '{{ __("diccionario.acepcion_dropify_arrastra_click") }}' + '<br>'+
                            '{{ __("diccionario.acepcion_dropify_msg_max_size", ["size"=>config('ctes.dropify.image_max-file-size-preview').'B' ])}}'
                    , // 'Arrastra y suelta un archivo aquí o haz clic',
                    replace: '{{ __("diccionario.acepcion_dropify_arrastra_reemplazar") }}', // 'Arrastre y suelte un archivo o haga clic para reemplazar',
                    remove: '{{ __("diccionario.acepcion_dropify_eliminar") }}', // 'Eliminar',
                    error: '{{ __("diccionario.acepcion_dropify_error") }}', // 'Lo sentimos, ha ocurrido un error',
                },
                error: {
                    fileSize: '{{ __("diccionario.acepcion_dropify_grande") }} \{\{ value }}', //"El fichero es demasiado grande. Máximo (\{\{ value }})",
                    // minWidth: "'El ancho es demasiado pequeño. Mínimo (\{\{ value }}}px)",
                    // maxWidth: 'El ancho es demasiado grande. Máximo (\{\{ value }}}px)',
                    // minHeight: 'La altura de la imagen es demasiado pequeña. Mínimo (\{\{ value }}}px)',
                    // maxHeight: 'La altura de la imagen es demasiado grande. Máximo (\{\{ value }}px)',
                    fileExtension: '{{ __("diccionario.acepcion_dropify_no_permitido") }} \{\{ value }}', //'El tipo de fichero no está permitido. Los permitidos son (\{\{ value }})'
                }
            });

            // reseteamos grabaciones cuando sustituimos grabacion por archivo del ordenador
            $('input#medio_audio').change(function(e){
                delete grabacion.audio;
                $(".grab_audio").css("display", "none");
                $(".text-audio-reemplazar span").css("visibility", "hidden");
                $(".medio_audio .dropify-wrapper").css("display", "block");
            });
            $('input#medio_video').change(function(e){
                delete grabacion.video;
                $(".grab_video").css("display", "none");
                $(".text-video-reemplazar span").css("visibility", "hidden");
                $(".medio_video .dropify-wrapper").css("display", "block");
            })
            $('input#medio_imagen').change(function(e){
                delete grabacion.imagen;
                $(".grab_foto").css("display", "none");
                $(".text-img-reemplazar span").css("visibility", "hidden");
                $(".medio_imagen .dropify-wrapper").css("display", "block");
            })

        });

        // función para convertir bytes a MB
        function bytesToMegaBytes(bytes) {
            return bytes / (1024*1024);
        }

        function mostrarOtroLenguaje() {
            var x = document.getElementById("otroLenguaje");
            if (x.style.display === "none") {
                x.style.display = "";
            } else {
                x.style.display = "none";
            }

            // Pero por que lo borraba??
            // document.getElementById("idioma_palabra").value = "";
            // document.getElementById("idioma_id").value = "";
        }

        function tematicaAnadir() {
            // Añado la temática a la lista de seleccionadas
            $("#tematicas_disponibles > option").filter(":selected").each(function() {
                $('#tematicas_seleccionadas').append($('<option>', {
                    value: this.value,
                    text: this.text
                }));
                // Borro la temática de la lista de disponibles
                this.remove();
            });

            // Reordenar las listas de temáticas
            sortSelect(document.getElementById('tematicas_disponibles'));
            sortSelect(document.getElementById('tematicas_seleccionadas'));

            // Recalculo la lista de tematicas selecciondas
            calcularTematicasSeleccionadas();
        }

        function tematicaQuitar() {
            // Añado la temática a la lista de disponibles
            $("#tematicas_seleccionadas > option").filter(":selected").each(function() {
                $('#tematicas_disponibles').append($('<option>', {
                    value: this.value,
                    text: this.text
                }));
                // Borro la temática de la lista de seleccionadas
                this.remove();
            });

            // Reordenar las listas de temáticas
            sortSelect(document.getElementById('tematicas_disponibles'));
            sortSelect(document.getElementById('tematicas_seleccionadas'));

            // Recalculo la lista de tematicas selecciondas
            calcularTematicasSeleccionadas();
        }

        function sortSelect(selElem) {
            var tmpAry = new Array();
            for (var i = 0; i < selElem.options.length; i++) {
                tmpAry[i] = new Array();
                tmpAry[i][0] = selElem.options[i].text;
                tmpAry[i][1] = selElem.options[i].value;
            }
            tmpAry.sort();
            while (selElem.options.length > 0) {
                selElem.options[0] = null;
            }
            for (var i = 0; i < tmpAry.length; i++) {
                var op = new Option(tmpAry[i][0], tmpAry[i][1]);
                selElem.options[i] = op;
            }
            return;
        }

        function calcularTematicasSeleccionadas() {
            // console.log('lista tematicas', $("#listaTematicas").val() );
            $("#listaTematicas").val("");
            // console.log('lista tematicas borrada: ', $("#listaTematicas").val() );
            $("#tematicas_seleccionadas > option").each(function() {
                var listaTematicas = $("#listaTematicas").val();
                listaTematicas = listaTematicas + "," + this.value;
                $("#listaTematicas").val(listaTematicas);
                // console.log( 'lista tematicas opcion:', this.name, ':',  $("#listaTematicas").val() );
            });
        }

        // Filtrar las temáticas disponibles https://stackoverflow.com/questions/1447728/how-to-dynamic-filter-options-of-select-with-jquery
        jQuery.fn.filterByText = function(textbox, select_comparado) {
            return this.each(function() {
                var listaTematicas = [];
                var select = this;
                var options = [];
                var seleccionadas = [];


                // Rellenamos listaTematicas[] con las opciones de tematicas_disponibles y de tematicas_seleccionadas
                $(select).find('option').each(function() {
                    listaTematicas.push({
                        value: $(this).val(),
                        text: $(this).text()
                    });
                });
                $(select_comparado).find('option').each(function() {
                    listaTematicas.push({
                        value: $(this).val(),
                        text: $(this).text()
                    });
                });

                // Rellenamos options[] con las opciones de tematicas_disponibles
                $(select).find('option').each(function() {
                    options.push({
                        value: $(this).val(),
                        text: $(this).text()
                    });
                });
                $(select).data('options', options);

                $(textbox).bind('change keyup', function() {

                    // Refrescamos options[] con las todas las opciones
                    options = [];
                    options = listaTematicas.slice();

                    // Rellenamos seleccionadas[] con los valores de tematicas_seleccionadas
                    seleccionadas = [];
                    $(select_comparado).find('option').each(function() {
                        seleccionadas.push($(this).val());
                    });

                    // Recargo el tematicas_disponibles con todas las opciones posibles
                    $(select).empty().data('options');
                    var search = $.trim($(this).val());
                    var regex = new RegExp(search, "gi");

                    $.each(options, function(i) {
                        var option = options[i];
                        if (option.text.match(regex) !== null) {
                            if ($.inArray(option.value, seleccionadas) == -1) {
                                $(select).append(
                                    $('<option>').text(option.text).val(option.value)
                                );
                            } else {
                                // la opción está en el select tematicas_seleccionadas
                            }
                        }
                    });
                });
            });
        };

        jQuery(function($) {
            $('#tematicas_disponibles').filterByText($('#tematica_buscador'), $('#tematicas_seleccionadas'));
            
            // Si no se ejectuta al principio se pierden las tematicas guardadas:
            calcularTematicasSeleccionadas();
            if ($('#idioma_palabra').val() != ''){
                $('#otroLenguaje').show();
            }
        });

        // -------------------------------------------------------------------------------------------
        //          FUNCIONES DE LA VENTANA MODAL 
        //-------------------------------------------------------------------------------------------

        let grabacion = {};
        let medioDisponible = true;

        //Comprobamos si el navegador soporta las funciones de audio y video
        function tieneSoporteMedia(modal)
        {
            const tieneSoporteUserMedia = () =>
            //!!(navigator.mediaDevices.getUserMedia)
            navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            }).then(
                stream => {
                    medioDisponible = true;
                    return (video.srcObject = stream);
                },
                err => {
                    medioDisponible = false;
                    $('#modal-grabacion').modal('hide');
                    alert('La cámara está bloqueada. Asegúrate de concederle permisos de uso y de que no está siendo usada por otra aplicación.')
                }
            );

            if (typeof MediaRecorder === "undefined" || !tieneSoporteUserMedia()){
               return false
            }

            return true;
        }

        //Comprobamos si dispone de Micro o Video -> los valores del inputType pueden ser "videoinput" o "audioinput"
        function hayInputMedia(inputType)
        {
           
            console.log("-->"+inputType);
            
            return navigator
                .mediaDevices
                .enumerateDevices()
                .then(dispositivos => {

                    let hayInput = false

                    dispositivos.forEach((dispositivo, indice) => {
                        console.log(dispositivo.kind);
                        if(dispositivo.kind == inputType)
                            hayInput=true
                    })
                    
                    return hayInput
                }).catch(
                    error => {
                        console.log('error',error);
                    }
                )
        }

        function ShowHide_btns(btnsVisibles, listIdsBtns)
        {

            for (let id of listIdsBtns)
            {
                let element = document.getElementById(id);
                element.style.display = (btnsVisibles) ? "block" : "none"
            }
        }

        function mostarMsg(msg, color)
        {
            let divMsg= $("#msg").get(0);
            divMsg.className=""

            $("#msg").text(msg)
            $("#msg").addClass(color)
            //$("#msg").addClass("text-white")
            $("#msg").addClass("text-center")
        }
        
        //funcion para crear los btns que aparecen en la modal
        function createButtons(mediaType)
        {
            //VIDEO Y AUDIO
            let arrayPropertiesButtonsVideoAudio = [
                {
                    id: "btnIniciar",
                    tooltip: "Iniciar",
                    //color: "btn-success",
                    text: ""
                },
                {
                    id: "btnDetener",
                    tooltip: "Pausar",
                    //color: "btn-warning",
                    text: "",
                },
                {
                    id: "btnContinuar",
                    tooltip: "Continuar",
                    //color: "btn-primary",
                    text: "",
                },
                {
                    id: "btnReproducir",
                    tooltip: "Reproducir",
                    //color: "btn-secondary",
                    text: "",
                },
                {
                    id: "btnBorrar",
                    tooltip: "Borrar",
                    //color: "btn-danger",
                    text: "",
                }
                
            ]
            // botones guardar y cerrar en audio y video
            let arrayPropertiesButtonsModalVideoAudio = [
                {
                    id: "btnGuardar",
                    tooltip: "",
                    color: "btn-primary",
                    text: "Guardar y Salir",
                },
                {
                    id: "btnCerrar",
                    tooltip: "",
                    color: "btn-danger",
                    text: "Cancelar",
                }
            ]

            //FOTO
            let arrayPropertiesButtonsFoto = [
                {
                    id: "btnTomarFoto",
                    tooltip: "Sacar foto",
                    //color: "btn-success",
                    text: ""
                },
                {
                    id: "btnBorrarFoto",
                    tooltip: "Borrar foto",
                    //color: "btn-danger",
                    text: ""
                }
            ]

            // botones guardar y cerrar en foto
            let arrayPropertiesButtonsModalFoto= [
                {
                    id: "btnGuardarFoto",
                    tooltip: "",
                    color: "btn-primary",
                    text: "Guardar y Salir"
                },
                {
                    id: "btnCerrar",
                    tooltip: "",
                    color: "btn-danger",
                    text: "Cancelar",
                }
            ]

            let container=document.getElementById("botonesAccion");
            let containerBtnsModal=document.getElementById("botonesModal");

            let arrayButtons = (mediaType == "foto" ) ? arrayPropertiesButtonsFoto.slice() : arrayPropertiesButtonsVideoAudio.slice();
            let arrayButtonsModal = (mediaType == "foto" ) ? arrayPropertiesButtonsModalFoto.slice() : arrayPropertiesButtonsModalVideoAudio.slice();


            for (let btnProperties of arrayButtons) 
            {
                let btn = document.createElement("button")
                btn.setAttribute("id",btnProperties.id)
                btn.setAttribute("name","btnsModal")
                btn.setAttribute("data-toggle","tooltip")
                btn.setAttribute("data-placement","top")
                btn.setAttribute("data-original-title",btnProperties.tooltip)
                btn.innerText=btnProperties.text
                btn.classList.add("btn")
 
                btn.classList.add(btnProperties.color)

                container.appendChild(btn);
            }

            for (let btnPropertiesModal of arrayButtonsModal) 
            {
                let btnM = document.createElement("button")
                btnM.setAttribute("id",btnPropertiesModal.id)
                btnM.setAttribute("name","btnsModal")
                btnM.setAttribute("data-toggle","tooltip")
                btnM.setAttribute("data-placement","top")
                btnM.setAttribute("data-original-title",btnPropertiesModal.tooltip)
                btnM.innerText=btnPropertiesModal.text
                btnM.classList.add("btn")
 
                btnM.classList.add(btnPropertiesModal.color)

                containerBtnsModal.appendChild(btnM);
            }

            // cambiamos el icono del video que no es el mismo que el audio
            if(mediaType == "video"){
                $("#btnIniciar").css("background-image", "url('{{asset('/imagenes/ico-cam-video.svg')}}')");
            }
            // deshabilitamos los botones que no necesitamos
            $('#btnBorrarFoto').prop("disabled", true);
            $('#btnBorrar').prop("disabled", true);
            $('#btnContinuar').prop("disabled", true);
            $('#btnDetener').prop("disabled", true);
            $('#btnReproducir').prop("disabled", true);
            $('#btnGuardar').prop("disabled", true);
            $('#btnGuardarFoto').prop("disabled", true);

        }

        // funcion para abrir el modal correcto (según sea foto, audio o video) y comprueba si existe medio
        function grabacionCambiaModal(event) {            
            
            //console.log(event);
            let button = $(event.currentTarget);
            let mediaType = button.data('mediatype')         
            
            let modal = $(this);
            modal= $('#modal-grabacion');

            //Modicacion del contenido que hay en la modal
            if(mediaType == "foto"){
                modal.find('.modal-title').text("Sacar foto");
                $("#imgAudio").css("display", "none")
                $("#textTiempoMaxAudio").css("display", "none")
                $("#textTiempoMaxVideo").css("display", "none")
            }else if(mediaType == "audio"){
                modal.find('.modal-title').text("Grabación de audio");
                modal.find('#video').hide() 
                $("#imgAudio").css("display", "block")
                $("#textTiempoMaxAudio").css("display", "block")
                $("#textTiempoMaxVideo").css("display", "none")
            }else{
                modal.find('.modal-title').text("Grabación de vídeo");
                modal.find('#video').show()
                $("#imgAudio").css("display", "none")
                $("#textTiempoMaxVideo").css("display", "block")
                $("#textTiempoMaxAudio").css("display", "none")
            }
            
            $("#canvasFoto").hide()

            //comprobacion si hay audio y video
            let inputMedia= (mediaType == "audio") ? "audioinput" : "videoinput"

            hayInputMedia(inputMedia)
            .then( response =>{                    
                console.log("INPUT MEDIA => "+response);
                return response
            })
            .then( response => {                
                if(response) {
                    createButtons(mediaType)
                    actionsBtnsGrabacion(modal, mediaType);
                }else{
                    let msg = (inputMedia == "audio") ? "No hay micro" : "No hay video"
                    return alert(msg);
                }
            })
        };

        // funcion que comprueba si el medio está disponible para abir el modal
        function elegirModal(event) {

            const tieneSoporteUserMedia = (event) => navigator.mediaDevices.getUserMedia({
                video: true,
                audio: true
            }).then(
                stream => {
                    medioDisponible = true;
                    grabacionCambiaModal(event);
                    $('#modal-grabacion').modal('show');
                    return (video.srcObject = stream);
                },
                err => {
                    medioDisponible = false;
                    // $('#modal-grabacion').modal('hide');
                    alert('La cámara está bloqueada. Asegúrate de concederle permisos de uso y de que no está siendo usada por otra aplicación.')
                }
            );
            tieneSoporteUserMedia(event);
        }
        
        $('#btnFoto').on('click', (event) => elegirModal(event) );
        $('#btnAudio').on('click', (event) => elegirModal(event) );
        $('#btnVideo').on('click', (event) => elegirModal(event) );

        //Funcion que se ejecuta cuando se carga la modal en pantalla
        function actionsBtnsGrabacion(modal, mediaType){

            let mediaRecorder;
            let fragmentosDeAudio = [];

            let canvasFoto = $("#canvasFoto")

            $(function () {
                $('[data-toggle="tooltip"]').tooltip({
                    trigger : 'hover'
                })
            })

            navigator.mediaDevices.getUserMedia({
                    audio: (mediaType == "video" || mediaType == "audio" ) ? true : false,
                    video: (mediaType == "video" || mediaType == "foto" ) ? true : false
            })
            .then(stream => {

                if(mediaType == "video" || mediaType == "foto" ) 
                    video.srcObject = stream

                //---------------------------------
                // VIDEO Y AUDIO
                //---------------------------------
                //INICIAR GRABACION Y AUDIO
                $('#btnIniciar').click((e) => {
                    
                    if(mediaRecorder)
                        return alert("Ya hay una grabación en curso");
                    
                    mediaRecorder=null;
                    fragmentosDeAudio=[]
                   
                    //if(!hayInput("audioinput")) return alert("No hay micro");
                    //if(!hayInput("videoinput") && mediaType == "video") return alert("No hay video");

                    if(mediaType == "video" ) video.play()

                    mediaRecorder = new MediaRecorder(stream);
                    mediaRecorder.start(100);
                    
                    mostarMsg("Grabando...", "text-danger")
                    $('#icon-record').css("display", "block");

                    mediaRecorder.ondataavailable = (evento => {
                        fragmentosDeAudio.push(evento.data);
                        console.log("Grabando");
                    });

                    // Habilitamos y deshabilitamos botones
                    $('#btnDetener').prop("disabled", false);
                    $('#btnDetener').css("background-image", "url('{{asset('/imagenes/ico-stop.svg')}}')");
                    $('#btnGuardar').prop("disabled", false);
                    $('#btnIniciar').prop("disabled", true);
                    if(mediaType == "audio"){
                        $('#btnIniciar').css("background-image", "url('{{asset('/imagenes/ico-micro-rojo.svg')}}')");
                    }
                    if(mediaType == "video"){
                        $('#btnIniciar').css("background-image", "url('{{asset('/imagenes/ico-cam-video-rojo.svg')}}')");
                    }

                })

                //PAUSAR GRABACION Y AUDIO
                $('#btnDetener').click((e) => {

                    if(fragmentosDeAudio.length <= 0 || mediaRecorder==null)
                        return alert("No se puede pausar. No hay nada grabado")
                    
                    if(mediaRecorder.state=="paused")
                        return alert("La grabacion ya esta en pausa")

                    //ShowHide_btns(false,["btnIniciar","btnDetener"])

                    // Habilitamos y deshabilitamos botones
                    $('#btnIniciar').prop("disabled", true);
                    if(mediaType == "audio"){
                        $('#btnIniciar').css("background-image", "url('{{asset('/imagenes/ico-micro-gris.svg')}}')");
                    }
                    if(mediaType == "video"){
                        $('#btnIniciar').css("background-image", "url('{{asset('/imagenes/ico-cam-video-gris.svg')}}')");
                    }
                    $('#btnContinuar').prop("disabled", false);
                    $('#btnContinuar').css("background-image", "url('{{asset('/imagenes/ico-continuar.svg')}}')");
                    $('#btnReproducir').prop("disabled", false);
                    $('#btnReproducir').css("background-image", "url('{{asset('/imagenes/ico-play.svg')}}')");
                    $('#btnBorrar').prop("disabled", false);
                    $('#btnBorrar').css("background-image", "url('{{asset('/imagenes/ico-trash.svg')}}')");
                    $('#btnDetener').prop("disabled", true);
                    $('#btnDetener').css("background-image", "url('{{asset('/imagenes/ico-stop-gris.svg')}}')");

                    mediaRecorder.pause();
                    video.pause();

                    mostarMsg("Pausa...", "")
                    $('#icon-record').css("display", "none");
                })
                
                //CONTINUAR GRABACION Y AUDIO
                $('#btnContinuar').click((e)=> {

                    if(fragmentosDeAudio.length <= 0 || mediaRecorder==null )
                        return alert("No se puede reproducir. No hay nada grabado")

                    if(mediaRecorder.state == "recording") 
                        return alert("La grabacion ya esta en reproduciendo.")

                    //ShowHide_btns(true,["btnIniciar","btnDetener"])

                    // Habilitamos y deshabilitamos botones
                    $('#btnReproducir').prop("disabled", true);
                    $('#btnReproducir').css("background-image", "url('{{asset('/imagenes/ico-play-gris.svg')}}')");
                    $('#btnDetener').prop("disabled", false);
                    $('#btnDetener').css("background-image", "url('{{asset('/imagenes/ico-stop.svg')}}')");
                    $('#btnContinuar').prop("disabled", true);
                    $('#btnContinuar').css("background-image", "url('{{asset('/imagenes/ico-continuar-gris.svg')}}')");
                    if(mediaType == "audio"){
                        $('#btnIniciar').css("background-image", "url('{{asset('/imagenes/ico-micro-rojo.svg')}}')");
                    }
                    if(mediaType == "video"){
                        $('#btnIniciar').css("background-image", "url('{{asset('/imagenes/ico-cam-video-rojo.svg')}}')");
                    }

                    mediaRecorder.resume();
                    if(mediaType == "video" ) video.play();

                    mostarMsg("Grabando...", "text-danger")
                    $('#icon-record').css("display", "block");
                })

                //REPRODUCIR GRABACION Y AUDIO
                $('#btnReproducir').click((e) => {

                    if(fragmentosDeAudio.length <= 0 || mediaRecorder==null )
                        return alert("No se puede reproducir. No hay nada grabado")

                    if(mediaRecorder.state == "recording") 
                        return alert("Pausa la grabacion, para poder reproducirla.")

                    mostarMsg("Reproduciendo...", "")
                    $('#icon-record').css("display", "none");

                    const blobGrabacion = new Blob(fragmentosDeAudio);
                    const url = URL.createObjectURL(blobGrabacion);

                    let container = document.getElementById("containerGrabacion")

                    // if(mediaType=="video")
                    //     $("video").get(0).style.display="none"

                    let element;
                    if(mediaType == "video")
                        element=document.createElement("video")
                    else
                        element=document.createElement("audio")

                    container.appendChild(element)

                    //ShowHide_btns(false,["btnIniciar","btnDetener","btnContinuar","btnBorrar","btnReproducir","btnCerrar"])
                   
                    // Habilitamos y deshabilitamos botones
                    $('#btnBorrar').prop("disabled", false);
                    $('#btnBorrar').css("background-image", "url('{{asset('/imagenes/ico-trash.svg')}}')");
                    $('#btnReproducir').prop("disabled", true);
                    $('#btnReproducir').css("background-image", "url('{{asset('/imagenes/ico-play-gris.svg')}}')");

                    element.src=url
                    element.controls="controls"
               
                    element.onloadeddata = function() {
                        
                        this.play();
                    
                        this.onended = function(e) {

                            this.src=""
                            this.remove()

                            // if(mediaType=="video")
                            //     $("video").get(0).style.display="block"

                            //ShowHide_btns(true,["btnIniciar","btnDetener","btnContinuar","btnBorrar","btnReproducir", "btnCerrar"])
                            mostarMsg("Pausa...", "")
                            $('#icon-record').css("display", "none");
                        };
                    }
                      
                })

                //GUARDAR GRABACION Y AUDIO
                $('#btnGuardar').click((e) => {

                    if(fragmentosDeAudio.length <= 0 || mediaRecorder==null)
                        return alert("No se puede guardar. No hay nada grabado")
                    
                    video.pause();
                    mediaRecorder.pause();

                    const blobGrabacion = new Blob(fragmentosDeAudio);
                    const url = URL.createObjectURL(blobGrabacion);

                    //ShowHide_btns(true,["btnIniciar","btnDetener"])

                    //comprobamos que no exceda del tamaño permitido
                    let tamanhoGrabacion = bytesToMegaBytes(blobGrabacion.size);
                    let tamanhoMaxAudio = "{{ config('ctes.dropify.audio_data-max-file-size') }}";
                    let tamanhoMaxVideo = "{{ config('ctes.dropify.video_data-max-file-size') }}";
                    
                    if(tamanhoGrabacion > tamanhoMaxAudio || tamanhoGrabacion > tamanhoMaxVideo){                        
                        alert('No es posible guardar la grabación, excede el máximo permitido');
                    }else{
                        if(mediaType == "audio"){
                            grabacion.audio = {name: mediaType, content:blobGrabacion};
                        }
                        if(mediaType == "video"){
                            grabacion.video = {name: mediaType, content:blobGrabacion};
                        }

                        mostarMsg("Guardada...", "")
                        $('#icon-record').css("display", "none");

                        //Reiniciarmos el div de los  msg
                        $("#msg").get(0).className=""
                        $("#msg").get(0).innerText=""
                        // cerramos modal
                        modal.modal('hide');

                        // previsualizamos icono de grabacion añadida
                        if(mediaType == "audio"){
                            // vaciamos primero el input si hay algo
                            $("#medio_audio").parent().find('.dropify-clear').trigger('click');
                            $(".grab_audio").css("background-image", "url('{{asset('/imagenes/img-ico-audio.png')}}')");
                            $(".medio_audio .dropify-wrapper").css("display", "none");
                            $(".grab_audio").css("display", "flex");
                            $(".text-audio-reemplazar span").css("visibility", "visible");
                        }
                        if(mediaType == "video"){
                            // vaciamos primero el input si hay algo
                            $("#medio_video").parent().find('.dropify-clear').trigger('click');
                            $(".grab_video").css("background-image", "url('{{asset('/imagenes/img_ico_video.png')}}')");
                            $(".medio_video .dropify-wrapper").css("display", "none");
                            $(".grab_video").css("display", "flex");
                            $(".text-video-reemplazar span").css("visibility", "visible");
                        }
                    }

                })

                //BTN BORRAR GRABACION
                $('#btnBorrar').click((e) => {
                    
                    if(fragmentosDeAudio.length <= 0 || mediaRecorder=="null")
                        return alert("No se puede borrar. No hay nada grabado")

                    if(mediaRecorder.state != "inactive")
                        mediaRecorder.pause();

                    if(mediaType == "video" ) video.pause();

                    if(confirm('¿Quieres borrar la grabación?\nRecuerda que una vez borrado no podrás recuperarla.'))
                    {
                        mediaRecorder=null;
                        fragmentosDeAudio=[];
                    }else{
                        if(mediaType == "video" )
                            video.play();
                        mediaRecorder.resume();
                    }

                    //ShowHide_btns(true,["btnIniciar","btnDetener"])

                    // Habilitamos y deshabilitamos botones
                    $('#btnIniciar').prop("disabled", false);
                    if(mediaType == "audio"){
                        $('#btnIniciar').css("background-image", "url('{{asset('/imagenes/ico-micro.svg')}}')");
                    }
                    if(mediaType == "video"){
                        $('#btnIniciar').css("background-image", "url('{{asset('/imagenes/ico-cam-video.svg')}}')");
                    }
                    $('#btnContinuar').prop("disabled", true);
                    $('#btnContinuar').css("background-image", "url('{{asset('/imagenes/ico-continuar-gris.svg')}}')");
                    $('#btnDetener').prop("disabled", true);
                    $('#btnDetener').css("background-image", "url('{{asset('/imagenes/ico-stop-gris.svg')}}')");
                    $('#btnReproducir').prop("disabled", true);
                    $('#btnReproducir').css("background-image", "url('{{asset('/imagenes/ico-play-gris.svg')}}')");
                    $('#btnBorrar').prop("disabled", true);
                    $('#btnBorrar').css("background-image", "url('{{asset('/imagenes/ico-trash-gris.svg')}}')");
                    $('#btnGuardar').prop("disabled", true);

                    if(mediaType == "audio"){
                        // vaciamos el input si hay algo
                        $("#medio_audio").parent().find('.dropify-clear').trigger('click');
                        $(".medio_audio .dropify-wrapper").css("display", "block");
                        $(".grab_audio").css("display", "none");
                        $(".text-audio-reemplazar span").css("visibility", "hidden");
                    }
                    if(mediaType == "video"){
                        // vaciamos el input si hay algo
                        $("#medio_video").parent().find('.dropify-clear').trigger('click');
                        $(".medio_video .dropify-wrapper").css("display", "block");
                        $(".grab_video").css("display", "none");
                        $(".text-video-reemplazar span").css("visibility", "hidden");
                    }

                    mostarMsg("Borrado...", "")
                    $('#icon-record').css("display", "none");
                })

                //BTN CERRAR MODAL
                $('#btnCerrar').click((e) => {
                    
                    //if (confirm("¿Quieres cerrar la ventana?")){
                        if(mediaRecorder!=null)
                            mediaRecorder.pause()

                        if(mediaType == "video" || mediaType == "foto") video.play()
                         
                        fragmentosDeAudio=[];
                        mediaRecorder = null

                        //Reiniciarmos el div de los  msg
                        $("#msg").get(0).className=""
                        $("#msg").get(0).innerText=""

                        // if(mediaType == "video" || mediaType == "audio")
                        //     ShowHide_btns(true,["btnIniciar","btnDetener"])
                        
                        //Con esto se detien la grabacion de la camara poniendo mediaRecorder.state == inactive, pero no lo deja a volver iniciar en el caso de detenerla
                        //stream.getTracks().forEach(track => track.stop());
                        modal.modal('hide');
                    //}
                })

                //---------------------------------
                // FOTO
                //---------------------------------

                $('#btnTomarFoto').click((e)=>{
                    //Pausar reproducción
                    video.pause();
                    grabacion.imagen = null;

                    //Obtener contexto del canvas y dibujar sobre él
                    let contexto = canvasFoto.get(0).getContext("2d");
                    canvasFoto.get(0).width = video.videoWidth ;
                    canvasFoto.get(0).height = video.videoHeight ;
                    contexto.drawImage(video, 0, 0, canvasFoto.get(0).width, canvasFoto.get(0).height);
                    canvasFoto.show()

                    video.play()

                    //ShowHide_btns(true,["btnBorrarFoto"])

                    // Habilitamos botones
                    $('#btnBorrarFoto').prop("disabled", false);
                    $('#btnBorrarFoto').css("background-image", "url('{{asset('/imagenes/ico-trash.svg')}}')");
                    $('#btnGuardarFoto').prop("disabled", false);
                })

                $('#btnBorrarFoto').click((e)=>{
                    let contexto = canvasFoto.get(0).getContext("2d");
                    contexto.clearRect(0,0, video.videoWidth, video.videoHeight);
                    grabacion.imagen  = null;

                    // Deshabilitamos botones
                    $('#btnBorrarFoto').prop("disabled", true);
                    $('#btnBorrarFoto').css("background-image", "url('{{asset('/imagenes/ico-trash-gris.svg')}}')");
                    $('#btnGuardarFoto').prop("disabled", true);
                    // vaciamos el input si hay algo
                    $("#medio_imagen").parent().find('.dropify-clear').trigger('click');

                    $(".medio_imagen .grab_foto").css("display", "none");
                    $(".text-img-reemplazar span").css("visibility", "hidden");
                    $(".medio_imagen .dropify-wrapper").css("display", "block");
                })

                $('#btnGuardarFoto').click((e)=>{
                    
                    fetch( canvasFoto.get(0).toDataURL())
                    .then(function (response) {
                        return response.blob();
                    })
                    .then(function(blob){
                        console.log(blob.size+"  "+blob.type);
                        mostarMsg("Foto guardada...", "")
                        grabacion.imagen = {name: "imagen", content: blob};
                        //Reiniciarmos el div de los  msg
                        $("#msg").get(0).className=""
                        $("#msg").get(0).innerText=""
                        // cerramos modal
                        modal.modal('hide');
                        // vaciamos el input si hay algo
                        $("#medio_imagen").parent().find('.dropify-clear').trigger('click');
                        
                        $(".medio_imagen .dropify-wrapper").css("display", "none");
                        $(".medio_imagen .grab_foto").css("display", "flex");
                        $(".medio_imagen .grab_foto").css("background-image", "url(" + canvasFoto.get(0).toDataURL() + ")");
                        $(".text-img-reemplazar span").css("visibility", "visible");
                        $("#borrarImg").css("display", "block");
                    })
                })
            })
        }

        //metodo que se ejecuta una vez hacemos el hide a la modal
        $("#modal-grabacion").on('hidden.bs.modal', function () {

            //eliminamos los btns para que no reaparezcan al recargar la modal
            let btnsModalList = document.querySelectorAll('button[name="btnsModal"]')

            btnsModalList.forEach(element => {
                element.remove();
            });
        });

        //metodos para eliminar archivos del input antes de enviar al servidor (icono borrar)
        $('#borrarImg').click((e) => {
            const inputImg = document.getElementById("medio_imagen");
            if(grabacion.imagen != null || inputImg.files.length > 0){
                if(confirm('¿Quieres borrar la imagen?')){
                    //console.log("grabacion.imagen",grabacion.imagen);
                    $("#medio_imagen").parent().find('.dropify-clear').trigger('click');
                    if(grabacion.imagen){
                        grabacion.imagen = null;
                        $(".medio_imagen .dropify-wrapper").css("display", "block");
                        $(".grab_foto").css("display", "none");
                        $(".text-img-reemplazar span").css("visibility", "hidden");
                    }
                }
            }
        })
        $('#borrarAudio').click((e) => {
            const inputAudio = document.getElementById("medio_audio");
            if(grabacion.audio != null || inputAudio.files.length > 0){
                if(confirm('¿Quieres borrar el audio?')){
                    $("#medio_audio").parent().find('.dropify-clear').trigger('click');
                    if(grabacion.audio){
                        grabacion.audio = null;
                        $(".medio_audio .dropify-wrapper").css("display", "block");
                        $(".grab_audio").css("display", "none");
                        $(".text-audio-reemplazar span").css("visibility", "hidden");
                    }
                }
            }
        })
        $('#borrarVideo').click((e) => {
            const inputVideo = document.getElementById("medio_video");
            if(grabacion.video != null || inputVideo.files.length > 0){
                if(confirm('¿Quieres borrar el video?')){
                    $("#medio_video").parent().find('.dropify-clear').trigger('click');
                    if(grabacion.video){
                        grabacion.video = null;
                        $(".medio_video .dropify-wrapper").css("display", "block");
                        $(".grab_video").css("display", "none");
                        $(".text-video-reemplazar span").css("visibility", "hidden");
                    }
                }
            }
        })

        //BTN GUARDAR DEL FORMULARIO DE ACEPCION
        $("#btnGuardarForm").click((e) => {

            e.preventDefault()
            
            let formElement = document.getElementById("formularioAcepcion");
            let form = new FormData(formElement);

            //editamos el valor de la variable medio del formulario
            if (grabacion != null && (grabacion.imagen || grabacion.audio || grabacion.video)) {
                console.log('grabacion ok');
                if(grabacion.imagen){
                    form.set("medio_" + grabacion.imagen.name, new File([ grabacion.imagen.content], "grabación_" + grabacion.imagen.name));
                }
                if(grabacion.audio){
                    form.set("medio_" + grabacion.audio.name, new File([grabacion.audio.content], "grabacion_" + grabacion.audio.name));
                }
                if(grabacion.video){
                    form.set("medio_" + grabacion.video.name, new File([grabacion.video.content], "grabación_" + grabacion.video.name));
                }
                
                form.set('grabacion', true);
                const urlReq = formElement.action;
                console.log('form action', urlReq);

                //const urlReq = window.location.origin+"/medusa/apps/lexican/personal/entrada/acepcion/create";

                fetch(urlReq, {
                        method: "POST",
                        body: form
                        // headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
                    })

                    //todo el response que nos llega lo pasamos a json
                    .then(response => {
                        return response.json();
                    })
                    //obtenemos la ruta
                    .then(data => {
                        //console.log('data.ruta',data.ruta,'json',data);
                        // comprobamos errores
                        if (data.error) {
                            let htmlAlerta = '<div class="alert alert-danger"><ul>';
                            Object.values(data.error).forEach(element => {
                                htmlAlerta += '<h4><li>';
                                htmlAlerta += element;
                                htmlAlerta += '</li></h4>';
                            });
                            htmlAlerta += '</ul></div>';
                            $('#alertas').append(htmlAlerta);
                            document.body.scrollTop = 0; // For Safari
                            document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
                            // y redireccionamos al enviar los datos del formulario
                        } else {
                            window.location.replace(data.ruta);
                        }
                    });
            } else {
                //console.log('form action',urlReq);
                formElement.submit();
            }
        })
    
    
    </script>

@endsection {{--  fin section scripts --}}