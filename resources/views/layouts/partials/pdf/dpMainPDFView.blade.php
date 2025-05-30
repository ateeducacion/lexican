<html>
    <head>
        <title>Diccionario Personal</title>        
        @include('layouts.partials.pdf.css')            
    </head>
    <body>

        <!-- Cargamos la portada -->
        @include('layouts.partials.pdf.dpPortada')

         {{-- header fixed no va bien  --}}
        <!-- Cargamos el header -->
        @include('layouts.partials.pdf.dpHeader') 
        

        <!-- Cargamos el footer -->
        <!-- En este caso concreto no usaremos Footer porque sólo nos han pedido paginación. Lo comentamos. -->
        <!-- include('layouts.partials.pdf.dpFooter') -->

        <script type="text/php">
            // Fijamos las variables globales que van a controlar la paginación al principio del Body
            $GLOBALS['start_pages'] = array( );
            $GLOBALS['current_start_page'] = null;
            $GLOBALS['show_page_numbers'] = false;
        </script>


        <!-- --------------------------------------------------------------------- -->
        <!-- Aquí irá el contenido del PDF. El cuerpo del documento. Irá paginado. -->
        <!-- --------------------------------------------------------------------- -->

        <script type="text/php">
            // A partir de aquí necesitamos paginación. Por lo que inicializamos las variables globales que ya hemos definido.
            $GLOBALS['current_start_page'] = $pdf->get_page_number();
            $GLOBALS['start_pages'][$pdf->get_page_number()] = array(
            'show_page_numbers' => true,
            'page_count' => 1
            );
        </script>

        <section id="content">

                <!-- Cargamos el header -->
                {{-- el headder de la primera pagina no aparece bien asi que tengo que meterlo de nuevo de esta manera  --}}
                <div class="cabecera" style="position: relative;height: 0; top:-125px">
                    <div class="col-10">
                        <div>
                            <img src="{{ public_path() }}/imagenes/lexican_azul.png" style="width: 120px;" />
                        </div>
                        <div class="text-cab">
                            {{ mb_strtoupper(__('diccionario.diccionario')) }}: {{ mb_strtoupper($datos['titulo']) }}
                        </div>
                    </div>
            
                    <div class="col-2">
                        <img class="logo" src="{{ public_path() }}/imagenes/escudo_gobierno_negro.png" />
                    </div>
                </div>    

                <!-- Desde la página 1 hasta la penúltima del PDF -->
                <?php
                if (!empty($datos['contenido'])) {
                    foreach ($datos['contenido'] as $clave => $valor) {
                        ?>
                        <!-- Título de la entrada -->
                        <div class="cabecera-entrada" >
                            <span class="titulo-entrada" ><?php echo $clave; ?></span>
                            <div class="titulo-linea" ></div>
                        </div> 
                        <?php

                        foreach ($valor as $index => $acepcion) { ?>

                            <!-- Mostramos las acepciones -->
                            <div class="card-wrapper">
                                {{-- Columna Definición Acepción --}}
                                <div class="col-10 pdf-acepcion-texto">
                                    {{-- Texto de la Acepción --}}
                                    <p>
                                    {{-- @if( $datos['showHidden'] ) --}}
                                        {{ $index+1 }}. 
                                    {{-- @endif --}}
                                    
                                    {{ $acepcion['atributos'] }} 
                                    {{ $acepcion['definicion'] }} 
                                    </p>
                                    <p class="dc-campos">
                                    {{-- mas datos visible --}}
                                    @if ( $acepcion["frase_ejemplo"]  && $acepcion["frase_ejemplo"] != '')
                                    <div class="tematicas py-1" >{{ __('diccionario.ver_acepcion_frase_ejemplo') }}<span class="dc-frase-ejemplo"> {{ $acepcion["frase_ejemplo"] }}</span></div>
                                    @endif

                                    {{-- Frase ejemplo visible --}}
                                    @if ( $acepcion["ejemplo2"]  && $acepcion["ejemplo2"] != '')
                                    <br>
                                    <div class="tematicas py-1" >{{ __('diccionario.ver_acepcion_ejemplo2') }}<span class="dc-ejemplo2"> {{ $acepcion["ejemplo2"] }}</span></div>
                                    @endif


                                    {{-- lengua-idioma --}}
                                    @if ( $acepcion->dpIdioma && $acepcion->dpIdioma->descripcion && $acepcion->dpIdioma->descripcion != '' && $acepcion->idioma_palabra !='' )
                                    <br>
                                    <div class="tematicas py-1" >
                                        <span class="dc-idioma">{{  mb_convert_case($acepcion->dpIdioma->descripcion, MB_CASE_TITLE, 'UTF-8') }}:</span> <span class="dc-idioma-palabra">{{ $acepcion->idioma_palabra }}</span>
                                    </div>
                                    @endif
                    
                                    {{-- Tematicas Generales Visibles --}}
                                    @if( $acepcion->dpAcepcionTematicas && $acepcion->dpAcepcionTematicas->count()>0 ) 
                                    {{-- @if( array_key_exists("dpAcepcionTematicas",$acepcion) && $acepcion["dpAcepcionTematicas"] && count($acepcion["dpAcepcionTematicas"])>0 )  --}}
                                        <br>
                                        <div class="tematicas py-1" >@lang('diccionario.ver_acepcion_tematica'):
                                        @foreach( $acepcion['dpAcepcionTematicas'] as $key => $tematica)
                                        <span class="dc-tematica">{{ $tematica->descripcion }}{{ (!$loop->last)? ',':'' }}</span>
                                        @endforeach
                                        </div>
                                    @endif
                                    </p>
                                    
                                    {{-- <div class="acepcion-linea"></div>    --}}
                                </div>
                                <div class="col-2">                                    
                                    @if(!empty ($acepcion['imagen']))
                                        {{-- <img src="{{ $acepcion['imagen'] }}" style="height: 100%; width: 100%;" > --}}
                                        <img 
                                            src="{{ $acepcion['imagen'] }}" 
                                            style="width: 31mm;max-height: 6cm" 
                                        >
                                    @endif
                                </div>
                            </div>

                            <div class="card-wrapper"> 
                                <div class="col-10">
                                    <div class="acepcion-linea"></div>
                                </div>
                                <div class="col-2">                                    
                                </div>
                            </div>
                            
                        <?php
                        }
                        ?>

                        <script type="text/php">
                            // Graba el número total de páginas de cada sección.
                            $GLOBALS['start_pages'][$GLOBALS['current_start_page']]['page_count'] = $pdf->get_page_number() - $GLOBALS['current_start_page'] + 1;
                        </script>

                        <?php
                    }

                    ?> <div class="page-breaka"></div> <?php

                } else {
                    // Si no hay contenido que mostrar ponemos este mensaje:
                    echo "No hay entradas para este diccionario";
                    ?> <h3> No hay entradas para este diccionario </h3>

                    <div class="page-break"></div>
                    <script type="text/php">
                        // Graba el número total de páginas de cada sección.
                        $GLOBALS['start_pages'][$GLOBALS['current_start_page']]['page_count'] = $pdf->get_page_number() - $GLOBALS['current_start_page'] + 1;
                    </script>
                    <?php
                }
                ?>
        </section>
        <!-- ------------------------------------------------ -->
        <!-- Cargamos la contraportada. Última página del PDF -->
        <!-- ------------------------------------------------ -->

        <script type="text/php">
            // Inicializamos los parámetros para una nueva paginación. Pero como no nos interesa ponemos el show_page_numbers a FALSE.
            $GLOBALS['current_start_page'] = $pdf->get_page_number();
            $GLOBALS['start_pages'][$pdf->get_page_number()] = array(
              'show_page_numbers' => false,
              'page_count' => 1
            );
        </script>

        @include('layouts.partials.pdf.dpContraPortada')

        <script type="text/php">
            // Graba el número total de páginas hasta esta sección.
            $GLOBALS['start_pages'][$GLOBALS['current_start_page']]['page_count'] = $pdf->get_page_number() - $GLOBALS['current_start_page'] + 1;
        </script>
        
        <script type="text/php">
            // Este es el script que se encargará de mostrar la paginación sólo en las secciones donde la hayamos puesto visible.
            $pdf->page_script('
                if ($pdf) {
                    if (array_key_exists($PAGE_NUM, $GLOBALS["start_pages"])) {
                        $GLOBALS["current_start_page"] = $PAGE_NUM;
                        $GLOBALS["show_page_numbers"] = $GLOBALS["start_pages"][$GLOBALS["current_start_page"]]["show_page_numbers"];
                    }
                    if ($GLOBALS["show_page_numbers"]) {
                        $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
                        $pdf->text(300, 800, ($PAGE_NUM - $GLOBALS["current_start_page"] + 1), $font, 10);
                    }
                }
            ');
        </script>

    </body>
</html>