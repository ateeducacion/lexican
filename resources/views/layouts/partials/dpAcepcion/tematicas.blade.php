<div class="form-row">
    {{-- Columna de lista de temáticas disponibles --}}
    <div class="form-group col-md-5">
        <label for="tematicas_disponibles">@lang('diccionario.acepcion_tematica') {{ __('diccionario.disponibles') }}</label>
        <input type="text" name="tematica_buscador" id="tematica_buscador" class="form-control" />
        <select multiple id="tematicas_disponibles" name="tematicas_disponibles" class="form-control" ondblclick="tematicaAnadir()">
            @foreach($datos->listaTematicasDisponibles as $tematica)
                <option id="tematicas_disponibles_opt_{{ $tematica->id }}" value="{{ $tematica->id }}">{{ $tematica->descripcion }}</option>
            @endforeach
        </select>
    </div>

    {{-- Columna de botones de añadir y quitar temáticas --}}
    <div class="form-group col-md-2" style="text-align: center">
        <label>&nbsp;</label>
        <div>
                <div class="dpAcepcionAnadirBtn cursor-pointer mx-auto pl-md-1 pl-lg-3" onclick="tematicaAnadir()">                                      
                    <div class="mr-2 d-inline-block d-md-none d-lg-inline-block dpAcepcionAnadirBtn-text">
                        @lang('diccionario.acepcion_tematica_anadir')
                    </div>                                    
                    <img class="dpAcepcionAnadirImg" src="{{ asset('imagenes/boton_flecha_derecha.svg') }}">
                </div> 
        </div>
        <div>
            <div 
            class="dpAcepcionAnadirBtn cursor-pointer mx-auto pl-md-1 pl-lg-3" 
            onclick="tematicaQuitar()" >                                      
                <div class="mr-2 d-inline-block d-md-none d-lg-inline-block dpAcepcionAnadirBtn-text">
                    @lang('diccionario.acepcion_tematica_quitar')
                </div>
                <img class="dpAcepcionAnadirImg" src="{{ asset('imagenes/boton_flecha_izquierda.svg') }}" alt="@lang('diccionario.acepcion_tematica_quitar')">
                
            </div>
        </div>
    </div>

    {{-- Columna de lista de temáticas seleccionadas --}}
    <div class="form-group col-md-5">
        <label for="tematicas_seleccionadas">@lang('diccionario.acepcion_tematica') {{ __('diccionario.seleccionadas') }}</label>
        <select multiple id="tematicas_seleccionadas" name="tematicas_seleccionadas" class="form-control" ondblclick="tematicaQuitar()">
            @if(isset($datos->listaTematicasSeleccionadas))
                @foreach($datos->listaTematicasSeleccionadas as $tematica)
                    <option id="tematicas_disponibles_opt_{{ $tematica->id }}" value="{{ $tematica->id }}">{{ $tematica->descripcion }}</option>
                @endforeach
            @endif
        </select>
    </div>
    <input type="hidden" id="listaTematicas" name="listaTematicas" value="" />
</div>