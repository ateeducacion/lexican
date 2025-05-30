@extends('diccionario/home')

@section('title', 'Docente - Editar Acepción ')


@section('css')
    <link rel="stylesheet" type="text/css" href="{{ asset('/css/dropify.min.css') }}">
    <style>
    .dropify-wrapper .dropify-message span.file-icon p{
        font-size: 0.9rem;
        color: rgb(119, 119, 119)
    }

    </style>
@append

@section('content')

@parent
{{-- @include('layouts.partials.alerts.alertas') --}}
    



@can( 'createDicAula', App\DicAula::class )

<div class="row">
    <div class="col-md-12">
        <h5 class="card-title">
            {{-- Mensaje: CREACIÓN DE LA ACEPCIÓN --}}
            <strong>{{ $datos->mensaje }}</strong>
            para "{{ $datos->acepcion->envioEntrada->entrada }}"
        </h5>
    </div>
</div>

<div class="dc-panel-gris card" > 
    <h5 class="card-title">

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
    
    <form class="my-form" method="POST" action={{ route('aula.acepcion.update', [ 'acepcion_id' => $datos->acepcion->id]) }} enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="envioEntrada_id" value="{{ $datos->acepcion->envioEntrada->id }}" />
        <input type="hidden" name="acepcion_id" value="{{ $datos->acepcion->id }}" />
        <input type="hidden" name="orden" value="{{ $datos->acepcion->orden }}" />

        @include('layouts/partials/dpAcepcion/acepcionFormFieldComun')

        {{-- línea de medios --}}
        <div class=" form-row">

            <div class="form-group col-md-4">
                <div class="card text-success border-success mb-4">
                    <div class="card-header" style="height: 35px; padding-left: 20px; padding-top: 3px; padding-bottom: 0px;  ">
                        <div class="row">
                            <div class="col-md-2">
                                @if(daMedio_getImagen($datos->acepcion))
                                    @include('layouts.partials.components.modal-AceptarCancelar', [
                                    'id' => 'uid_'.uniqid(),
                                    'img' => asset('/imagenes/ico-trash.svg'),
                                    'modal_width' => '700px',
                                    'tooltip' => __('diccionario.acepcion_borrar_imagen'),
                                    'titulo' => __('diccionario.confirm_titulo'),
                                    'mensaje' => __('diccionario.confirm_imagen_borrar'),
                                    'action' => route('aula.medio.delete', [
                                            $datos->acepcion->id, 
                                            daMedio_getImagen($datos->acepcion)->id 
                                        ]),
                                    'imgstyle' => "height: 100%; float: left; "
                                    ])
                                    @else
                                    <img id="borrarImg" class="card-img-top" data-toggle="tooltip" data-placement="top" data-original-title="Borrar Imagen"
                                        src="{{ URL::to('/') }}/imagenes/ico-trash.svg" alt="Borrar"> 
                                @endif
                            </div>
                            @if(daMedio_getImagen($datos->acepcion))
                                <div class="col-md-10 d-inline-block text-truncate" title="{{ daMedio_getImagen($datos->acepcion)->nombre }}">
                                    {{ daMedio_getImagen($datos->acepcion)->url_interna }}
                                </div>
                            @else
                                <div class="col-md-10 d-inline-block text-truncate">
                                    <br />
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body medio_imagen">
                        {{-- <h5 class="card-title"></h5>
                            <p class="card-text"><br /></p> --}}
                        <input type="file" id="medio_imagen" name="medio_imagen" accept="{{ getTiposMediosMimeType(config('ctes.tipos_medios.imagen')) }}" class="dropify" data-height="150" data-height="150" data-max-file-size-preview="{{ config('ctes.dropify.image_max-file-size-preview') }}" data-max-file-size="{{ config('ctes.dropify.image_data-max-file-size') }}" data-allowed-file-extensions="{{ config('ctes.extensiones_medios.imagen') }}" 

                                {{-- vista previa de la imagen subida --}}
                                @if(daMedio_getImagen($datos->acepcion))
                                data-default-file="{{ URL::to('/') .'/storage/' . config('ctes.path_medios.imagen') . '/' . getNombreFichero(daMedio_getImagen($datos->acepcion)->url_interna) . config('ctes.video_thumbnail_sufijo') . ".jpg" }}"
                                @endif                        
                        />
                    </div>
                </div>
            </div>

            <div class="form-group col-md-4">
                <div class="card text-success border-success mb-4">
                    <div class="card-header" style="height: 35px; padding-left: 20px; padding-top: 3px; padding-bottom: 0px;  ">
                        <div class="row">
                            <div class="col-md-2">
                                @if(daMedio_getAudio($datos->acepcion))
                                    @include('layouts.partials.components.modal-AceptarCancelar', [
                                    'id' => 'uid_'.uniqid(),
                                    'img' => asset('/imagenes/ico-trash.svg'),
                                    'modal_width' => '700px',
                                    'tooltip' => __('diccionario.acepcion_borrar_audio'),
                                    'titulo' => __('diccionario.confirm_titulo'),
                                    'mensaje' => __('diccionario.confirm_audio_borrar'),
                                    'action' => route('aula.medio.delete', [
                                            $datos->acepcion->id, 
                                            daMedio_getAudio($datos->acepcion)->id 
                                        ]),
                                    'imgstyle' => "height: 100%; float: left; "
                                    ])
                                    @else
                                    <img id="borrarAudio" class="card-img-top" data-toggle="tooltip" data-placement="top" data-original-title="Borrar Audio" 
                                    src="{{ URL::to('/') }}/imagenes/ico-trash.svg" alt="Borrar">
                                    {{-- @else
                                    <img class="card-img-top" src="{{ URL::to('/') }}/imagenes/icono_audio.svg" alt="audio" class="dc-ico-audio" > --}}
                                @endif
                            </div>
                            @if(daMedio_getAudio($datos->acepcion))
                                <div class="col-md-10 d-inline-block text-truncate" title="{{ daMedio_getAudio($datos->acepcion)->nombre }}">
                                    {{ daMedio_getAudio($datos->acepcion)->url_interna }}
                                </div>
                            @else
                                <div class="col-md-10 d-inline-block text-truncate">
                                    <br />
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body medio_audio">
                        {{-- <h5 class="card-title"></h5>
                                <p class="card-text"><br /></p> --}}
                        <input type="file" id="medio_audio" name="medio_audio" accept="{{ getTiposMediosMimeType(config('ctes.tipos_medios.audio')) }}" class="dropify" data-height="150" data-height="150" data-max-file-size="{{ config('ctes.dropify.audio_data-max-file-size') }}" data-allowed-file-extensions="{{ config('ctes.extensiones_medios.audio') }}" />
                    </div>
                </div>
            </div>

            <div class="form-group col-md-4">
                <div class="card text-success border-success mb-4">
                    <div class="card-header" style="height: 35px; padding-left: 20px; padding-top: 3px; padding-bottom: 0px;  ">
                        <div class="row">
                            <div class="col-md-2">
                                @if(daMedio_getVideo($datos->acepcion))
                                    @include('layouts.partials.components.modal-AceptarCancelar', [
                                    'id' => 'uid_'.uniqid(),
                                    'img' => asset('/imagenes/ico-trash.svg'),
                                    'modal_width' => '700px',
                                    'tooltip' => __('diccionario.acepcion_borrar_video'),
                                    'titulo' => __('diccionario.confirm_titulo'),
                                    'mensaje' => __('diccionario.confirm_video_borrar'),
                                    'action' => route('aula.medio.delete', [
                                            $datos->acepcion->id, 
                                            daMedio_getVideo($datos->acepcion)->id 
                                        ]),
                                    'imgstyle' => "height: 100%; float: left; "
                                    ])
                                    @else
                                    <img id="borrarVideo" class="card-img-top" data-toggle="tooltip" data-placement="top" data-original-title="Borrar Video" 
                                    src="{{ URL::to('/') }}/imagenes/ico-trash.svg" alt="Borrar">
                                    {{-- @else
                                    <img class="card-img-top" src="{{ URL::to('/') }}/imagenes/icono_video.svg" alt="VIDEO"> --}}
                                @endif
                            </div>
                            @if(daMedio_getVideo($datos->acepcion))
                                <div class="col-md-10 d-inline-block text-truncate" title="{{ daMedio_getVideo($datos->acepcion)->nombre }}">
                                    {{ daMedio_getVideo($datos->acepcion)->url_interna }}
                                </div>
                            @else
                                <div class="col-md-10 d-inline-block text-truncate">
                                    <br />
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body medio_video">
                        {{-- <h5 class="card-title"></h5>
                            <p class="card-text"><br />wwww</p> --}}
                        <input type="file" id="medio_video" name="medio_video" accept="{{ getTiposMediosMimeType(config('ctes.tipos_medios.video')) }}" class="dropify" data-height="150" data-max-file-size="{{ config('ctes.dropify.video_data-max-file-size') }}" data-allowed-file-extensions="{{ config('ctes.extensiones_medios.video') }}" />
                    </div>
                </div>
            </div>

        </div>

        {{-- Botones del formulario --}}
        <div class="text-center">
            <button type="submit" class="btn btn-primary">@lang('diccionario.acepcion_guardar')</button>

            {{-- cancelar redirije a listado dic de aula actual  --}}
                <a href="{{ route('aula.consulta.all') }}" class="btn btn-danger">
                    @lang('diccionario.acepcion_cancelar')</a>

                
                    {{-- <button type="submit" class="btn btn-primary">Prueba</button> --}}
        </div>
        
        
    </form>

