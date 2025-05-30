@extends('diccionario/home')

@section('title', 'Diccionario Personal')

@section('content')
@parent

{{-- Seleccion letras Abecedario --}}
@include( 'layouts.partials.components.abecedario', [
'route'=> 'personal.consulta.byInitial',
'letraSel'=>$datos->letra ?? ''])

{{-- Buscador --}}
{{-- <div class="dc-home-buscador dc-centerdiv"> --}}
    @include('layouts.partials.components.buscador')
{{-- </div> --}}


{{-- Numero de entradas --}}
@include('layouts.partials.nentradas', [ 'texto' => 'Ver todas las entradas de '. $datos->diccionario->titulo ])
{{-- Iconos svg --}}
@include('layouts.partials.components.actionbuttons-iconossvg')

{{-- 
Debug
<pre>

    Curso actual:
    getStrCursoByAnoIni(getAnoIniCurrentCursoEscolar()): {{ getStrCursoByAnoIni(getAnoIniCurrentCursoEscolar()) }}
    getAnoIniCursoEscolar(new Datetime()): {{ getAnoIniCursoEscolar(new Datetime()) }}

    curso por mes    
    getAnoIniCursoEscolar(new Datetime('2022-12-13')) : {{ getAnoIniCursoEscolar(date_create( '2022-12-13' )) }}

</pre>

    Probar fechas
    <table class="table-bordered table-striped text-center w-100" >
        <tr>
            <th> fecha str </th>
            <th> date->format('d/m/Y') </th>
            <th> getAnoIniCursoEscolar() </th>
            <th> getFormattedCursoEscolar() </th>
        <tr>

            <?php 
        // $datestr = '2022-'.$m.'-1';
        $date = new Datetime(); 
    ?>
    <tr><td colspan="4">HOY<td></tr>
    <tr>
        <td> new Datetime() </td>
        <td> {{ $date->format('d/m/Y')  }}</td>
        <td> {{ getAnoIniCursoEscolar( $date ) }}</td>
        <td> {{ getFormattedCursoEscolar( $date->getTimestamp() ) }}
    </tr>

    @foreach( range(1,12) as $m)
    <?php 
        $datestr = '2022-'.$m.'-1';
        $date = new Datetime($datestr); 
    ?>
    <tr><td colspan="4">{{ $m }}<td></tr>
    <tr>
        <td>{{ $datestr }}</td>
        <td> {{ $date->format('d/m/Y')  }}</td>
        <td> {{ getAnoIniCursoEscolar( $date ) }}</td>
        <td> {{ getFormattedCursoEscolar( $date->getTimestamp() ) }}
    </tr>
    <?php 
        $datestr = '2022-'.$m.'-'.($m+1);
        $date = new Datetime($datestr); 
    ?>
    <tr>
        <td> {{  $datestr }} </td>
        <td> {{ $date->format('d/m/Y')  }}</td>
        <td> {{ getAnoIniCursoEscolar( $date ) }}
        </td>
        <td> {{ getFormattedCursoEscolar( $date->getTimestamp() ) }}
    </tr>
    <tr>
    <?php ;
        $datestr = '2022-'.$m.'-30';
        $date = new Datetime($datestr); 
    ?>
        <td> {{ $datestr }} </td>
        <td> {{ $date->format('d/m/Y')  }}</td>
        <td> {{ getAnoIniCursoEscolar( $date ) }}</td>
        <td> {{ getFormattedCursoEscolar( $date->getTimestamp() ) }}
    </tr>
    
    @endforeach
    </table>
    <br>
<pre>
    DicAula:

    Diccionarios conectados activos: {{  daGetDiccionariosAulaByUserConectado( config('ctes.estado_envio_habilitado.activo') )->count()  }}
    Diccionarios conectados: {{ daGetDiccionariosAulaConectadosByPersonaId( getSessionPersona()['id'] )->count() }}
    DicActivo: {{ getDicAulaActivo()->titulo }}
    Curso dic : {{ getDicAulaActivo()->ano_ini_curso_escolar }}
    vigencia : {{ getDicAulaActivo()->vigencia }}
    COmentarios visibles? {{ getDicAulaActivo()->comentarios_visibles? 'si': 'no' }}
    ComentariosGenerales: {{ daGetComentariosGeneralesAlumno( getSessionPersona() )->count() }};
    ComentariosGenerales: daGetComentariosGeneralesAlumnoAula {{ daGetComentariosGeneralesAlumnoAula( getSessionPersona(), getDicAulaActivo() )->count() }};
    Comentarios Entradas : {{ daGetComentariosEntradasAlumno( getSessionPersona() )->count() }};


    DicPersonal:
    {{ json_encode($datos->diccionario,     JSON_PRETTY_PRINT)  }}
    Entradas: {{ $datos->diccionario->dpEntradas()->count() }}
    exists: {{ (true == $datos->diccionario->dpEntradas()->exists())? 'verdadero': 'falso' }}.

</pre>
--}}



{{-- acciones --}}
<div class="row dc-btn-acciones">

    <div class="col-md-4 text-center">
        @include('layouts.partials.components.actionbutton', [
        'icon'=>'addEntradaFB.svg',
        'text'=>__('diccionario.boton_anadir_entrada'),
        'href' => route('entrada.buscador')
        ])
    </div>

    <div class="col-md-4 text-center position-relative z-10" id="envios-comentarios">
        @include('layouts.partials.components.actionbutton', [
        'icon'=>'envios-comentariosFB.svg',
        'text'=>__('diccionario.boton_envios_comentarios'),
        ])

        <div class="dc-actionbutton-menu dc-bocadillo horizontal d-none" >
            <div class="dc-bocadillo-bg horizontal d-flex" style="right: -17%">
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

    <div class="col-md-4 text-center position-relative z-10" id="gestion-dic">
        @include('layouts.partials.components.actionbutton', [
        'icon'=>'gestionDicFB.svg',
        'text'=>__('diccionario.boton_gestion_diccionario'),
        ])

        <!-- Bocadillo de gestión de diccionario -->
        <div class="dc-actionbutton-menu left dc-bocadillo horizontal d-none" >
            {{-- <div style="margin: -0px 0px 0px -484px;" class="dc-actionbutton-menu-bg d-none"></div> --}}
            {{-- right: -12 con 3 elementos --}}
            <div class="dc-bocadillo-bg horizontal d-flex" style="right: -19%">
                <div class="dc-bocadillo-horizontal-start"> </div>
                <div class="dc-bocadillo-horizontal-middle"></div>
                <div class="dc-bocadillo-horizontal-end"></div>
            </div>
            <div class="d-flex dc-bocadillo-contenido">
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
                
                {{-- Boton exportar directamente --}}
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

                {{-- Subir portfolio: --}}
                {{-- 
                <a href="" class="col dc-actionbutton-menu-btn-enlace">
                    <div class="d-flex flex-column dc-actionbutton-menu-btn">
                        <svg width="100%" height="54.545" viewBox="0 0 70 55">
                            <use xlink:href="#ico-bocadillo-subirportfolio" />
                        </svg>
                        <span>@lang('diccionario.subir_portfolio')</span>
                    </div>
                </a> 
                --}}
            </div>
        </div>
    </div>

</div>

@endsection