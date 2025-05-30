{{-- línea de idioma --}}
<div class="form-row" id="otroLenguaje" style="display: none">
    {{-- columna vacía --}}
    <div class="form-group col-md-4">
    </div>
    
    
    {{-- columna idiomas disponibles --}}
    <div class="form-group col-md-4">
        <label for="idioma_id">@lang('diccionario.acepcion_idioma')</label>
        <div class="dc-select-grp">
            <select  id="idioma_id" name="idioma_id" class="form-control input-group-append">
                <option value="1">@lang('diccionario.acepcion_seleccione_valor')</option>
                @foreach($datos->listaIdiomas as $idioma)
                    <option value="{{ $idioma->id }}" {{ (($datos->acepcion->idioma_id == $idioma->id || old('idioma_id') == $idioma->id) ? "selected":"") }}>
                        {{ $idioma->descripcion }}
                    </option>
                @endforeach
            </select>
            <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">           
        </div>
    </div>


    {{-- columna idiomas seleccionados --}}
    <div class="form-group col-md-4">
        <label for="idioma_palabra">@lang('diccionario.acepcion_idioma_traduccion')</label>
        <input type="text" name="idioma_palabra" id="idioma_palabra" class="form-control" value="{{ ($datos->acepcion->idioma_palabra) ? : old('idioma_palabra') }}" />
    </div>
</div>

{{-- línea de combos --}}
<div class="form-row">
    <div class="form-group col-md-4">
        <label for="cat_gramatical_id">@lang('diccionario.acepcion_categoria')</label>
        <div class="dc-select-grp">
            <select id="cat_gramatical_id" name="cat_gramatical_id" class="form-control">
                <option value="">@lang('diccionario.acepcion_seleccione_valor')</option>                            
                @foreach($datos->listaCategorias as $categoria)
                    <option value="{{ $categoria->id }}" {{ (($datos->acepcion->cat_gramatical_id == $categoria->id || old('cat_gramatical_id') == $categoria->id) ? "selected":"") }}>
                        {{ $categoria->descripcion }}</option>
                @endforeach
            </select>
            <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">           
        </div>
    </div>
    <div class="form-group col-md-4">
        <label for="genero_id">@lang('diccionario.acepcion_genero')</label>
        <div class="dc-select-grp">
        <select id="genero_id" name="genero_id" class="form-control">
            <option value="">@lang('diccionario.acepcion_seleccione_valor')</option>
            @foreach($datos->listaGeneros as $genero)
                <option value="{{ $genero->id }}" {{ (($datos->acepcion->genero_id == $genero->id || old('genero_id') == $genero->id) ? "selected":"") }}>
                    {{ $genero->descripcion }}
                </option>
            @endforeach
        </select>
        <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
        </div>
    </div>
    <div class="form-group col-md-4">
        <label for="numero_id">@lang('diccionario.acepcion_numero')</label>
        <div class="dc-select-grp">
        <select id="numero_id" name="numero_id" class="form-control">
            <option value="">@lang('diccionario.acepcion_seleccione_valor')</option>
            @foreach($datos->listaNumeros as $numero)
                <option value="{{ $numero->id }}" {{ (($datos->acepcion->numero_id == $numero->id || old('numero_id') == $numero->id) ? "selected":"") }}>
                    {{ $numero->descripcion }}
                </option>
            @endforeach
        </select>
        <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
        </div>
    </div>

</div>

{{-- Definicion --}}

<div class="form-row">
    <div class="form-group {{ $errors->has('definicion') ? 'has-error' :'' }} col-md">
        <label for="definicion">@lang('diccionario.acepcion_definicion') *</label>
        <textarea required class="form-control" id="definicion" name="definicion" rows="3" maxlength="{{ config('ctes.constantes_acepciones.max_definicion') }}" placeholder="{{ __('diccionario.acepcion_placeholder_maximo', ['max'=>config('ctes.constantes_acepciones.max_definicion')]) }}">{{ ($datos->acepcion->definicion) ? : old('definicion') }}</textarea>
        @if($errors->has('definicion')) <p class="help-block">{{ $errors->first('definicion') }}</p> @endif
    </div>
</div>
{{-- línea de ejemplo y mas datos   --}}
<div class="form-row">
    {{-- EJEMPLO --}}
    <div class="form-group col-md-6">
        <label for="ejemplo2">@lang('diccionario.acepcion_ejemplouso')</label>
        <textarea class="form-control" id="ejemplo2" name="ejemplo2" rows="3" maxlength="{{ config('ctes.constantes_acepciones.max_frase') }}" placeholder="{{ __('diccionario.acepcion_placeholder_maximo', ['max'=>config('ctes.constantes_acepciones.max_frase')]) }}">{{ ($datos->acepcion->ejemplo2) ? : old('ejemplo2') }}</textarea>
    </div>
    
    {{-- OJO: originalmente se cambio frase_ejemplo por Mas datos asi que para evitar que carge carge mas datos en ejeplo o se pierdan estos datos necesito mantener los nombres    --}}
    {{-- MAS_DATOS --}}
    <div class="form-group col-md-6">
        <label for="frase_ejemplo">@lang('diccionario.acepcion_masdatos')</label>
        <textarea class="form-control" id="frase_ejemplo" name="frase_ejemplo" rows="3" maxlength="{{ config('ctes.constantes_acepciones.max_frase') }}" placeholder="{{ __('diccionario.acepcion_placeholder_maximo', ['max'=>config('ctes.constantes_acepciones.max_frase')]) }}">{{ ($datos->acepcion->frase_ejemplo) ? : old('frase_ejemplo') }}</textarea>
    </div>
</div>

{{-- Línea de temáticas --}}
@include('layouts/partials/dpAcepcion/tematicas')
