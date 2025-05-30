
@extends('diccionario/home')

@section('title', 'Diccionario de Aula')

@section('content')
{{-- Si no hay diccionarios --}}
<div class="alert alert-info {{ (isset($noExisteDicAula) && $noExisteDicAula) ? '' : 'd-none' }}">
    <ul style="width: 92%">
        @can( 'createDicAula', App\DicAula::class )
        <li>{{-- Docente --}}
            {{ __('diccionario.aula_diccionarios_cero_docente') }}
        </li>
        @else
        <li>
            {{-- Alunmado --}}
            {{ __('diccionario.aula_diccionarios_cero_alumno') }}            
        </li>
        @endcan
    </ul>
</div>

@parent

{{-- Menu seleccionar diccionario de aula activo --}}
@include('layouts.partials.components.select-DicAula')


@cannot( 'createDicAula', App\DicAula::class )
    {{-- no puedo createDicAula --}}
    @if( getDicAulaActivo() )
        {{-- Seleccion letras Abecedario --}}
        @include('layouts.partials.components.abecedario', [
            'route'=> 'aula.consulta.byInitial',
            'letraSel'=>$datos->letra ?? ''])
    
        {{-- Buscador --}}
        {{-- @include('layouts.partials.dpEntrada.dpEntradaBuscador') --}}
        @include('layouts.partials.components.buscador', ['aula'=>true] )
        
    
    
        {{-- Numero de entradas --}}
        @if(session('userData.dicAulaActivo'))
        @include('layouts.partials.nentradas', [ 'texto' => 'Ver todas las entradas de '. session('userData.dicAulaActivo')->titulo ])
        @else 
        @include('layouts.partials.nentradas')
        @endif
    
        @include('layouts.partials.components.actionbuttons-iconossvg')
    @else
        <div class="my-3 dc-centerdiv">
            &nbsp;
        </div>
        <div class="my-3 dc-centerdiv">
            &nbsp;
        </div>
        <div 
        class="my-3 dc-centerdiv text-center col-md-6" 
        style="color: var(--color4); font-size:18px" >
            {{ __('diccionario.aula_no_unido_diccionario') }}
        </div>

    @endif
@endcannot

@can( 'createDicAula', App\DicAula::class )

    {{-- Seleccion letras Abecedario --}}
    @include('layouts.partials.components.abecedario', [
        'route'=> 'aula.consulta.byInitial',
        'letraSel'=>$datos->letra ?? ''])

    {{-- Buscador --}}
    
    @include('layouts.partials.components.buscador', ['aula'=>true] )
    


    {{-- Numero de entradas --}}
    @if(session('userData.dicAulaActivo'))
        @include('layouts.partials.nentradas', [ 'texto' => 'Ver todas las entradas de '. session('userData.dicAulaActivo')->titulo ])
    @else 
        @include('layouts.partials.nentradas')
    @endif

    @include('layouts.partials.components.actionbuttons-iconossvg')
    {{-- can create dic aula --}}
    {{-- @can('canEditDic', $datos->diccionarioActivo) --}}

    {{-- acciones --}}
    <div class="row dc-btn-acciones">
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
                'disable' => !( property_exists($datos, 'dicAulaActivo') && $datos->dicAulaActivo )
            ])

            @if( property_exists($datos, 'dicAulaActivo') && $datos->dicAulaActivo )
            <div class="dc-actionbutton-menu dc-bocadillo horizontal d-none" >
                <div class="dc-bocadillo-bg horizontal d-flex" style="right: -27%">
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
                    <a href="{{ route('comentarios.enviar', [$datos->dicAulaActivo->id]) }}" 
                            class="dc-bocadillo-item dc-actionbutton-menu-btn-enlace">
                        <div class="d-flex flex-column dc-actionbutton-menu-btn">
                            <svg width="100%" height="54.545" viewBox="0 0 70 55">
                                <use xlink:href="#ico-bocadillo-comentar" />
                            </svg>
                            <span>@lang('diccionario.comentarios_generales')</span>
                        </div>
                    </a>                    
                    {{-- fin botones menu --}}
                </div>
                
            </div>
            @endif
        </div>

        {{-- Gestionar Diccionario de aula --}}
        <div class="col-md-4 text-center position-relative" id="gestion-dic">
            @include('layouts.partials.components.actionbutton', [
            // 'svginline'=>'ico-gestion-dic',
                'icon'=> 'gestionDicFB.svg',
                'text'=> __('diccionario.gestionar_aula'),
                'disable' => !( property_exists($datos, 'dicAulaActivo') && $datos->dicAulaActivo )
            ])
            @if( property_exists($datos, 'dicAulaActivo') && $datos->dicAulaActivo )
            <div class="dc-actionbutton-menu left dc-bocadillo horizontal d-none">
                <div class="dc-bocadillo-bg horizontal d-flex"  style="right: -13%">
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

                    {{-- Subir a Portafolio --}}
                    {{-- <a href="" class="dc-bocadillo-item dc-actionbutton-menu-btn-enlace">
                        <div class="d-flex flex-column dc-actionbutton-menu-btn">
                            <svg width="100%" height="54.545" viewBox="0 0 70 55">
                                <use xlink:href="#ico-bocadillo-subirportfolio" />
                            </svg>
                            <span>@lang('diccionario.subir_portfolio')</span>
                        </div>
                    </a> --}}

                    {{-- fin botones menu --}}
                </div>
            </div>
            @endif
        </div>

    </div>


@endcan

@can( 'createDicAula', App\DicAula::class )
    @if(isset($datos->dicAulaActivo))
    <!-- Modal -->
        @include('layouts.partials.components.modal-formulario-invitar-docente', ['diccionarioAula'=>$datos->dicAulaActivo ])
    <!-- End Modal -->
    @endif
@endcan


@endsection