@endcan

@endsection


@section('scripts')
    <script>
        jQuery(function($) {
            $('.dropify').dropify({
                messages: {
                    default: '{{ __("diccionario.acepcion_dropify_arrastra_click") }}' + '<br>'+
                            '{{ __("diccionario.acepcion_dropify_msg_max_size", ["size"=>config('ctes.dropify.image_max-file-size-preview')]).'B' }}', 
                    // 'Arrastra y suelta un archivo aquí o haz clic',
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
        });

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

        //metodos para eliminar archivos del input antes de enviar al servidor (icono borrar)
        $('#borrarImg').click((e) => {
            const inputImg = document.getElementById("medio_imagen");
            if(inputImg.files.length > 0){
                if(confirm('¿Quieres borrar la imagen?')){
                    $("#medio_imagen").parent().find('.dropify-clear').trigger('click');
                }
            }
        })
        $('#borrarAudio').click((e) => {
            const inputAudio = document.getElementById("medio_audio");
            if(inputAudio.files.length > 0){
                if(confirm('¿Quieres borrar el audio?')){
                    $("#medio_audio").parent().find('.dropify-clear').trigger('click');
                }
            }
        })
        $('#borrarVideo').click((e) => {
            const inputVideo = document.getElementById("medio_video");
            if(inputVideo.files.length > 0){
                if(confirm('¿Quieres borrar el vídeo?')){
                    $("#medio_video").parent().find('.dropify-clear').trigger('click');
                }
            }
        })

    </script>
@endsection {{--  fin section scripts --}}

