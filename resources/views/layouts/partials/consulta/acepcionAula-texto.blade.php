<div class="dc-acepcion-texto" style="overflow: visible">
    <p>
        {{ $acepcion->orden }}. 
        {{-- categoria, genero y numore  en atribuutos --}}
        {{ $acepcion->getAtributos() }}<br>
        {{ $acepcion->definicion }}
    </p>
    
    <p class="dc-campos">
        {{-- Mas datos visible --}}
        @if ( $acepcion->envioEntrada->dpEnvio->dicAula->dicAulaCampos
                ->where('mst_campo_entrada_id',config('ctes.mst_campos_entrada.frase_ejemplo'))->first()->visible == config('ctes.visibilidad.visible'))
            @if ( $acepcion->frase_ejemplo  && $acepcion->frase_ejemplo != '')
            <div class="tematicas py-1" >{{ __('diccionario.ver_acepcion_frase_ejemplo') }}<span class="dc-frase-ejemplo"> {{ $acepcion->frase_ejemplo }}</span>
            </div>
            @endif
        @endif


        {{-- Frase ejemplo visible --}}
        <?php
        $frase_ejemplo_first = $acepcion->envioEntrada->dpEnvio->dicAula->dicAulaCampos
        ->where('mst_campo_entrada_id',config('ctes.mst_campos_entrada.ejemplo2'))->first(); 
        ?>

        @if ( $frase_ejemplo_first && $frase_ejemplo_first->visible == config('ctes.visibilidad.visible'))
            @if ( $acepcion->ejemplo2  && $acepcion->ejemplo2 != '')
            <div class="tematicas py-1" >{{ __('diccionario.ver_acepcion_ejemplo2') }}<span class="dc-ejemplo2"> {{ $acepcion->ejemplo2 }}</span>
            </div>
            @endif
        @endif
        
        
        {{-- lengua-idioma --}}
        @if ( $acepcion->envioEntrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id',  
        // $acepcion->dpIdioma->mst_campo_entrada_id 
        5 )->first()->visible == config('ctes.visibilidad.visible'))
            @if ( $acepcion->dpIdioma && $acepcion->dpIdioma->descripcion && $acepcion->dpIdioma->descripcion != '' && $acepcion->idioma_palabra !='' )
            <div class="tematicas py-1" >
                <span class="dc-idioma">{{  mb_convert_case($acepcion->dpIdioma->descripcion, MB_CASE_TITLE, 'UTF-8') }}:</span> <span class="dc-idioma-palabra">{{ $acepcion->idioma_palabra }}</span>
            </div>
            @endif
        @endif
        {{-- Tematicas Generales Visibles --}}
        @if ( $acepcion->envioAcepcionTematicas->count()>0 && $acepcion->envioEntrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id', $acepcion->envioAcepcionTematicas->first()->dpTematica->mst_campo_entrada_id)->first()->visible == config('ctes.visibilidad.visible'))
        <div class="tematicas py-1" >
            @lang('diccionario.ver_acepcion_tematica'): 
            @foreach( $acepcion->envioAcepcionTematicas as $key => $tematica)
            <span class="dc-tematica">
                {{ $tematica->dpTematica->descripcion }}{{ !$loop->last? ',':''}}
            </span>
            {{-- Estado: {{ $tematica->estado }} </li> --}}
            @endforeach                    
        </div>
        @endif
    </p>
</div>