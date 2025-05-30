@extends('diccionario/home')

@section('title', __('diccionario.title_envio_seleccionaraulas'))


@section('content')
    @parent
    {{-- Carga migas de pan y + en parent --}}


    <div class="row">
        <div class="col-md-12">
            <h5 class="card-title">
                {{-- Mensaje: ENVÍO DE DICCIONARIO PERSONAL A DICCIONARIO DE AULA  --}}
                <strong>@lang('diccionario.envio_seleccionaraulas')</strong>
            </h5>
        </div>
    </div>


    <div class="row">
        <div class="col-md-8 col-lg-9 col-xl-10">
            <div class="card dc-panel-gris text-center"  >
                <div class="card-body">
                    <form class="my-form" method="POST" action="{{ route('diccionario.send', ['diccionario_id'=> $datos->diccionario->id]) }}" enctype="multipart/form-data">

                        @csrf

                        <input type="hidden" id="listaDiccionariosAula" name="listaDiccionariosAula" value="" />

                        <div class="form-row">
                            <br />
                        </div>

                        {{-- Selecciona los diccionarios --}}
                        <div class="form-row">
                            <div class="form-group col-md-12 comentario-entrada-letra">
                                @lang('diccionario.envio_seleccionadiccionarios_diccionario')<br />
                            </div>
                        </div>

                        <div class="form-row">
                            {{-- Listado de diccionarios de aula a los que el user está unido --}}

                            <div class="form-group col-md-12">
                                <ul style="padding-left: 0px;">
                                    @include('layouts/partials/components/listaDicAula-customRadio', [
                                        'listaDiccionariosAula' => $datos->listaDiccionariosAula,
                                        'enviosDehabilitadosGris' => true,
                                        'ocultarNoVisible' => $datos->ocultarDiccionarioNoVisible,
                                    ])
                                </ul>
                            </div>
                        </div>



                        {{-- <div class="row">
                            <div class="offset-md-1 col-md-11 text-left comentario-entrada-letra">
                                @lang('diccionario.envio_recuerda')<br />
                            </div>
                        </div> --}}

                        <div class="form-row">
                            {{-- Nube de entradas del diccionario --}}
                            <div class="offset-md-1 form-group col-md-10" id="listadoEntradas">
                                <div class="card text-center">
                                    <div class="card-body" >
                                        @foreach($datos->diccionario->dpEntradas('ASC')->get() as $entrada)
                                            @if($entrada->estado == config('ctes.estados_entrada.visible'))
                                                <span data-dpid="{{ $entrada->id }}" class="dc-round-btn dc-palabra dc-palabra-visible">{!! $entrada->entrada; !!}<span class="badge top-badge rounded-pill"></span></span>
                                            @else
                                                <span data-dpid="{{ $entrada->id }}" data-oculta="true" class="dc-round-btn dc-palabra dc-palabra-oculta">{!! $entrada->entrada; !!}<span class="badge top-badge rounded-pill oculta"></span></span>
                                            @endif
                                        @endforeach
                                    </div>
                                    <span id="palabras-total">{{ __('diccionario.enviarDiccionario_total_palabras') }}<span class='count'></span></span>
                                </div>
                            </div>
                        </div>

                        {{-- info entrada seleccionada --}}
                        <div class="form-row" id="infoEntrada" >
                            <div class="offset-md-1 form-group col-md-10" >
                                <div class="info-body text-left">
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            {{-- Mensaje de ultimo envio si lo hubiese --}}
                            {{-- por diccionario --}}
                            <div class="offset-md-1 col-md-11 text-left ">

                                @foreach($datos->listaDiccionariosAula->sortBy('titulo') as $diccionarioAula)
                                    <p class="dc-info-ultimo-envio d-none" id="ultimoEnvio{{ $diccionarioAula->id }}">
                                        {{-- Muestra algo como Ultimo envio al diccionario "nombre": fecha --}}
                                        {{ mb_ucfirst(__('diccionario.envio_ultimo_envio_diccionario')) }}
                                        "{{ $diccionarioAula->titulo }}" :
                                    @if ( $diccionarioAula->envios->where('dic_personal_id',$datos->diccionario->id )
                                            ->count()>0 )
                                        {{
                                            $diccionarioAula->envios
                                                ->where('dic_personal_id',$datos->diccionario->id )
                                                ->last()->updated_at
                                                ->format('d/m/Y')
                                        }}.
                                    @else
                                        {{ __('diccionario.envio_diccionario_no_enviado') }}.
                                    @endif
                                    </p>

                                @endforeach
                            </div>

                        </div>

                        <div id="leyenda">
                            <div class="text-left offset-md-1 col-md-10">
                                <span >{{ __('diccionario.enviarDiccionario_leyenda') }}: </span>
                            </div>
                            <div>
                                <span class="dc-round-btn dc-palabra dc-palabra-visible">
                                    {{ __('diccionario.enviarDiccionario_marcada_para_enviar') }}
                                    <span class="count"></span>
                                </span>

                                <span class="dc-round-btn dc-palabra dc-palabra-enviada" data-toggle="tooltip" data-placement="top" title="{{ __('diccionario.tooltip_palabra-enviada') }}" >
                                    {{ __('diccionario.dpDiccionairoEnviarFormulario_enviadasConAnterioridad') }} <span class="count"></span>
                                    <span class="badge top-badge rounded-pill"></span>
                                </span>

                                <span class="dc-round-btn dc-palabra dc-palabra-oculta" data-toggle="tooltip" data-placement="top" title="{{ __('diccionario.tooltip_palabra-oculta') }}" >
                                    {{ __('diccionario.dpDiccionairoEnviarFormulario_ocultas') }}
                                    <span class="count"></span> <span class="badge top-badge rounded-pill"></span>
                                </span>

                                <span class="dc-round-btn dc-palabra dc-palabra-error" data-toggle="tooltip" data-placement="top" title="{{ __('diccionario.tooltip_palabra-error') }}" >
                                    {{ __('diccionario.dpDiccionairoEnviarFormulario_conErrores') }}
                                    <span class="badge top-badge rounded-pill"></span> <span class="count"></span>
                                </span>

                                {{-- <p>
                                ids Entradas seleccionadas: <span id="entradasSelecionadas"> </span><br>
                                ids Entradas ocultas: <span id="entradasOcultas"> </span>
                                </p> --}}
                            </div>

                            <input type="hidden" name="entradasSelecionadas" value="">
                            <input type="hidden" name="entradasOcultas" value="">


                        </div>


                        {{-- Botones del formulario --}}
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">@lang('diccionario.acepcion_enviar')</button>
                            <a href="{{ route('diccionariopersonal.get') }}" class="btn btn-danger">@lang('diccionario.acepcion_cancelar')</a>
                        </div>

                    </form>


                    <div class="form-row">
                        <br />
                    </div>

                </div>
            </div>
        </div>

        {{-- Botones acciones --}}
        <div class="col-md-4 col-lg-3 col-xl-2">
            @include('layouts.partials.components.actionbuttonPersonal')
        </div>
    </div>

    {{-- iconos ocultar entradas --}}
    <div id="spectacle-case" >
        @foreach($datos->diccionario->dpEntradas('ASC')->get() as $entrada)
        <div class="dpid-{{ $entrada->id }} ojo-oculto ">
            @include('layouts.partials.components.ajaxmodal-AceptarCancelar', [
                'id' => 'uid_'.uniqid(),
                'img' => asset('/imagenes/ico-ojo-rojo.svg'),
                'modal_width' => '700px',
                'tooltip' => __('diccionario.modal_entrada_mostrar_tooltip'),
                'titulo' => __('diccionario.modal_entrada_mostrar_titulo'),
                'mensaje' => __('diccionario.modal_entrada_mostrar', ['entrada' => $entrada->entrada]),
                'action' => route('entrada.ocultar', [$entrada->dic_personal_id, $entrada->id]),
                ])
        </div>
        <div class="dpid-{{ $entrada->id }} ojo-visible">
            @include('layouts.partials.components.ajaxmodal-AceptarCancelar', [
                'id' => 'uid_'.uniqid(),
                'img' => asset('/imagenes/ico-p-ojo.svg'),
                'modal_width' => '700px',
                'tooltip' => __('diccionario.modal_entrada_ocultar_tooltip'),
                'titulo' => __('diccionario.modal_entrada_ocultar_titulo'),
                'mensaje' => __('diccionario.modal_entrada_ocultar', ['entrada' => $entrada->entrada]),
                'action' => route('entrada.ocultar', [$entrada->dic_personal_id, $entrada->id]),
                ])
        </div>
        @endforeach
    </div>

    @endsection

    @section('scripts')
    <script>

        // token CSRF
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        var entradasSelecionadas = [];
        var entradasOcultas = [];
        var entradas;
        var dicSelAnterior;
        // Maneja el cambio de diccionario
        // Función que recorre los checkboxes y si está marcados concatena sus id separándolo por comas y los guarda en el hidden listaDiccionariosAula para que luego se mande esto por POST
        function handleChange(checkbox) {
            // console.log('handleChange', checkbox.value );
            // deseleciona todos los checkbox
            let listaCheckboxes = document.getElementsByName('checkbox');
            listaCheckboxes.forEach(chkb => {
                if ( chkb !== checkbox ){
                    // si on es el que se acab de plusar
                    chkb.checked = false;
                }

            });
            // seleciona el que se acaba de pulsar
            checkbox.checked = true;

            let listaDiccionariosAula = document.getElementById('listaDiccionariosAula');
            // console.log( 'checkbox.id', checkbox.id, checkbox.id.substr(4) );
            listaDiccionariosAula.value = checkbox.id.substr(4);
            // console.log('listaDiccionariosAula.value',listaDiccionariosAula.value);

            // solo cargamos los camibos si es un diccionario distinto al actualmene selecionado
            // para evitar borrar los cambios y los marcados para enviar accidentalmente
            if (dicSelAnterior !== listaDiccionariosAula.value){
                getInfoEntradasDA(listaDiccionariosAula.value);
                dicSelAnterior = listaDiccionariosAula.value;
            }
        }

        // Obtener las entradas del diccionario de aula sellecionado
        function getInfoEntradasDA( diccionario ) {
            var tag = 'getInfoEntradasDA';
            // console.log( tag, 'called with: diccionario:', diccionario  );
            // /aula/{id}/entradas -> entradasAjax

            // ocultamos la infoEntrada si estaba activo del dic anterior
            $('#infoEntrada').hide();
            $('#palabras-total').show();

            // borramos las entradas enviados de otro diccionario:
            $('#listadoEntradas').find('.dc-palabra').removeClass('dc-palabra-enviada');
            $('#listadoEntradas').find('.dc-palabra').removeClass('dc-palabra-error');
            $('#listadoEntradas').find('.dc-palabra').data('infoEntrada','');

            // Obtener las entradas enviadas anteriormente como un json con informacion de su estado
            var url = '../../aula/'+diccionario+'/entradas.json';
            // console.log(tag, 'url entradas json: ',url);
            $.get( url )
                .done( (data) => {
                    console.log( 'done! data: ', data );

                    data['entradas'].forEach( (entrada) => {
                        var entradaEl = $('#listadoEntradas').find('[data-dpid="'+entrada.dp_entrada_id+'"]');
                        // si esta aqui en esta lista es por que ya se envio
                        // si esta ocula se queda como oculta
                        if ( ! entradaEl.data('oculta') ) {
                            entradaEl.addClass('dc-palabra-enviada');
                            entradaEl.removeClass('dc-palabra-visible');
                        }
                        entradaEl.data('enviada',true);

                        // Guardamos datos en la misma entrada
                        $(entradaEl).data('infoEntrada',entrada);
                    });

                    // Ahora selecionamos las palabras enviables:
                    selPalabrasNuevDic(diccionario);

                })
                .fail( (data) => {
                    console.log( 'fail :( \n\n data: ', data );
                });

        }

        // se ejecuta al cambiar de diccionario
        // Selecciona automaticamente las entradas que se van a enviar por defecto
        function selPalabrasNuevDic(diccionario) {
            // en un primer paso las entradas selecionadas son todas las que no se han envidao
            // luego quitaremos las que dan errores
            entradasSelecionadas = [];
            entradasOcultas = [];
            updateEntradasSelecionadas();
            // console.log('entradasSelecionadas',entradasSelecionadas);
            entradas = $('#listadoEntradas .dc-palabra');
            entradas.each( function(i,entrada) {
                if( !($(entrada).hasClass('dc-palabra-enviada')) && !($(entrada).data('oculta')) ) {
                    // console.log('palabra no envida',entrada.dataset.dpid, entrada.innerHTML );
                    entradasSelecionadas.push(entrada.dataset.dpid);
                    $(entrada).addClass('dc-palabra-visible');
                }
                if ( $(entrada).hasClass('dc-palabra-oculta') ){
                    // console.log('agregra a palabraos ocultas', entrada.dataset.dpid );
                    entradasOcultas.push(entrada.dataset.dpid);
                }
                // borramos las marcas de datos de errores
                $(entrada).removeData('errores');
                $(entrada).removeData('acepciones-error');
            });
            updateEntradasSelecionadas();
            // console.log('entradasSelecionadas, todas menos ocultas y enviadas:', entradasSelecionadas );

            const entradasComprobar = [];
            entradas.each( function(i,v) {
                // if(  !($(v).hasClass('dc-palabra-oculta')) ) {
                    entradasComprobar.push(v.dataset.dpid);
                // }
            });

            // Comprobar si las entradas no tiene errores y se pueden enviar:
            var urlComprobarEnviable = '../../aula/'+diccionario+'/comprobarEntradas';
            var data = {
                'entradas': entradasComprobar
            }
            // console.log( urlComprobarEnviable, data);
            $.post( urlComprobarEnviable, data )
                .done((data) => {
                    // console.log( 'okey data: ', data );
                    var entradasErrores = [];
                    entradasComprobar.forEach( entradaId => {
                        // console.log('revisando entrada:' , entradaId);
                        if ( data[entradaId] !== 'ok' ) {
                            entradasErrores.push( entradaId );

                            // y le agregamaos la clase que marca que tiene errores
                            var entradaEl = $('#listadoEntradas').find('[data-dpid="'+entradaId+'"]');
                            entradaEl.data('errores', true);
                            if ( !entradaEl.hasClass('dc-palabra-oculta')) {
                                entradaEl.addClass('dc-palabra-error');
                                entradaEl.removeClass('dc-palabra-visible');
                            }
                            // guarda los errores en la entrada
                            accepcionesErrores = data[entradaId];
                            // console.log('accepcionesErrores',accepcionesErrores);
                            entradaEl.data('acepciones-error',accepcionesErrores );
                        }
                    });
                    // hay que hacerlo separado por que si no es splice rompe el array que estamos recorriendo en el foreach
                    entradasErrores.forEach( (errorId) => {
                        // hay algun error quitamos esta palabraa de entradas sellecionadas
                        if ( entradasSelecionadas.indexOf(errorId) !== -1 ){
                            entradasSelecionadas.splice( entradasSelecionadas.indexOf(errorId),1 );
                        }
                    });
                    updateEntradasSelecionadas();
                })
                .fail((data) =>{
                    console.log( 'fail :( \n\n data: ', data );
                });
        }

        function updateEntradasSelecionadas(){
            // console.log('updateEntradasSelecionadas called', 'seleccionadas',entradasSelecionadas, 'ocultas', entradasOcultas);
            strEntradasSel = entradasSelecionadas.join(', ');
            strEntradasOcultas = entradasOcultas.join(', ');
            // console.log('str entradas sel: ', strEntradasSel,'ocultas', strEntradasOcultas );
            $('#listadoEntradas').show();
            $('#leyenda').show();

            $('#entradasSelecionadas').html( strEntradasSel );
            $('#entradasOcultas').html( strEntradasOcultas);
            $('input[name="entradasSelecionadas"]').val(strEntradasSel);
            $('input[name="entradasOcultas"]').val(strEntradasOcultas);


            n_palabrasSelecionadas = $('#listadoEntradas .dc-palabra-visible').length;
            // console.log('n_palabrasSelecionadas:' , n_palabrasSelecionadas);
            if ( n_palabrasSelecionadas == 0 ){
                console.log('n_palabrasSelecionadas:' , n_palabrasSelecionadas, '== 0');
                $('.btn.btn-primary').prop('disabled', true );
            } else {
                $('.btn.btn-primary').prop('disabled', false);
            }

            n_palabrasOcultas = $('#listadoEntradas .dc-palabra-oculta').length;
            n_palabrasError = $('#listadoEntradas .dc-palabra-error').length;
            n_palabrasOcultaEror = $('#listadoEntradas .dc-palabra-error.dc-palabra-oculta').length;
            n_palabrasNoSelecionable = n_palabrasOcultas + n_palabrasError - n_palabrasOcultaEror;
            n_palabrasTotal = $('#listadoEntradas .dc-palabra').length;
            n_palabrasNoSelcionadas = n_palabrasTotal - n_palabrasSelecionadas;

            $('#palabras-selecionadas .count').html( ': ' +  n_palabrasSelecionadas );
            $('#palabras-no-selecionadas .count').html( ': ' +  (n_palabrasNoSelcionadas - n_palabrasNoSelecionable) );
            $('#palabras-no-selecionable .count').html( ': ' +  n_palabrasNoSelecionable );

            $('#palabras-total .count').html( ': ' +  n_palabrasTotal );
            $('#leyenda .dc-palabra-visible .count').html(  ': ' + n_palabrasSelecionadas  );
            $('#leyenda .dc-palabra-oculta .count').html(  ': ' + $('#listadoEntradas .dc-palabra-oculta').length  );
            $('#leyenda .dc-palabra-enviada .count').html( ': ' + $('#listadoEntradas .dc-palabra-enviada').length  );
            $('#leyenda .dc-palabra-error .count').html(   ': ' + $('#listadoEntradas .dc-palabra-error').length  );

            // reactivar tooltips
            $('[data-toggle="tooltip"]').tooltip();
        }

        // Imprime el campo de entradaInfo solo si existe , para evitar errores
        // si no existe o hay algun error devuelve una cadena vacia
        // ej:
        //  con infoJson{ dp_envio: 4 }
        // printInfo(infoJson.dp_envio.estado, 'el id es ##')
        // imprimiria el id es 4
        function printInfo(campo, texto='##') {
            if (typeof campo !== 'undefined'){
                // console.log(texto, campo);
                texto = texto.replace("##", campo);
                // console.log(texto, campo);
                return texto;
            }
            return '';
        }


        // Si sólo hay un diccionario verde lo marca
        $(function() {
            if ($('[id^=chk_]').length == 1) {
                $('[id^=chk_]').prop('checked', true);
                handleChange($('[id^=chk_]')[0])
            }

            $('#spectacle-case .ojo-oculto>a').addClass('icon');
            $('#spectacle-case .ojo-visible>a').addClass('icon');

            // marcar como enviada o no:
            const cambiarEstadoEnvio = (ev) => {
                // console.log ('click en ', ev.target );
                dpid = ev.target.dataset.dpid;
                // console.log ('click en ', dpid);
                //quitar o agregar de entradasSelecionadas
                posId = entradasSelecionadas.indexOf(dpid);
                entradaEl = $('#listadoEntradas').find('[data-dpid="'+dpid+'"]');

                if ( posId != -1 ) {
                    // console.log('esta selecionado para enviar');
                    entradasSelecionadas.splice( posId, 1 );
                    // console.log('quitado de entradasSelecionadas', entradasSelecionadas);
                    entradaEl.removeClass('dc-palabra-visible');
                    if ( !entradaEl.hasClass('dc-palabra-enviada') ){
                        entradaEl.addClass('dc-palabra-oculta');
                        entradasOcultas.push( dpid );
                        // console.log('marcada para ocultar:', dpid );
                        updateEntradasSelecionadas();
                    }

                } else {
                    // No esta selecionado para enviar

                    // console.log('estado: no se va a enviar, intentnando cambiar');
                    // si no esta oculta o tiene errores se marca para enviar
                    if ( !(entradaEl.hasClass('dc-palabra-error')) && !entradaEl.data('errores')) {
                        // console.log('no es una palabra oculta por lo que se seleciona');
                        entradasSelecionadas.push( dpid );
                        // console.log('agregado a entradasSelecionadas', entradasSelecionadas);
                        entradaEl.addClass('dc-palabra-visible');
                        entradaEl.removeClass('dc-palabra-oculta');
                        var posIdOculta = entradasOcultas.indexOf(dpid);
                        if ( posIdOculta != -1 ) {
                            entradasOcultas.splice( posIdOculta, 1 );
                        }
                    }
                    // no envio el cambio de estado de las que tienen errores
                    if (entradaEl.data('errores')){
                        entradaEl.addClass('dc-palabra-error');
                        entradaEl.removeClass('dc-palabra-oculta');
                    }
                }
                // Mostrar informacion de la entrada que acabamos de pulsar:
                var infoJson = entradaEl.data('infoEntrada');
                infoEntradaHtml = '<!-- infoEntrada dpDiccionarioEnviarFormulario -->';
                infoEntradaHtml += '<div class="dc-entrada" style="margin-top:0.8rem">';

                infoEntradaHtml += '<div class="dc-entrada-top"><div class="dc-entrada-titulo ml-0 flex-grow-1">'+entradaEl.html();
                infoEntradaHtml += '</div>';


                var editarEntrada = '<a href="./entrada/'+dpid+'" >';
                editarEntrada += '<img  data-toggle="tooltip" data-placement="top" src="{{ asset('/imagenes/ico-edit.svg') }}" title="{{ __('diccionario.icono_editar') }}">';
                editarEntrada +='</a>';

                var iconoEnviar = '<img style="width:36px;" data-dpid="'+dpid+'" class="cursor-pointer info-enviar icon" data-toggle="tooltip" data-placement="top" src="../../../imagenes/ico-enviar.svg" title="Marcarda para enviar">';
                var iconoNoEnviar = '<img style="width:36px;" data-dpid="'+dpid+'" data-toggle="tooltip" class="info-enviar icon" data-placement="top" src="../../../imagenes/ico-enviar-gris.svg" title="No se va enviar">';

                var botones = '';
                var cuerpo = '';


                if ( entradaEl.hasClass('dc-palabra-enviada') ){
                    if ( infoJson !== '') {
                        // numero de envios
                        if(infoJson.count>1){
                            cuerpo += '<span class="envio_veces">'+
                                printInfo(infoJson.count, "{{ __('diccionario.enviado_veces') }}" ) +
                                '. </span>';
                        }
                        if(infoJson.count==1){
                            cuerpo += '<span class="envio_veces">{{ __('diccionario.enviado_una') }}. </span>';
                        }
                        cuerpo += ' ';

                        // ultimo envio
                        var fecha = infoJson.info.created_at;
                        var t = fecha.split(/[- :]/);
                        var d = new Date(Date.UTC(t[0], t[1]-1, t[2], t[3], t[4], t[5]));
                        // console.log("date", d);
                        // var dateString = d.getDay() + '/' + d.getMonth() + '/' + d.getFullYear();
                        var dateString = d.toLocaleDateString();
                        cuerpo += '<span class="envio_fecha">'+
                            printInfo(dateString, "{{ __('diccionario.enviarDiccionario_ultimo_envio') }}" ) +
                            '</span>';
                    }
                } else {
                    cuerpo += '<span class="envio_veces">{{ __('diccionario.enviarDiccionario_nunca_enviada') }}</span>';
                }

                if ( entradaEl.hasClass('dc-palabra-error') ){
                    // console.log("hay palabras con errores ", entradaEl);
                    // infoEntradaHtml += 'Palabra con errores';
                    // infoEntradaHtml += '</p>';
                    // infoEntradaHtml += 'Errores:';

                    // Agregamos el boton de editar entrada para poder ir a solucionarlo rapdiamente
                    botones += editarEntrada;

                    // imprimimos los errores con fondo de alerta rojo
                    cuerpo += '<div class="alert alert-danger">';
                    // console.log('entradaEl.data(\'acepciones-error\').length', entradaEl.data('acepciones-error').length);
                    // console.log('entradaEl.data("errores")', entradaEl.data("errores"));


                        // solo deberia entrar si tiene acepciones con error
                    Object.keys(entradaEl.data('acepciones-error')).forEach((orden) => {
                        // console.log('orden', orden);
                        var error = entradaEl.data('acepciones-error')[orden];
                        // console.log('error', error);
                        // console.log('error.tipo', error.tipo);
                        
                        if ( error.tipo == 'FALTAN_CAMPOS' ){
                            cuerpo += '<div>Acepción '+orden+',';
                            cuerpo += "{{ __('diccionario.envio_error_faltan_datos')  }}";
                            cuerpo += entradaEl.data('acepciones-error')[1].html;
                            cuerpo += '</div>';

                            console.log('cuerpo eror faltan campos', cuerpo);
                        }

                        if ( error.tipo == 'FALTA_ACEPCION' ){
                            cuerpo += '<div>';
                            // cuerpo += "{{ __('diccionario.envio_error_faltan_acepciones')  }}";
                            cuerpo += entradaEl.data('acepciones-error')[orden].html;
                            cuerpo += '</div>';
                        }

                    })
                    cuerpo += '</div>';

                }

                infoEntradaHtml += '<div class="dc-entrada-botones-top dc-entrada-botones d-flex flex-grow-0" >';
                infoEntradaHtml += botones;
                infoEntradaHtml += '</div>';

                infoEntradaHtml += '</div>';//fin dc-entrada-top
                infoEntradaHtml += '<div>';// cuerpo
                infoEntradaHtml += cuerpo;
                infoEntradaHtml += '</div>';


                infoEntradaHtml += '</div>';

                $('#infoEntrada .info-body').html(infoEntradaHtml);
                $('#infoEntrada').show();

                // recragarmos tooltip para que funcione en los nuevos
                // $('[data-toggle="tooltip"]').tooltip({ trigger : 'hover' });
                // Actualizar visualizacion entradas selecionadas
                updateEntradasSelecionadas();
            };

            $('.diccionario-enviar').on('click', '.info-enviar', cambiarEstadoEnvio );
            $('#listadoEntradas .dc-palabra').click( cambiarEstadoEnvio );

        });
    </script>
@endsection
