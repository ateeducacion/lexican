<div class="col-md-4 col-lg-3 col-xl-2">
    <div id="dc-btn-acciones" class="btn-acciones-vertical dc-btn-acciones ">
        
        {{-- Crear Diccionario de aula --}}
    <div class="col-md-4 text-center">
        @include('layouts.partials.components.actionbutton', [
        'icon'=>'libroAbierto.svg',
        'text'=>'Crear diccionario de aula',
        'href' => route('diccionarioaula.create')
        ])
    </div>

    {{-- Gestionar Entradas Enviadas --}}
    <div class="col-md-4 text-center position-relative" id="gestionar-entradas-enviadas">
        @include('layouts.partials.components.actionbutton', [
        'icon'=>'ico-gestionar-entradas.svg',
        'text'=>'Gestionar Entradas Enviadas',
        ])

        <div class="dc-actionbutton-menu left dc-bocadillo horizontal d-none">
            <div class="dc-bocadillo-bg horizontal d-flex">
                <div class="dc-bocadillo-horizontal-start"> </div>
                <div class="dc-bocadillo-horizontal-middle"></div>
                <div class="dc-bocadillo-horizontal-end"></div>
            </div>
            <div class="d-flex dc-bocadillo-contenido">
                {{-- botones menu --}}

                {{-- Ver Y publicar entradas --}}
                @if( property_exists($datos, 'dicAulaActivo') && $datos->dicAulaActivo )
                <a href="{{ route('diccionarioaula.entradas', $datos->dicAulaActivo->id ) }}" class="dc-bocadillo-item dc-actionbutton-menu-btn-enlace">
                    <div class="d-flex flex-column dc-actionbutton-menu-btn">
                        <svg width="100%" height="54.545" viewBox="0 0 70 55">
                            <use xlink:href="#ico-bocadillo-ver-publicar" />
                        </svg>
                        <span>@lang('diccionario.ver_publicar_entradas')</span>
                    </div>
                </a>
                @endif

                {{-- Comentarios generales --}}
                @if( property_exists($datos, 'dicAulaActivo') && $datos->dicAulaActivo )
                <a href="{{ route('comentarios.enviar', [$datos->dicAulaActivo->id]) }}" 
                        class="dc-bocadillo-item dc-actionbutton-menu-btn-enlace">
                    <div class="d-flex flex-column dc-actionbutton-menu-btn">
                        <svg width="100%" height="54.545" viewBox="0 0 70 55">
                            <use xlink:href="#ico-bocadillo-comentar" />
                        </svg>
                        <span>@lang('diccionario.comentarios_generales')</span>
                    </div>
                </a>
                @else 
                    {{-- unirse a diccionario para que no salga vacio --}}
                    <div style="margin-top: -25px;" id="dc-select-dicaula-unirsedic" class="text-center cursor-pointer">
                        @include('layouts.partials.components.botones.diccionario.unirse', [
                            'id' => 'uid'.uniqid(),
                            'bocadillo' => 'ico-bocadillo-libro',
                            'caption' => __('diccionario.unirse_dic'),
                            'modal_width' => '1000px',
                        ])
                    </div>
                    
                @endif
                {{-- fin botones menu --}}
            </div>
        </div>
    </div>

    {{-- Gestionar Diccionario de aula --}}
    <div class="col-md-4 text-center position-relative" id="gestion-dic">
        @include('layouts.partials.components.actionbutton', [
        // 'svginline'=>'ico-gestion-dic',
        'icon'=> 'gestionDicFB.svg',
        'text'=> __('diccionario.gestionar_aula'),
        ])

        <div class="dc-actionbutton-menu left dc-bocadillo horizontal d-none">
            <div class="dc-bocadillo-bg horizontal d-flex">
                <div class="dc-bocadillo-horizontal-start"> </div>
                <div class="dc-bocadillo-horizontal-middle"></div>
                <div class="dc-bocadillo-horizontal-end"></div>
            </div>
            <div class="d-flex dc-bocadillo-contenido">
                {{-- botones menu  --}}

                {{-- Invitar a docente --}}
                @if( property_exists($datos, 'dicAulaActivo') && $datos->dicAulaActivo )
                    <a href="#" 
                        class="dc-bocadillo-item dc-actionbutton-menu-btn-enlace"
                        data-toggle="modal" data-target="#docentesModal" 
                        >
                        <div class="d-flex flex-column dc-actionbutton-menu-btn">
                            <svg width="100%" height="54.545" viewBox="0 0 70 55">
                                <use xlink:href="#ico-bocadillo-invitar-docente" />
                            </svg>
                            <span>@lang('diccionario.invitar_a_docente')</span>
                        </div>
                    </a>
                @endif

                {{-- Modificar dic aula --}}
                
                @if( property_exists($datos, 'dicAulaActivo') && $datos->dicAulaActivo )
                <a href="{{ route('diccionarioaula.edit', $datos->dicAulaActivo->id ) }}" class="dc-bocadillo-item dc-actionbutton-menu-btn-enlace">
                    <div class="d-flex flex-column dc-actionbutton-menu-btn">
                        <svg width="100%" height="54.545" viewBox="0 0 70 55">
                            <use xlink:href="#ico-bocadillo-moddic" />
                        </svg>
                        <span>@lang('diccionario.modificar_dic_aula')</span>
                    </div>
                </a>
                @endif

                {{-- exportar a pdf --}}
                {{-- @if( property_exists($datos, 'dicAulaActivo') && $datos->dicAulaActivo )
                <a target="_blank" href="{{ route('pdfda.download', $datos->dicAulaActivo->id) }}" class="dc-bocadillo-item dc-actionbutton-menu-btn-enlace">
                    <div class="d-flex flex-column dc-actionbutton-menu-btn">
                        <svg width="100%" height="54.545" viewBox="0 0 70 55">
                            <use xlink:href="#ico-bocadillo-exportarpdf" />
                        </svg>
                        <span>@lang('diccionario.exportar_pdf')</span>
                    </div>
                </a>
                @endif --}}
                {{-- opciones exportar a pdf --}}
                @if( property_exists($datos, 'dicAulaActivo') && $datos->dicAulaActivo )
                <a href="{{ route('pdfAula.options', $datos->dicAulaActivo->id) }}" 
                        class="col dc-actionbutton-menu-btn-enlace">
                    <div class="d-flex flex-column dc-actionbutton-menu-btn">
                        <svg width="100%" height="54.545" viewBox="0 0 70 55">
                            <use xlink:href="#ico-bocadillo-exportarpdf" />
                        </svg>
                        <span>@lang('diccionario.exportar_pdf')</span>
                    </div>
                </a>
                @endif

                {{-- fin botones menu --}}
            </div>
        </div>
    </div>

    <!-- Success Panel -->
    @include('layouts.partials.alerts.dynamicPanels', [
        'type' => 'both',
        'titleSuccess' => 'Success',
        'bodySuccess' => 'Entrada publicada con éxito',
        'titleError' => 'Error',
        'bodyError' => 'Error al publicar la entrada'
    ])
    <!-- End Success Panel -->
    </div>
</div>

@can( 'createDicAula', App\DicAula::class )
    @if(isset($datos->dicAulaActivo))
    <!-- Modal -->
        @include('layouts.partials.components.modal-formulario-invitar-docente', ['diccionarioAula'=>$datos->dicAulaActivo ])
    <!-- End Modal -->
    @endif
@endcan