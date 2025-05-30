<div class="dc-acepcion-texto" style="overflow: visible">
    <p>
        {{ $acepcion->orden }}. 
        {{ $acepcion->getAtributos() }}
        <br>
        {{ $acepcion->definicion }}
    </p>
    <p class="dc-campos">
        {{-- mas datos visible --}}
        @if ( $acepcion->frase_ejemplo  && $acepcion->frase_ejemplo != '')
        <div class="tematicas py-1" >{{ __('diccionario.ver_acepcion_frase_ejemplo') }} <span class="dc-frase-ejemplo">{{ $acepcion->frase_ejemplo }}</span>
        </div>
        @endif

        {{-- Frase ejemplo visible --}}
        @if ( $acepcion->ejemplo2  && $acepcion->ejemplo2 != '')
        <div class="tematicas py-1" >{{ __('diccionario.ver_acepcion_ejemplo2') }} <span class="dc-ejemplo2">{{ $acepcion->ejemplo2 }}</span>
        </div>
        @endif
        
        {{-- lengua-idioma --}}
        @if ( $acepcion->dpIdioma && $acepcion->dpIdioma->descripcion && $acepcion->dpIdioma->descripcion != '' && $acepcion->idioma_palabra !='' )
        <div class="tematicas py-1" >
            {{-- Devuelve nombre idionma con la prmera letra en mayuscula: --}}
            <span class="dc-idioma">{{  mb_convert_case($acepcion->dpIdioma->descripcion, MB_CASE_TITLE, 'UTF-8') }}:</span> <span class="dc-idioma-palabra">{{ $acepcion->idioma_palabra }}</span>
        </div>
        @endif

        {{-- Tematicas Generales Visibles --}}
        @if( $acepcion->dpAcepcionTematicas && $acepcion->dpAcepcionTematicas->count()>0 ) 
        <div class="tematicas py-1" >
            @lang('diccionario.ver_acepcion_tematica'): 
            @foreach($acepcion->dpAcepcionTematicas as $key => $tematica)
                <span class="dc-tematica"> {{ $tematica->descripcion }} </span>
                @if(!$loop->last)
                    , 
                    @endif
            {{-- Estado: {{ $tematica->estado }} </li> --}}
            @endforeach                    
        </div>
        @endif
    </p>
</div>