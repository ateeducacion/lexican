{{-- enviosDehabilitadosGris  {{ isset($enviosDehabilitadosGris)? $enviosDehabilitadosGris : 'no'   }} --}}
{{-- mostar no visibles? --}}
{{-- poder selecionar envios habilitados? --}}
{{-- <li>
    estado : {{ $diccionarioAula->estado }} <br>
    estado visible? {{ ($diccionarioAula->estado == config('ctes.comentarios_visibles.visible')) }}
    visible estudiante? {{ $diccionarioAula->visible_estudiante }}
    <br>
</li> --}}
{{-- @if( isset($ocultarNoVisiblesEstudiante) && $ocultarNoVisiblesEstudiante && 
        $diccionarioAula->visible_estudiante ==  ) --}}
{{-- @else  --}}
{{-- @endif --}}
{{-- <div class="comentario-texto-letra">{{ $diccionarioAula->profesor->nombreCompleto() }}</div> --}}

{{-- Para no poner los textos directamente en el js y usar lang --}}
<input type="hidden" id="lang_importarDesde" value="{{ __("diccionario.importar_desde") }}" />

@foreach($listaDiccionariosAula->sortBy('titulo') as $diccionarioAula)
@if( 
        isset($ocultarNoVisible) && 
        $ocultarNoVisible && 
        $diccionarioAula->visible_estudiante == config('ctes.visibilidad.novisible')
)
{{-- Si no es visible y esta definido ocultar visible no muestra nada --}}
@else

    @if(isset($enviosDehabilitadosGris) && $enviosDehabilitadosGris && $diccionarioAula->enviosHabilitados() == config('ctes.estado_envio_habilitado.inactivo'))
        <li class="libroDiccionarioGris" data-toggle="tooltip" title="{{ __('diccionario.tooltip_envios_deshabilitados') }} " data-placement="bottom" >
            <label for="chk_{{ $diccionarioAula->id }}">
                <img src="{{ asset('imagenes/ico-libro-gris.svg') }}" />
            </label>
            
    @else
        <li class="libroDiccionarioVerde">    
            <input 
                type="checkbox" 
                name="{{ isset($checkbox_name)?  $checkbox_name : 'checkbox' }}" 
                @isset($checkbox_name)
                {{old($checkbox_name)? (old($checkbox_name) == "chk_$diccionarioAula->id"? 'checked' :'') : ''}}     
                @endisset                
                id="chk_{{ $diccionarioAula->id }}" 
                value="chk_{{ $diccionarioAula->id }}" 
                data-dataid="{{ $diccionarioAula->id }}" 
                data-title="{{ $diccionarioAula->titulo }}" 
                autocomplete="off" onchange='handleChange(this)' 
            />
            <label for="chk_{{ $diccionarioAula->id }}"
                data-toggle="tooltip" 
                title="{{ __('diccionario.coordinador') . ': ' . $diccionarioAula->profesor->nombreCompleto() }}"
                data-placement="top" 
                >
                <img class="ico-libro-verde" src="" alt="{{ $diccionarioAula->titulo }}" />
            </label>
    @endif        
        <div class="comentario-texto-letra">{{ $diccionarioAula->titulo }}<br /></div>            

    </li>

    
@endif

{{-- <div> --}}
    {{-- [  --}}
        {{-- {{ $diccionarioAula->titulo }} - 
        {{ ( $diccionarioAula->visible_estudiante == config('ctes.visibilidad.visible') ) }} 
        -{{ $diccionarioAula->visible_estudiante }}  --}}
        {{-- {{ ( $diccionarioAula->visible_estudiante == config('ctes.visibilidad.visible') ) ? 'visible': 'no visible' }},
        {{ $diccionarioAula->enviosHabilitados() == config('ctes.estado_envio_habilitado.inactivo') ? 'envios no habilitados': 'envios habilitados' }} --}}
    {{-- ] --}}
{{-- </div> --}}
@endforeach
