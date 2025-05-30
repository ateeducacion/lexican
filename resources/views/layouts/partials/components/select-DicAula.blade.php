{{-- carga iconos svg para bocadillo --}}
@include('layouts.partials.components.actionbuttons-iconossvg')

<input type="hidden" id="aulaSelectredirectUrl" value="{{ $aulaSelectredirectUrl?? '' }}" >
{{-- verison web: --}}
<div class="dc-select-dicAula-activo d-none d-md-block text-right">
    {{-- Elegir diccionario de aula activo --}}
    {{-- select oculto  --}}
    <select class="form-control d-none" name="diccionarioAulaActivo" id="diccionarioAulaActivo">
        
        @if( !getDicAulaActivo()  )
            <option value="">  </option>
        @endif
        
        @if( getDicAulaActivo() && (getDicAulaActivo()->id ) )
            <option value="{{ getDicAulaActivo()->id }}" >
                {{ getDicAulaActivo()->id }}
                {{ getDicAulaActivo()->titulo }}
            </option>
        @endif   

        {{-- diccionarios que has creado --}}
        @if( 
                auth()->user()->userPersona->dicAulas && 
                count(auth()->user()->userPersona->dicAulas)>0 
        )
            @foreach( auth()->user()->userPersona->dicAulas as $aula )
            <option value="{{$aula->id}}">{{$aula->titulo}}</option>
            @endforeach
        @endif

        {{-- Diccionarios a los que te has unido --}}
        
        @if (
                daGetDiccionariosAulaConectadosByPersonaId( auth()->user()->userPersona->id ) &&
                count( daGetDiccionariosAulaConectadosByPersonaId(auth()->user()->userPersona->id))>0                 
            )
            @foreach( daGetDiccionariosAulaConectadosByPersonaId(auth()->user()->userPersona->id) as $aula )
                <option value="{{$aula->id}}">{{$aula->titulo}}</option>
            @endforeach
        @endif
    </select>

{{-- {{ $dicAulaActivoName }}  --}}
{{-- Muestra siempre diccionario selecionado actualmente o Boton de unirse a diccionario si no estas unido a ninguno--}}
@if( getDicAulaActivo() )
    <div class="dc-select-dicAula-activo-show">
        {{-- Cuando hay diciconario activo te permite elegir --}}
        <span class="" >{{ getDicAulaActivo()->titulo }}</span>
        {{-- {{ separaCodigo( getDicAulaActivo()->codigo ) }} --}}
        <img 
            @if(getDicAulaActivo()->visible_estudiante != config('ctes.estados.activo'))
                class="dc-aula-no-visible"
            @endif
            src="{{ asset('imagenes/ico-libro-verde.svg') }}" >

    </div>
@else

    <div class="dc-select-dicAula-activo-show">
        <img class="dc-aula-no-visible" src="{{ asset('imagenes/ico-libro-verde.svg') }}" >
    </div>

@endif  



{{-- muestra desplegado --}}
    <div class="position-relative">
        <div class="dc-bocadillo vertical" id="bocadillo-dic-aula" >
            <div class="position-relative w-100">

                <div class="dc-bocadillo-bg vertical d-flex flex-column">
                    <div class="dc-bocadillo-vertical-top"> </div>
                    <div class="dc-bocadillo-vertical-middle"></div>
                    <div class="dc-bocadillo-vertical-bottom"></div>
                </div>
                
                <div class="d-flex flex-column dc-bocadillo-contenido">

                    {{-- Diccionarios a los que te has unido --}}
                    @if (
                        daGetDiccionariosAulaConectadosByPersonaId( auth()->user()->userPersona->id ) &&
                        count( daGetDiccionariosAulaConectadosByPersonaId(auth()->user()->userPersona->id))>0                 
                    )
                        @foreach( daGetDiccionariosAulaConectadosByPersonaId(auth()->user()->userPersona->id) as $aula )
                            
                            @can( 'createDicAula', App\DicAula::class )
                                <div data-id="{{$aula->id}}" class="d-flex flex-column dc-menu-btn dc-sel-aula
                                    {{ $aula->visible_estudiante == config('ctes.estados.activo')? '':'dc-aula-no-visible' }} 
                                    {{ (getDicAulaActivo() && (getDicAulaActivo()->id == $aula->id))? 'activo' : '' }}"
                                    @if( $aula->visible_estudiante !== config('ctes.estados.activo') ) 
                                        data-toggle="tooltip" 
                                        title="{{ __('diccionario.mensaje_dicAula_no_visible') }}"
                                        data-placement="left" 
                                    @endif
                                >
                                    <img src="{{ asset('imagenes/ico-libro-verde.svg') }}" >
                                    {{ $aula->titulo }}
                                </div>                        
                            @endcan

                            @cannot( 'createDicAula', App\DicAula::class )
                                {{-- estudiante --}}
                                @if($aula->visible_estudiante == config('ctes.estados.activo'))
                                    <div data-id="{{$aula->id}}" class="d-flex flex-column dc-menu-btn dc-sel-aula {{ 
                                        (getDicAulaActivo() && (getDicAulaActivo()->id == $aula->id))? 'activo' : '' 
                                    }}">
                                        <img src="{{ asset('imagenes/ico-libro-verde.svg') }}" >
                                        {{ $aula->titulo }}
                                    </div>
                                
                                @else 
                                    {{-- si no es visible para el estudiante --}}
                                    <div data-toggle="tooltip" title="{{ __('diccionario.mensaje_dicAula_no_visible') }}" data-placement="left" class="d-flex flex-column dc-menu-btn dc-sel-aula dc-aula-no-visible {{ 
                                        (getDicAulaActivo() && (getDicAulaActivo()->id == $aula->id))? 'activo' : '' 
                                    }}">
                                        <img src="{{ asset('imagenes/ico-libro-verde.svg') }}" >
                                        {{ $aula->titulo }}
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

                    {{-- Unirse a  diccionario --}}
                    <div id="dc-select-dicaula-unirsedic" class="text-center cursor-pointer">
                    @include('layouts.partials.components.botones.diccionario.unirse', [
                        'id' => 'uid'.uniqid(),
                        'bocadillo' => 'ico-bocadillo-plus',
                        'caption' => __('diccionario.unirse_dic'),
                        'modal_width' => '1000px',
                    ])
                    </div>
                </div>

            </div>
        </div>
    </div>
    
</div>


{{-- Mostrar solo en movil --}}
<div class="dc-select-dicAula-activo col-4 d-block d-sm-none">
    <div class="dc-actionbutton-menu-bg d-none"></div>
    <div class="d-flex">

    </div>
</div>
