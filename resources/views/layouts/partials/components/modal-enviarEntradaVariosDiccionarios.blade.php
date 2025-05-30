@extends('layouts/partials/components/modal-simpleyield')


@section('modal-title')
{{ __('diccionario.modal_enviar_entrada_title') }}
@overwrite

    @section('modal-body')

    <p>@lang(__('diccionario.envio_seleccionadiccionarios_entrada', ['entrada' => $entrada->entrada]))</p>

    <form id="{{ 'form' . $id }}" class="my-form form-enviar-diccionarios" method="POST" action="{{ route('entrada.send', ['diccionario_id'=> $entrada->dic_personal_id, 'entrada_id'=> $entrada->id]) }}" enctype="multipart/form-data">
        @csrf

        <input type="hidden" id="listaDiccionariosAula" name="listaDiccionariosAula" value="" />

        <div class="form-row">
            <br />


            {{-- Listado de diccionarios de aula a los que el user está unido --}}
            <div class="form-group col-md-12">
                <ul>
                    @foreach($listaDiccionariosAula as $diccionarioAula)
                        @if($diccionarioAula->estadoActual == config('ctes.estado_envio_habilitado.activo'))
                            <li class="libroDiccionarioVerde" 
                                data-toggle="tooltip" 
                                title="{{ __('diccionario.coordinador') . ': ' . $diccionarioAula->profesor->nombreCompleto() }}"
                                data-placement="top" 
                            >
                            @else
                            <li class="libroDiccionarioGris" data-toggle="tooltip" title="{{ __('diccionario.tooltip_envios_deshabilitados') }} " data-placement="bottom" >
                        @endif
                        @if($diccionarioAula->estadoActual==config('ctes.estado_envio_habilitado.activo'))
                            <input type="checkbox" name="checkbox" id="chk_{{ $diccionarioAula->id }}" autocomplete="off" onchange='handleChange(this)' />
                            <label for="chk_{{ $diccionarioAula->id }}">
                                <img class="ico-libro-verde" src="" alt="{{ $diccionarioAula->titulo }}" />
                            </label>
                        @else
                            <label for="chk_{{ $diccionarioAula->id }}">
                                <img src="{{ asset('imagenes/ico-libro-gris.svg') }}" />
                            </label>
                        @endif
                        <span>{{ $diccionarioAula->titulo }}<br /></span>
                        
                        {{-- <span>{{ $diccionarioAula->profesor->nombreCompleto() }}</span> --}}
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>

        <div class="form-row">
            <br />
        </div>

        <div class="form-row">
            {{-- Mensaje de ultimo envio si lo ubiese --}}
            {{-- por diccionario --}}
            <div class="offset-md-1 col-md-11 text-left ">
            
                @foreach($listaDiccionariosAula->sortBy('titulo') as $diccionarioAula)

                <p class="dc-info-ultimo-envio d-none" style="color: var(--primary)" id="ultimoEnvio{{ $diccionarioAula->id }}">
                    @if ($diccionarioAula->ultimoEnvioPalabra->count()>0)
                        {{ __('diccionario.diccionario_aula') }} "{{ $diccionarioAula->titulo }}", {{ __('diccionario.envio_entrada_fecha_anterior') }} 
                        {{ \Carbon\Carbon::parse($diccionarioAula->ultimoEnvioPalabra->last()->updated_at)->format('d/m/Y') }}.
                    @else
                        {{-- {{ __('diccionario.diccionario_aula') }} "{{ $diccionarioAula->titulo }}", {{ __('diccionario.envio_entrada_no_enviado') }}. --}}
                    @endif
                </p>
                @endforeach                        
            </div>
        </div>

        <div class="form-row">
            <br />
        </div>

        {{-- Botones del formulario --}}
        {{-- <div class="text-center">
                        <button type="submit" class="btn btn-primary">@lang('diccionario.acepcion_enviar')</button>
                        <a href="{{ route('diccionario.send', ['diccionario_id' => $entrada->dic_personal_id]) }}" class="btn btn-danger">@lang('diccionario.acepcion_cancelar')</a>
        </div> --}}

    </form>

    @overwrite


        @section('modal-footer')
        <button type="button" class="btn btn-primary" onclick="{{ 'aceptar' . $id }}()">
            @lang(__('diccionario.boton_aceptar'))
        </button>
        <button type="button" class="btn btn-secondary btn-danger" data-dismiss="modal">
            @lang(__('diccionario.boton_cancelar'))
        </button>
        @overwrite

            @section('modal-scripts')
            <script>
                $('[data-toggle="tooltip"]').tooltip();
                // Función que recorre los checkboxes y si está marcados concatena sus id separándolo por comas y los guarda en el hidden listaDiccionariosAula para que luego se mande esto por POST
                function handleChange(checkbox) {
                    let listaCheckboxes = document.getElementsByName('checkbox');
                    let listaDiccionariosAula = document.getElementById('listaDiccionariosAula');
                    listaDiccionariosAula.value = '';
                    [...listaCheckboxes].forEach(element => {
                        var eleId = element.id.substr(4);
                        if (element.checked) {
                            $('#ultimoEnvio' + eleId  ).removeClass('d-none');
                            if (listaDiccionariosAula.value == "") {
                                listaDiccionariosAula.value = eleId ;
                            } else {
                                listaDiccionariosAula.value = listaDiccionariosAula.value + ',' + eleId ;
                            }
                        } else {
                            $('#ultimoEnvio' + eleId  ).addClass('d-none');
                        }
                    });
                }

                // Si sólo hay un diccionario verde lo marca
                jQuery(function($) {
                    if ($('[id^=chk_]').length == 1) {
                        $('[id^=chk_]').prop('checked', true);
                        handleChange($('[id^=chk_]')[0])
                    }
                });

                //enviar form
                {{ 'function aceptar'.$id. '() {' }}
                    document.getElementById('{{ 'form' . $id }}').submit();
                }
            </script>
            @overwrite