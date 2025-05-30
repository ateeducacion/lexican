@extends('diccionario.home')
@section('title', 'Diccionario de Aula')
@section('content')
    @parent
    <h3>
        {{ isset($diccionarioAula) ? __('diccionario.Modificar diccionario de aula') : __('diccionario.Crear diccionario de aula') }}
        {{-- <br>estado {{ $diccionarioAula->estado }}         --}}
        {{-- vigencia max -{{ $datos->vigenciaMax }}- --}}
    </h3>
    <div class="dc-panel-gris card">
        <form class="dc-input-group" style="width: 100%" method="POST"
            action="{{ isset($diccionarioAula) ? 'edit' : 'create' }}">
            @csrf
            <div class="row text-center diccionario-aula-info">
                <div class="col-12 col-sm-12 col-md">
                    <label for="propietario">{{ __('diccionario.Propietario') }}:</label>
                    <span id="propietario">{{ getSessionPersona()['nombre'] }}</span>
                </div>
                <div class="col-12 col-sm-12 col-md">
                    <label for="cursoEscolar">{{ __('diccionario.Curso escolar') }}:</label>

                    @if (isset($diccionarioAula))
                        <span id="cursoEscolar">
                            {{ getStrCursoByAnoIni($diccionarioAula->ano_ini_curso_escolar) }}
                        </span>
                    @else
                        <span id="cursoEscolar">{{ getFormattedCursoEscolar(time()) }}</span>
                    @endif

                </div>

                @if (isset($diccionarioAula))
                    <div class="col-12 col-sm-12 col-md">
                        <label for="códigoCabecera">{{ __('diccionario.Código') }}:</label>
                        <span id="códigoCabecera">{{ $diccionarioAula->codigo }}</span>
                    </div>
                @endif

            </div>

            <div class="row">

                @isset($diccionarioAula)
                    {{-- por si existiese algun diccionario anterior con tipo de diccionario se conserva el dato --}}
                    <input type="hidden" name="tipoDiccionario" id="tipoDiccionario"
                        value="{{ $diccionarioAula->mst_tipo_dic_id }}">
                @endisset

                {{-- Se quita el tipo de diccionario en la bbdd se sigue guardando como 1 siempre
        <div class="form-group col-12 col-sm-12 col-md-6 col-lg-4">
          <label for="tipoDiccionario">{{__('diccionario.Tipo de diccionario')}}*</label>
          <div class="dc-select-grp">

            <select class="form-control input-group-append" name="tipoDiccionario" id="tipoDiccionario" >
              @foreach ($tipoDiccionarioAulas as $tipoDiccionarioAula)
              <option value="{{$tipoDiccionarioAula->id}}" {{ old('tipoDiccionario') == $tipoDiccionarioAula->id || ( isset($diccionarioAula) && $diccionarioAula->mst_tipo_dic_id == $tipoDiccionarioAula->id)? 'selected': ''}}>{{$tipoDiccionarioAula->tipo_diccionario}}</option>
              @endforeach
            </select>
            <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
          </div>
        </div> --}}
                <div class="form-group col-12">
                    <label for="nombreDiccionario">{{ __('diccionario.Nombre del diccionario') }}*</label>
                    <input class="form-control" type="text" name="nombreDiccionario" id="nombreDiccionario"
                        value="{{ old('nombreDiccionario') ? old('nombreDiccionario') : $diccionarioAula->titulo ?? '' }}">
                </div>
            </div>
            <div class="row">
                <div class="form-group col-12 col-sm-12">
                    <label for="descripcionDiccionario">{{ __('diccionario.Descripción') }}</label>
                    <textarea class="form-control" name="descripcionDiccionario"
                        id="descripcionDiccionario">{{ old('descripcionDiccionario') ? old('descripcionDiccionario') : $diccionarioAula->descripcion ?? '' }}</textarea>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-12 col-sm-12 col-md-6 col-lg">
                    <label for="estudio">{{ __('diccionario.Nivel de Estudio') }}</label>
                    <div class="dc-select-grp">
                        <select class="form-control input-group-append" name="estudio" id="estudio">
                            <option selected value="0">-- {{ __('diccionario.Seleccionar') }} --</option>

                            @foreach ($nivelEstudios as $nivelEstudio)
                                <option value="{{ $nivelEstudio->id }}"
                                    {{ old('estudio') == $nivelEstudio->id || (isset($diccionarioAula) && $diccionarioAula->mst_nivel_estudios_id == $nivelEstudio->id) ? 'selected' : '' }}>
                                    {{ $nivelEstudio->descripcion }}</option>
                            @endforeach

                        </select>
                        <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
                    </div>
                </div>
                <div class="form-group col-12 col-sm-12 col-md-6 col-lg">
                    <label for="grupoLetra">{{ __('diccionario.Grupo') }}</label>
                    <div class="dc-select-grp">
                        <select class="form-control input-group-append" name="grupoLetra" id="grupoLetra">
                            <option selected value="0">-- {{ __('diccionario.Seleccionar') }} --</option>

                            @foreach (range('A', 'Z') as $letter)
                                <option value="{{ $letter }}"
                                    {{ old('grupoLetra') == $letter || (isset($diccionarioAula) && $diccionarioAula->letra_grupo == $letter) ? 'selected' : '' }}>
                                    {{ $letter }}</option>
                            @endforeach

                        </select>
                        <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
                    </div>
                </div>
                <div class="form-group col-12 col-sm-12 col-md-6 col-lg">
                    <label for="areaMateria">{{ __('diccionario.Area materia') }}</label>
                    <div class="dc-select-grp">
                        <select class="form-control input-group-append" name="areaMateria" id="areaMateria">

                            @foreach ($areaMaterias as $areaMateria)
                                <option value="{{ $areaMateria->id }}"
                                    {{ old('areaMateria') == $areaMateria->id || (isset($diccionarioAula) && $diccionarioAula->mst_area_materia_id == $areaMateria->id) ? 'selected' : '' }}>
                                    {{ $areaMateria->descripcion }}</option>
                            @endforeach

                        </select>
                        <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="form-group col-12 col-md-12 col-lg-6 mb-0">
                    <label for="vigencia">
                        {{ __('vigencia') }}
                        <img data-toggle="tooltip" data-placement="top" title="{{  __('diccionario.tooltip_vigencia') }}"
                            class="d-inline-block dc-info-ico" src="{{ asset('imagenes/ico-info.svg') }}">
                    </label>
                </div>
            </div>
            <div class="row">
                <div class="form-group col-12 col-md-12 col-lg-6 d-flex">

                        <div class="dc-select-grp flex-grow-1 {{ isset($diccionarioAula)? 'disabled':''}}" style="height: var(--alto);"  >
                            {{-- @isset($diccionarioAula) --}}
                            <input type="hidden" name="vigencia" id="hiddenVigencia" value="{{ isset($diccionarioAula)? $diccionarioAula->vigencia :'1' }}">
                            {{-- @endisset --}}
                            <select class="form-control input-group-append" name="selectVigencia" id="vigencia" onChange="updateVigencia()"
                                {{ isset($diccionarioAula)? 'disabled':'' }} 
                            >
                                {{-- @for($i = (isset($diccionarioAula)? $datos->vigenciaMin: 0); $i <= $datos->vigenciaMax; $i++) --}}
                                @for($i = 1; $i <= $datos->vigenciaMax; $i++)
                                    <option value="{{ $i }}" 
                                            {{ (isset($diccionarioAula) && $diccionarioAula->vigencia == $i) ? 'selected' : '' }}
                                            {{ old('vigencia') == $i ? 'selected':'' }} 
                                            @if(isset($diccionarioAula) && $i < $datos->vigenciaMin)
                                                disabled
                                            @endif
                                    >
                                    @if(isset($diccionarioAula))
                                        {{ trans_choice('diccionario.vigencia__select_item', $i, [
                                            'n'=>$i, 
                                            'curso'=> getStrCursoByAnoIni( $diccionarioAula->ano_ini_curso_escolar+$i-1 )
                                        ]) }}
                                    @else
                                        {{ trans_choice('diccionario.vigencia__select_item', $i, [
                                            'n'=>$i, 
                                            'curso'=> getStrCursoByAnoIni(getAnoIniCurrentCursoEscolar()+$i-1 )
                                        ] ) }}
                                    @endif 
                                    </option>
                                @endfor
                            </select>
                            <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
                            
                        </div>
                        @if(isset($diccionarioAula))
                            <img 
                                onclick="enableVigencia()" 
                                data-toggle="tooltip" 
                                
                                {{-- title="{{ __('diccionario.activar_edicion__vigencia') }}"  --}}
                                title="{{ __('diccionario.Editar') }}"
                                data-placement="top" 
                                style="bottom: 4px; position: relative; height: 30px;"
                                class="cursor-pointer" 
                                src="{{ asset('imagenes/ico-edit.svg') }}">
                        @endif


                </div>
            </div>

            @if (!isset($diccionarioAula))
                <div>
                    <input type="checkbox" checked="checked" id="importarsection" class="toggleDiv">
                    <h5><label for="importarsection">{{ __('diccionario.Importar entradas') }}<div class="show-hide">
                            </div></label></h5>
                    <div class="row form-check">
                        <div class="row form-check">
                            <input class="" type="radio" id="vacio" name="importar" value="0" checked>
                            <label class="font-weight-normal"
                                for="vacio">{{ __('diccionario.Crear diccionario vacio') }}</label>
                        </div>
                        <div class="row form-check">

                            @if ($datos->listaDiccionariosAula->count() > 0)
                                <input {{ old('importarRadio') ? 'checked' : '' }} class="" type="radio" id="importar"
                                    name="importar" value="1">
                                <label data-toggle="modal" data-target="#importarEntradas"
                                    class="font-weight-normal underline-hover"
                                    for="importar">{{ __('diccionario.Importar entradas de otro diccionario') }}</label>
                                <span class="selected-element">

                                    @if (old('importarRadio'))
                                        {{-- Importar enttardas "Nombre diccionario" (10) --}}

                                        {{ __('diccionario.Importar entradas') }}
                                        "{{ $datos->listaDiccionariosAula->where('id', str_replace('chk_', '', old('importarRadio')))->first()->titulo }}"
                                        ({{ count($datos->listaDiccionariosAula->where('id', str_replace('chk_', '', old('importarRadio')))->first()->dicAulaEntradas) }})
                                    @endif

                                </span>
                            @else
                                <input class="" type="radio" id="importar" disabled>
                                <label class="font-weight-normal"
                                    for="importar">{{ __('diccionario.Importar entradas de otro diccionario') }}
                                    ({{ __('diccionario.importar_no_existen_diccionarios') }})</label>
                            @endif

                        </div>
                    </div>
                </div>
            @endif

            <div>
                <input type="checkbox" checked="checked" id="config" class="toggleDiv">
                <h5><label for="config">{{ __('diccionario.Configuración entradas') }}<div class="show-hide"></div>
                    </label>
                </h5>
                <div class="row">
                    <div class="mt-3 col-12 col-sm-12">
                        <div class="row">
                            <div class="row col-12 col-sm-12 col-md-6">
                                <div class="col-12 col-sm-10 col-md-8">
                                    <label class="sublabel font-weight-bold"
                                        for="maxAcepciones">{{ __('diccionario.Número máximo de acepciones por entrada') }}</label>
                                </div>
                                <div class="col-12 col-sm-2 col-md-4">
                                    <div class="dc-select-grp">
                                        <select class="form-control input-group-append" name="maxAcepciones"
                                            id="maxAcepciones">
                                            <option value="">{{ __('diccionario.Seleccione un valor...') }}</option>
                                            {{ $maxAcepciones = 10 }}

                                            @for ($i = 1; $i <= $maxAcepciones; $i++)
                                                <option value="{{ $i }}"
                                                    {{ old('maxAcepciones') == $i || (isset($diccionarioAula) && $diccionarioAula->max_acepciones_entrada == $i) ? 'selected' : '' }}>
                                                    {{ $i }}</option>
                                            @endfor

                                        </select>
                                        <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 col-12 col-sm-12">
                        <div class="row">
                            <div class="col-12 col-sm-12 col-md-6 col-lg-4">
                                <span
                                    class="form-span font-weight-bold">{{ __('diccionario.Campos visibles en entradas') }}</span>

                                @foreach ($camposEntradas as $camposEntrada)
                                    <div class="col-12">

                                        @if (!isset($diccionarioAula))
                                            {{-- Por defecto todos los campos estan como visibles --}}
                                            <input type="checkbox" name="{{ $camposEntrada->id }}_visible"
                                                id="{{ $camposEntrada->id }}_visible" checked>
                                        @else

                                            <input type="checkbox" name="{{ $camposEntrada->id }}_visible"
                                                id="{{ $camposEntrada->id }}_visible"
                                                {{ old($camposEntrada->id . '_visible') == 'on' ||
(isset($diccionarioAula) &&
    $diccionarioAula->dicAulaCampos()->where('mst_campo_entrada_id', $camposEntrada->id)->first() !== null &&
    $diccionarioAula->dicAulaCampos()->where('mst_campo_entrada_id', $camposEntrada->id)->first()->visible == config('ctes.estados.activo'))
    ? 'checked'
    : '' }}>
                                        @endif

                                        <label class="font-weight-normal"
                                            for="{{ $camposEntrada->id }}_visible">{{ $camposEntrada->nombre_campo }}</label>
                                    </div>
                                @endforeach

                            </div>
                            <div class="col-12 col-sm-12 col-md-6 col-lg-4">
                                <span
                                    class="form-span font-weight-bold">{{ __('diccionario.Campos obligatorios en entradas') }}</span>

                                @foreach ($camposEntradas as $camposEntrada)
                                    <div class="col-12">
                                        <input type="checkbox" name="{{ $camposEntrada->id }}_obligatorio"
                                            id="{{ $camposEntrada->id }}_obligatorio"
                                            {{ old($camposEntrada->id . '_obligatorio') == 'on' ||
(isset($diccionarioAula) &&
    $diccionarioAula->dicAulaCampos()->where('mst_campo_entrada_id', $camposEntrada->id)->first() !== null &&
    $diccionarioAula->dicAulaCampos()->where('mst_campo_entrada_id', $camposEntrada->id)->first()->obligatorio == config('ctes.estados.activo'))
    ? 'checked'
    : '' }}>
                                        <label class="font-weight-normal"
                                            for="{{ $camposEntrada->id }}_obligatorio">{{ $camposEntrada->nombre_campo }}</label>
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <input type="checkbox" checked="checked" id="pautas" name="pautas" class="toggleDiv">
                <h5><label for="pautas">{{ __('diccionario.Pautas diccionario') }}<div class="show-hide"></div></label>
                </h5>
                <div class="row">
                    <div class="mt-3 col-12 col-12-sm">
                        <button class="dc-round-btn dc-btn-ordenar mb-2 float-right" data-event="restablecer-pautas"
                            id="restablecerPautas" type="button">
                            <div class="d-inline-block mr-2">{{ __('diccionario.Restablecer') }}</div>
                        </button>
                        <span class="form-span font-weight-bold">{{ __('diccionario.Pautas diccionario') }}</span>
                        <textarea class="mt-3 textarea-tiny w-100" name="pautasEspecificas" id="pautasEspecificas" cols="30"
                            rows="10">
                            {{ old('pautasEspecificas') ? old('pautasEspecificas') : (isset($diccionarioAula) && $diccionarioAula->pauta()->first() !== null ? $diccionarioAula->pauta()->first()->texto : $datos->maestroPautas->texto) }}
                          </textarea>
                        <textarea class="d-none" name="" id="mst_pautas" cols="30" rows="10">
                            {{ $datos->maestroPautas->texto }}
                          </textarea>
                    </div>

                </div>

            </div>

            {{-- Visibilidad, envíos y avisos --}}
            <div>
                <input type="checkbox" checked="checked" id="avisos" name="avisos" class="toggleDiv">
                <h5><label for="avisos">{{ __('diccionario.Visibilidad, envíos y avisos') }}<div class="show-hide"></div>
                    </label></h5>
                <div class="row">
                    {{-- Avisar de envios al correo --}}
                    <div class="row col-12">
                        <div class="px-3 col-12 col-md-6 ">
                            <span
                                class="font-weight-bold">{{ __('diccionario.Enviar avisos de envios de entradas al correo electrónico:') }}</span>
                            <input class="form-control w-100" type="text" name="correoAvisos" id="correoAvisos"
                                value="{{ old('correoAvisos') ? old('correoAvisos') : (isset($diccionarioAula) && $diccionarioAula->destinatarioAvisos()->first() !== null ? $diccionarioAula->destinatarioAvisos()->first()->email : '') }}">
                        </div>
                    </div>

                    {{-- Visibildad del diccianrio de aula --}}
                    <div class="row mt-3 col-12">
                        <div class="col-12">
                            <span class="font-weight-bold" class="form-span">Visibilidad del diccionario de aula</span>
                            <img data-toggle="tooltip" data-placement="top" title="{{  __('diccionario.tooltip_configurar_visibilidad') }}" class="d-inline-block dc-info-ico" src="{{ asset('imagenes/ico-info.svg') }}" >
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="dc-select-grp">
                                <select class="form-control input-group-append" name="visibilidad" id="visibilidad">
                                    <option value="{{ config('ctes.visibilidad.visible') }}"
                                        {{ (old('visibilidad') == '1' ? 'selected' : '' || (isset($diccionarioAula) && config('ctes.visibilidad.visible') == $diccionarioAula->visible_estudiante)) ? 'selected' : '' }}>
                                        {{ __('diccionario.Visible para el alumnado') }}
                                    </option>
                                    <option value="{{ config('ctes.visibilidad.no_visible') }}"
                                        {{ (old('visibilidad') == '0' ? 'selected' : '') || (isset($diccionarioAula) && config('ctes.visibilidad.no_visible') == $diccionarioAula->visible_estudiante) ? 'selected' : '' }}>
                                        {{ __('diccionario.No visible para el alumnado') }}
                                    </option>
                                </select>
                                <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
                            </div>
                        </div>
                    </div>

                    {{-- Permisos de envio --}}
                    <div class="row mt-3 col-12">

                        <div class="col-6 col-sm-12 col-md-9 col-lg-6">
                            <span
                                class="form-span font-weight-bold">{{ __('diccionario.Permisos de envío del diccionario del aula') }}
                                <img data-toggle="tooltip"  data-placement="top" title="{{  __('diccionario.tooltip_configurar_envios') }}"
                                    class="d-inline-block dc-info-ico" src="{{ asset('imagenes/ico-info.svg') }}">
                            </span>
                            <div class="dc-select-grp">
                                <select class="form-control input-group-append" name="habilitarEnvio" id="habilitarEnvio">
                                    <option value="{{ config('ctes.estado_envio_habilitado.activo') }}"
                                        {{ (old('habilitarEnvio') == '1' ? 'selected' : '') || (isset($diccionarioAula) && config('ctes.estado_envio_habilitado.activo') == $diccionarioAula->envios_habilitados) ? 'selected' : '' }}>
                                        {{ __('diccionario.Permitir envíos de entradas desde diccionario personal') }}
                                    </option>
                                    <option value="{{ config('ctes.estado_envio_habilitado.inactivo') }}"
                                        {{ (old('habilitarEnvio') == '0' ? 'selected' : '') || (isset($diccionarioAula) && config('ctes.estado_envio_habilitado.inactivo') == $diccionarioAula->envios_habilitados) ? 'selected' : '' }}>
                                        {{ __('diccionario.No permitir envíos de entradas desde diccionario personal') }}
                                    </option>
                                </select>
                                <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
                            </div>
                        </div>

                        <div class="col-12 col-sm-12 col-md-3">
                            <!-- Campo Fecha de permisos de envío -->
                            <label class="sublabel"
                                for="envioFecha">{{ __('diccionario.Fecha de inicio para los envios') }}</label>
                            <img data-toggle="tooltip"  data-placement="top" title="{{  __('diccionario.tooltip_configurar_envio_entradas') }}"
                                    class="d-inline-block dc-info-ico" src="{{ asset('imagenes/ico-info.svg') }}">
                            {{-- <div data-toggle="tooltip"
                                title=" {{ __('diccionario.tooltip_configurar_envio_entradas') }} " data-placement="top"
                                class="ico-info">
                                <div>?</div>
                            </div> --}}
                            <input type="date" id="envioFecha" name="envioFecha"
                                {{ old('habilitarEnvio') == '0' ? 'disabled' : (isset($diccionarioAula) && $diccionarioAula->envios_habilitados == '0' ? 'disabled' : '') }}
                                value={{ old('envioFecha') ? old('envioFecha') : (isset($diccionarioAula) ? $diccionarioAula->envios_fecha_ini : '') }}>
                        </div>
                    </div>

                    {{-- Visibilidad de comentarios --}}
                    <div class="row mt-3 col-12">
                        <div class="col-6 col-sm-12 col-md-9 col-lg-6">
                            <span class="font-weight-bold" class="form-span">
                                {{ __('diccionario.Configurar visibilidad de comentarios para el alumnado') }}

                                <img data-toggle="tooltip"  data-placement="top" title="{{  __('diccionario.tooltip_configurar_visibilidad_comentarios') }}"
                                    class="d-inline-block dc-info-ico" src="{{ asset('imagenes/ico-info.svg') }}">

                                {{-- <div data-toggle="tooltip"
                                    title=" {{ __('diccionario.tooltip_configurar_visibilidad_comentarios') }} "
                                    data-placement="top" class="ico-info">
                                    <div>?</div>
                                </div> --}}
                            </span>
                            <div class="dc-select-grp">
                                <select class="form-control input-group-append" name="visibilidadComentarios"
                                    id="visibilidadComentarios">
                                    <option value="{{ config('ctes.estado_envio_habilitado.inactivo') }}"
                                        {{ (old('visibilidadComentarios') == '0' ? 'selected' : '') || (isset($diccionarioAula) && config('ctes.estado_envio_habilitado.inactivo') == $diccionarioAula->comentarios_visibles ? 'selected' : '') }}>
                                        {{ __('diccionario.No visible') }}</option>
                                    <option value="{{ config('ctes.estado_envio_habilitado.activo') }}"
                                        {{ (old('visibilidadComentarios') == '1' ? 'selected' : '') || (isset($diccionarioAula) && config('ctes.estado_envio_habilitado.activo') == $diccionarioAula->comentarios_visibles) ? 'selected' : '' }}>
                                        {{ __('diccionario.Visible') }}</option>
                                    <option value="2"
                                        {{ (old('visibilidadComentarios') == '2' ? 'selected' : '') || (isset($diccionarioAula) && '2' == $diccionarioAula->comentarios_visibles ? 'selected' : '') }}>
                                        {{ __('diccionario.Visibles los anteriores a:') }}</option>
                                </select>
                                <img class="d-block" src="{{ asset('imagenes/ico-flecha.svg') }}">
                            </div>
                        </div>
                        <div class="col-12 col-sm-12 col-md-3">
                            <!-- Campo fecha de visibilidad de comentarios anteriores a: -->
                            <label class="sublabel" for="comentariosVisibleFecha">
                                {{ __('diccionario.Fecha para los comentarios') }}
                                <img data-toggle="tooltip" data-placement="top" title="{{  __('diccionario.tooltip_fecha_configurar_visibilidad') }}"
                                    class="d-inline-block dc-info-ico" src="{{ asset('imagenes/ico-info.svg') }}">
                                {{-- <div data-toggle="tooltip"
                                    title=" {{ __('diccionario.tooltip_fecha_configurar_visibilidad') }} "
                                    data-placement="top" class="ico-info">
                                    <div>?</div>
                                </div> --}}
                            </label>

                            <input type="date" id="comentariosVisibleFecha" name="comentariosVisibleFecha"
                                {{ isset($diccionarioAula) && $diccionarioAula->comentarios_visibles != 2 && !old('visibilidadComentarios') ? 'disabled' : '' }}
                                {{ old('visibilidadComentarios') && old('visibilidadComentarios') != 2 ? 'disabled' : '' }}
                                {{ !isset($diccionarioAula) && !old('visibilidadComentarios') ? 'disabled' : '' }}
                                value={{ old('comentariosVisibleFecha') ? old('comentariosVisibleFecha') : (isset($diccionarioAula) ? (isset($diccionarioAula->comentarios_visibles_anteriores_a) ? $diccionarioAula->comentarios_visibles_anteriores_a : '') : '') }}>
                        </div>
                    </div>

                </div>


            </div>
            {{-- fin row --}}

            {{-- participantes --}}
            @if (isset($diccionarioAula))
                <div>
                    <input type="checkbox" checked="checked" id="participantes" name="participantes" class="toggleDiv">
                    <h5><label for="participantes">{{ __('diccionario.Participantes') }}<div class="show-hide"></div>
                        </label></h5>

                    <div>
                        <h6><label>{{ __('diccionario.Estudiantes') }}</span></h6>
                        <label class="sublabel"
                            for="estudiantes">{{ __('diccionario.Todos los que dispongan del código de diccionario') }}</label>

                        <img data-toggle="modal" data-target="#estudiantesModal" id="verEstudiantes"
                            src="{{ asset('imagenes/ico-p-ojo.svg') }}" alt="audio" class="dc-ico-audio cursor-pointer">
                        <!-- Modal -->
                        <div class="modal fade" id="estudiantesModal" tabindex="-1" role="dialog"
                            aria-labelledby="estudiantesLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="estudiantesLabel">
                                            {{ __('diccionario.Estudiantes unidos al diccionario') }}<nav></nav>
                                        </h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('diccionario.Nombre') }}</th>
                                                    <th>{{ __('diccionario.Apellidos') }}</th>
                                                    <th>{{ __('diccionario.Estado') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @include('diccionario.participantesAjax', [
                                                'diccionarioAula' => $diccionarioAula,
                                                'participantes' => $diccionarioAula->participantes()
                                                                        ->whereHas('persona.personaUser', function ($query) {
                                                                            $query->where('role_id', config('ctes.rol.alumno'));
                                                                        })->get(),
                                                'profesorado' => false
                                                ])
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal">{{ __('diccionario.boton_volver') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Modal -->
                        <h6><label>{{ __('diccionario.Profesorado invitado') }}</label></h6>
                        <div class="col-12 col-md-8">
                            <table class="table fixed_header" id="tabla-profesorado">
                                <thead>
                                    <tr>
                                        <th>{{ __('diccionario.Nombre') }}</th>
                                        <th>{{ __('diccionario.Apellidos') }}</th>
                                        <th>{{ __('diccionario.Estado') }}</th>
                                        <th>{{ __('diccionario.Administracion') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                  @include('diccionario.participantesAjax', [
                                  'diccionarioAula' => $diccionarioAula,
                                  'participantes' => $diccionarioAula->participantes()
                                                        ->whereHas('persona.personaUser', function ($query) {
                                                            $query->where('role_id', config('ctes.rol.docente'));
                                                        })->get(),
                                  'profesorado' => true                      
                                  ])
                                </tbody>
                            </table>
                            <div class="row text-right d-block">
                                <span
                                    style="margin-right: 1rem">{{ __('diccionario.Añadir profesorado invitado') }}</span><img
                                    class="cursor-pointer" src="{{ asset('imagenes/ico-mas.svg') }}" data-toggle="modal"
                                    data-target="#docentesModal" id="invitarDocente">
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row text-center col-12 col-sm-12 mt-5">
        @isset($diccionarioAula)
            <button type="button" class="btn btn-danger mx-auto" id="borrarDicButton"> {{ __('diccionario.eliminar_diccionario') }}
            </button>
        @endisset
                <button type="button" class="btn btn-primary mx-auto" data-toggle="modal" data-target="#saveModal"
                    id="crearButton">{{ isset($diccionarioAula) ? __('diccionario.Modificar diccionario') : __('diccionario.Crear diccionario') }}</button>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="saveModal" tabindex="-1" role="dialog" aria-labelledby="saveModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="saveModalLabel">{{ __('diccionario.Confirmación') }}<nav></nav>
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="padding-bottom: 25px">
                            <div class="row" style="margin-bottom:25px">
                                <div class="col-12 col-sm-12">
                                    {{ isset($diccionarioAula) ? __('Confirme la modificación del diccionario de aula.') : __('Confirme la creación del diccionario de aula.') }}
                                </div>
                            </div>
                            <div class="row">

                                <div class="form-group col-md">
                                    <label for="cursoEscolar">CURSO ESCOLAR</label>

                                    @isset($diccionarioAula)
                                        <input class="form-control text-center" type="text" name="" id=""                                        
                                            value="{{ getStrCursoByAnoIni($diccionarioAula->ano_ini_curso_escolar) }}" readonly>
                                        <input type="hidden" name="ano_ini_curso_escolar" value="{{ $diccionarioAula->ano_ini_curso_escolar }}">
                                    @else
                                        <input class="form-control text-center" type="text" name="" id="" value="{{ getFormattedCursoEscolar(time()) }}" readonly>
                                        <input type="hidden" name="ano_ini_curso_escolar" value="{{ getAnoIniCursoEscolar(new Datetime()) }}">
                                    @endisset

                                </div>

                                <div class="form-group offset-md-1 col-md">
                                    <label for="cursoEscolar">CÓDIGO</label>
                                    <div class="form-row">
                                        <input class="form-control text-center" type="text" name="codigo" id="codigo"
                                            value="{{ $diccionarioAula->codigo ?? 0 }}" readonly>
                                    </div>
                                </div>

                                <input class="form-control text-center" type="hidden" name="nivelEstudioFinal"
                                    id="nivelEstudioFinal" value="nivelEstudio" readonly>
                                <input class="form-control text-center" type="hidden" name="grupoFinal" id="grupoFinal"
                                    value="grupo" readonly>
                                <input class="form-control text-center" type="hidden" name="codigoAleatorio"
                                    id="codigoAleatorio" value="{{ $diccionarioAula->codigo ?? 0 }}" readonly>

                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="submit" data-toggle="modal" data-target="#saveModal"
                                value="{{ isset($diccionarioAula) ? __('diccionario.Guardar') : __('diccionario.Crear') }}"
                                class="btn btn-primary">
                            <button type="button" class="btn btn-danger"
                                data-dismiss="modal">@lang('diccionario.boton_cancelar')</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="importarEntradas" tabindex="-1" role="dialog"
                aria-labelledby="importarEntradasLabel" aria-hidden="true">
                <div class="modal-dialog  modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="importarEntradasLabel">
                                {{ __('diccionario.Importar entradas') }}
                                <nav></nav>
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center" style="padding-bottom: 25px">
                            
                            <a data-toggle="collapse" href="#dicActuales">
                            <h5 style="text-align:left;" class="cursor-pointer" >
                                {{ __('diccionario.importar_DiccionariosActivos') }}
                                <div class="show-hide"></div>
                            </h5>
                            </a>
                            <div id="dicActuales" class="collapse show form-group form-enviar-diccionarios col-md-12" >
                                <ul>
                                    @include('layouts/partials/components/listaDicAula-customRadio', [
                                    'listaDiccionariosAula' => $datos->listaDiccionariosAula,
                                    'checkbox_name' =>'importarRadio'
                                    ])
                                    
                                </ul>
                            </div>
                            
                            @if( $datos->listaDiccionariosAulaNoVigentes->count() > 0  )
                            <a data-toggle="collapse" href="#dicAntiguos">
                            <h5 style="text-align:left;" class="cursor-pointer">
                                {{ __('diccionario.importar_DiccionariosInactivos') }}
                                <div class="show-hide"></div>
                            </h5>
                            </a>
                            <div id="dicAntiguos" class="form-group form-enviar-diccionarios col-md-12 collapse">
                                <ul>
                                    @include('layouts/partials/components/listaDicAula-customRadio', [
                                        'listaDiccionariosAula' => $datos->listaDiccionariosAulaNoVigentes,
                                        'checkbox_name' =>'importarRadio'
                                        ])     
                                </ul>
                            </div>
                            @endif

                        </div>
                        <div class="modal-footer">
                            <input type="button" data-event="guardarImportar" data-toggle="modal"
                                data-target="#importarEntradas" value="{{ __('diccionario.Importar entradas') }}"
                                class="btn btn-primary">
                            <button type="button" class="btn btn-danger"
                                data-dismiss="modal">@lang('diccionario.boton_cancelar')</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- modal borrar dic --}}
            <div class="modal fade" id="modalDeletePeroPublicadas" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">

                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                {{ __('diccionario.eliminar_diccionario') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center">
                            {{ __('diccionario.eliminar_diccioanario__pubilcadas') }}
                        </div>
                        <div class="modal-footer text-center">
                            <button type="button" class="btn btn-primary" data-dismiss="modal">
                                {{ __('diccionario.boton_cancelar') }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal fade" id="modalDeletePeroEnviadas" tabindex="-1" role="dialog" aria-hidden="true" >
                <div class="modal-dialog modal-dialog-centered" role="document">

                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                        {{ __('diccionario.eliminar_diccionario') }}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body text-center">
                        {{ __('diccionario.eliminar_diccioanario__enviadas') }}
                    </div>
                    <div class="modal-footer text-center">

                        <button id="BorrarDiccionarioBtnConfirmar" type="button" class="btn btn-danger" data-dismiss="modal">
                        {{ __('diccionario.eliminar_diccionario') }}
                        </button>
                        &nbsp;

                        <button type="button" class="btn btn-primary" data-dismiss="modal">
                        {{ __('diccionario.boton_cancelar') }}
                        </button>
                    </div>
                    </div>

                </div>
            </div>

            <div class="modal fade" id="modalDelete" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">

                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                {{ __('diccionario.eliminar_diccionario') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center">
                            {{-- {{ __('diccionario.eliminar_diccioanario__confirmar') }} --}}
                            <p id="tieneParticipantes" class="d-none">{{ __('diccionario.eliminar_diccionario__participantes_unidos') }}</p>
                            {{ __('diccionario.eliminar_diccionario__desea_eliminar') }}
                        </div>
                        <div class="modal-footer text-center">
                            <button id="BorrarDiccionarioBtnConfirmarOk" type="button" class="btn btn-danger" data-dismiss="modal">
                                {{ __('diccionario.eliminar_diccionario') }}
                            </button>
                            &nbsp;
                            <button type="button" class="btn btn-primary" data-dismiss="modal">
                                {{ __('diccionario.boton_cancelar') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>


        </form>
    </div>

    @if (isset($diccionarioAula))
        <!-- Modal -->
        @include('layouts.partials.components.modal-formulario-invitar-docente',
        ['diccionarioAula'=>$diccionarioAula ])
        <!-- End Modal -->
    @endif

@endsection

@section('scripts')
<script>
// no borrar : 
function handleChange(el) {
    // console.log('[handleChange(el)] el', el );
    // descheckear otros diccionarios
    $('.libroDiccionarioVerde input').prop('checked',false);
    $(el).prop('checked',true)
}    

function enableVigencia() {
    $('#vigencia').prop('disabled', false);
    $('#vigencia').parent().removeClass('disabled');
    // $('#vigencia').prop('disabled', false);
}
function updateVigencia(){
    $('#hiddenVigencia').val($('#vigencia').val());
}
$('body').on('click', '[data-action="deshabilitarParticipanteAdmin"]', function(ev){
        // console.log("habilitar participante ", $(ev.currentTarget).data('id'));
        //we will send data and recive data fom our AjaxController
        $.ajax({
           url:location.href+'/deshabilitarParticipanteAdmin/'+$(ev.currentTarget).data('id'),
           data:{},
           type:'post',
           success: function (response) {
                // $( ev.currentTarget() ).parents('tbody').html(response);
                // console.log($( ev.currentTarget ));
                $( ev.currentTarget ).parents('tbody').html(response);
                $('.tooltip').hide()
                $('[data-toggle="tooltip"]').tooltip({
                    trigger : 'hover'
                })
           },
           statusCode: {
              404: function(response) {
                 alert('web not found');
              }
           },
           error:function(x,xs,xt){
               //nos dara el error si es que hay alguno
               console.log('error intentado deshabilitar participante',x);
               // console.log('xs',xs);
               // console.log('xt',xt);
              // window.open(JSON.stringify(x));
           }
        });
    });

    $('body').on('click', '[data-action="habilitarParticipanteAdmin"]',function(ev){
        //we will send data and recive data fom our AjaxController
        $.ajax({
           url:location.href+'/habilitarParticipanteAdmin/'+$(ev.currentTarget).data('id'),
           data:{},
           type:'post',
           success: function (response) {
                // console.log($( ev.currentTarget ));
                $(ev.currentTarget).parents('tbody').html(response);
                $('.tooltip').hide()
                $('[data-toggle="tooltip"]').tooltip({
                    trigger : 'hover'
                })
           },
           statusCode: {
              404: function(response) {
                 alert('web not found');
              }
           },
           error:function(x,xs,xt){
               //nos dara el error si es que hay alguno
               // window.open(JSON.stringify(x));
               console.log('error intentado habilitar participante',x);
           }
        });
    });
</script>
@endsection