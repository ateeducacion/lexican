@extends('layouts.app')

{{-- Cabecera con tabs --}}
@section('headertabs')
@include('layouts.partials.components.tabs')
@include('layouts.logging.route')
@endsection

@section('header-line-content')
{{-- {{ dd(getSessionRoles()) }} --}}
<div class="container text-right">
    <span id="header-line-content">

        {{-- : --}}
        <span class="dc-avatar">
            {{-- TODO: revisar url cuando se guarden/seleccionen los avatares --}}
            <a href="{{ route('diccionario.modificar.ver' ) }}" >
            @if( $sesionPersona = getSessionPersona() )
                @if( getSessionPersona()['avatar_URL'] )
                    <img
                    @if( getSessionRoles() )
                        data-toggle="tooltip"
                        data-placement="bottom"
                        title="{{ $sesionPersona['nombre'] }}
                            {{ $sesionPersona['apellidos'] }}
                            ({{ __( 'diccionario.'.getSessionRoles()[0]['display_name'] ) }})"
                    @endif

                    src="{{ URL::to('/') . '/storage/avatares/oficiales/' . getSessionPersona()['avatar_URL']  }}" >
                @else
                    <img src="{{ asset( '/imagenes/users/default.png' ) }}">
                @endif
            @endif
            </a>
        </span>
    </span>
</div>
@endsection

@section('nav')
<li class="nav-item">
@if ( isset($datos) && isset($datos->tabs[1]['active']) && $datos->tabs[1]['active'] === true  )
{{--  Aula: --}}
<button class="navbar-toggler submenu" type="button" data-toggle="collapse" data-target="#diccionario-aula-toggle" aria-controls="diccionario-aula-toggle" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
    <div>{{__('diccionario.diccionario_aula')}}</div>
</button>
<div class="collapse navbar-collapse submenu" id="diccionario-aula-toggle">
    <div class="select-dic dc-bocadillo-contenido">

        {{-- Unirse a  diccionario --}}
        <div id="dc-select-dicaula-unirsedic" class="tcursor-pointer row">
            @include('layouts.partials.components.botones.diccionario.unirse', [
                'id' => 'uid'.uniqid(),
                'bocadillo' => 'ico-bocadillo-plus',
                'caption' => __('diccionario.unirse_dic'),
                'modal_width' => '1000px',
            ])
        </div>
        {{-- Diccionarios a los que te has unido --}}
        @if (
            daGetDiccionariosAulaConectadosByPersonaId( auth()->user()->userPersona->id ) &&
            count( daGetDiccionariosAulaConectadosByPersonaId(auth()->user()->userPersona->id))>0
        )
            @foreach( daGetDiccionariosAulaConectadosByPersonaId(auth()->user()->userPersona->id) as $aula )

                @can( 'createDicAula', App\DicAula::class )
                    <div data-id="{{$aula->id}}" class="row dc-menu-btn dc-sel-aula
                        {{ $aula->visible_estudiante == config('ctes.estados.activo')? '':'dc-aula-no-visible' }}
                        {{ (getDicAulaActivo() && (getDicAulaActivo()->id == $aula->id))? 'activo' : '' }}"
                        @if( $aula->visible_estudiante !== config('ctes.estados.activo') )
                            data-toggle="tooltip"
                            title="{{ __('diccionario.mensaje_dicAula_no_visible') }}"
                            data-placement="left"
                        @endif
                    >
                        <div class="col-2"><img src="{{ asset('imagenes/ico-libro-verde.svg') }}" ></div>
                        <div class="col-10">{{ $aula->titulo }}</div>
                    </div>
                @endcan

                @cannot( 'createDicAula', App\DicAula::class )
                    {{-- estudiante --}}
                    @if($aula->visible_estudiante == config('ctes.estados.activo'))
                        <div data-id="{{$aula->id}}" class="row dc-sel-aula {{
                            (getDicAulaActivo() && (getDicAulaActivo()->id == $aula->id))? 'activo' : ''
                        }}">
                            <div class="col-2"><img src="{{ asset('imagenes/ico-libro-verde.svg') }}" ></div>
                            <div class="col-10">{{ $aula->titulo }}</div>
                        </div>

                    @else
                        {{-- si no es visible para el estudiante --}}
                        <div data-toggle="tooltip" title="{{ __('diccionario.mensaje_dicAula_no_visible') }}" data-placement="left" class="row dc-sel-aula dc-aula-no-visible {{
                            (getDicAulaActivo() && (getDicAulaActivo()->id == $aula->id))? 'activo' : ''
                        }}">
                            <div class="col-2"><img src="{{ asset('imagenes/ico-libro-verde.svg') }}" ></div>
                            <div class="col-10">{{ $aula->titulo }}</div>
                        </div>
                    @endif
                @endcannot
            @endforeach
        @endif

        {{-- Diccionarios en los que estas unido pero desactivado --}}
        {{--
        @if ( daGetDiccionariosAulaConectadosNoActivosByPersonaId( auth()->user()->userPersona->id ) &&
                    count( daGetDiccionariosAulaConectadosNoActivosByPersonaId(
                            auth()->user()->userPersona->id))>0)
            @foreach( daGetDiccionariosAulaConectadosNoActivosByPersonaId(auth()->user()->userPersona->id) as $aula )
                    <div data-toggle="tooltip" title="{{ __('diccionario.mensaje_dicAula_deshabilitado') }} " data-placement="left" class="d-flex flex-column dc-menu-btn dc-sel-aula dc-aula-no-visible {{
                        (getDicAulaActivo() && (getDicAulaActivo()->id == $aula->id))? 'activo' : ''
                    }}">
                        <img src="{{ asset('imagenes/ico-libro-verde.svg') }}" >
                        {{ $aula->titulo }}
                    </div>

            @endforeach
        @endif
        --}}

    </div>
