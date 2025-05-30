
@include('layouts.partials.components.actionbuttons-iconossvg')


<div class="row dc-btn-acciones tn-acciones-vertical">
    <div class="col-md-12 text-center dc-actionbutton-container">
        @include('layouts.partials.components.actionbutton', [
        'icon'=>'addEntradaFB.svg',
        'text'=>__('diccionario.boton_anadir_entrada'),
        'href' => route('entrada.buscador')
        ])
    </div>
    <div class="col-md-12 text-center position-relative z-10 dc-actionbutton-container" id="envios-comentarios">
        @include('layouts.partials.components.actionbutton', [
        'icon'=>'envios-comentariosFB.svg',
        'text'=>__('diccionario.boton_envios_comentarios'),
        ])

        {{-- <div style="margin: -329px 0px 0px 150px;" class="dc-actionbutton-menu d-none dc-bocadillo horizontal"> --}}
        <div class="dc-actionbutton-menu left dc-bocadillo horizontal d-none" >
            <div class="dc-bocadillo-bg horizontal d-flex" style="right: -12%">
                <div class="dc-bocadillo-horizontal-start"> </div>
                <div class="dc-bocadillo-horizontal-middle"></div>
                <div class="dc-bocadillo-horizontal-end"></div>
            </div>
            <div class="d-flex dc-bocadillo-contenido">
            {{-- <div class="dc-actionbutton-menu-bg d-none"></div> --}}
            {{-- <div class="d-flex"> --}}

                {{-- Unirse a diccionario --}}

                @include('layouts.partials.components.botones.diccionario.unirse', [
                'id' => 'uid'.uniqid(),
                'bocadillo' => 'ico-bocadillo-libro',
                'caption' => __('diccionario.unirse_dic'),
                'modal_width' => '1000px',
                ])

                {{-- Enviar diccionario --}}

                @if( !$datos->diccionario->dpEntradas()->exists() )
                    {{-- No hay entradas en el diccionario personal --}}
                    @include('layouts.partials.components.modal-simple', [
                    'id' => 'uid_'.uniqid(),
                    'bocadillo' => 'ico-bocadillo-enviar',
                    'caption' => __('diccionario.enviar_entradas'),
                    'modal_width' => '700px',
                    'tooltip' => __('diccionario.modal_enviar_diccionario_tooltip'),
                    'title' => __('diccionario.modal_enviar_diccionario_titulo'),
                    'body' => __('diccionario.modal_enviar_diccionario_noentradas'),
                    'boton'=> __('diccionario.boton_aceptar'),
                    ])
                @else
                    {{-- Si el usuario no está unido a ningún diccionario, abrimos la ventana MODAL de unirse a un diccionario --}}
                    {{-- Si sólo tiene un diccionario conectado, abrimos la ventana de confirmación MODAL de enviar a ese diccionario --}}
                    {{-- Si está conectado a varios diccionarios, abrimos la ventana NO MODAL para elegir diccionarios --}}
                    @include('layouts.partials.components.botones.diccionario.enviar', [
                    'id' => 'uid'.uniqid(),
                    'bocadillo' => 'ico-bocadillo-enviar',
                    'caption' => __('diccionario.enviar_entradas'),
                    'modal_width' => '1000px',
                    ])
                @endif


                {{-- Ver comentarios --}}
                @include('layouts.partials.components.botones.diccionario.verComentarios', [
                    'id' => 'uid'.uniqid(),
                    'bocadillo' => 'ico-bocadillo-comentar',
                    'caption' => __('diccionario.ver_comentarios'),
                    'modal_width' => '1000px',
                    ])

            </div>
        </div>


    </div>
    <div class="col-md-12 text-center position-relative z-10 dc-actionbutton-container" id="gestion-dic">
        @include('layouts.partials.components.actionbutton', [
        'icon'=>'gestionDicFB.svg',
        'text'=>__('diccionario.boton_gestion_diccionario'),
        ])

        <!-- Bocadillo de gestión de diccionario -->
        {{-- <div style="margin: -330px 0px 0px -333px;" class="dc-actionbutton-menu left d-none "> --}}
        <div class="dc-actionbutton-menu left dc-bocadillo horizontal d-none" >
            {{-- <div style="margin: -0px 0px 0px -484px;" class="dc-actionbutton-menu-bg d-none"></div> --}}
            <div class="dc-bocadillo-bg horizontal d-flex" style="right: -19%">
                <div class="dc-bocadillo-horizontal-start"> </div>
                <div class="dc-bocadillo-horizontal-middle"></div>
                <div class="dc-bocadillo-horizontal-end"></div>
            </div>
            <div class="d-flex dc-bocadillo-contenido" style="0 -42px 0 -25px">
            {{-- <div class="d-flex"> --}}
                @if( property_exists($datos, 'diccionario') && $datos->diccionario )
                <a href="{{ route('diccionario.modificar.ver') }}" class="col dc-actionbutton-menu-btn-enlace">
                    <div class="d-flex flex-column dc-actionbutton-menu-btn">
                        <svg width="100%" height="54.545" viewBox="0 0 70 55">
                            <use xlink:href="#ico-bocadillo-moddic" />
                        </svg>
                        <span>@lang('diccionario.modificar_dic')</span>
                    </div>
                </a>
                @endif
                
                {{-- @if( property_exists($datos, 'diccionario') && $datos->diccionario )
                <a target="_blank" href="{{ route('pdfdp.download', $datos->diccionario->id) }}" 
                        class="col dc-actionbutton-menu-btn-enlace">
                    <div class="d-flex flex-column dc-actionbutton-menu-btn">
                        <svg width="100%" height="54.545" viewBox="0 0 70 55">
                            <use xlink:href="#ico-bocadillo-exportarpdf" />
                        </svg>
                        <span>@lang('diccionario.exportar_pdf')</span>
                    </div>
                </a>
                @endif --}}
                {{-- muestra las opciones antes de exportar --}}
                @if( property_exists($datos, 'diccionario') && $datos->diccionario )
                <a href="{{ route('pdfPersonal.options', $datos->diccionario->id) }}" 
                        class="col dc-actionbutton-menu-btn-enlace">
                    <div class="d-flex flex-column dc-actionbutton-menu-btn">
                        <svg width="100%" height="54.545" viewBox="0 0 70 55">
                            <use xlink:href="#ico-bocadillo-exportarpdf" />
                        </svg>
                        <span>@lang('diccionario.exportar_pdf')</span>
                    </div>
                </a>
                @endif

                {{-- Subir portfolio --}}
                {{-- <a href="" class="col dc-actionbutton-menu-btn-enlace">
                    <div class="d-flex flex-column dc-actionbutton-menu-btn">
                        <svg width="100%" height="54.545" viewBox="0 0 70 55">
                            <use xlink:href="#ico-bocadillo-subirportfolio" />
                        </svg>
                        <span>@lang('diccionario.subir_portfolio')</span>
                    </div>
                </a> --}}
            </div>
        </div>


    </div>
</div>