</div>
    <a class="nav-link d-md-none d-sm-block" href="{{route('diccionariopersonal.get')}}">{{__('diccionario.diccionario_personal')}}</a>
@else
    <a class="nav-link d-md-none d-sm-block" href="{{route('diccionarioaula.get')}}">{{__('diccionario.diccionario_aula')}}</a>
@endif
</li>

<li class="nav-item">
@if ( isset($datos) && isset($datos->tabs[1]['active']) && $datos->tabs[1]['active'] === true  )
{{--  Aula: --}}
    <button class="navbar-toggler submenu" type="button" data-toggle="collapse" data-target="#quepuedohacer-toggle" aria-controls="quepuedohacer-toggle" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
        <div>{{__('diccionario.quepuedohacer')}}</div>
    </button>
    <div class="collapse navbar-collapse submenu" id="quepuedohacer-toggle">
        @include('layouts.partials.components.quepuedohacer-aula-menuLinks', ['pauta_chk'=>'pauta_chk1'])
    </div>

@else
    <button class="navbar-toggler submenu" type="button" data-toggle="collapse" data-target="#quepuedohacer-toggle" aria-controls="quepuedohacer-toggle" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
        <div>{{__('diccionario.quepuedohacer')}}</div>
    </button>
    <div class="collapse navbar-collapse submenu" id="quepuedohacer-toggle">
        @include('layouts.partials.components.quepuedohacer-personal-menuLinks')
    </div>
{{--  Personal: --}}

@endif
</li>
@endsection

@section('content')
@include('layouts.partials.components.quehacer')


@if( isset( $datos->breadcrumb ))
    @include('layouts.partials.components.breadcrumb', ['breadcrumbs' => $datos->breadcrumb] )
@else
    <?php
        $bc = [];
        $rutaAnterior = '/';
        foreach (explode( '/', request()->path() ) as $key => $value) {
            $item = [];
            $item['name'] = $value;
            $item['url'] = $rutaAnterior . $value;
            $rutaAnterior .= $value;
            $bc[] = $item;
        }
        ?>
    @include('layouts.partials.components.breadcrumb', [
    'breadcrumbs' => $bc
    ])
@endif

@include('layouts.partials.alerts.alertas')
{{-- papa probar tamaños de alertas --}}
{{-- @include('layouts.partials.alerts.alertas-test') --}}

<div id="padre_modal_ajax">
</div>

@endsection

@section('footer')
    @include('layouts.partials.components.creditos')
@endsection