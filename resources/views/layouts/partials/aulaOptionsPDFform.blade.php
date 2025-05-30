

<!-- dpOptiosnPDFform -->
<form class="dc-input-group" target="_blank" style="width: 100%" method="POST"
        action="{{ $datos->action }}">
    @csrf

    <h5><label>{{ __('diccionario.entradas') }}<label></h5>
    {{-- 
    <div class="form-row">
        <div class="form-group col-md-4">
            <div>
                <label>
                    <input type="checkbox" name="showHidden" value="1"> {{ __('diccionario.exportarpdf__mostrar_ocultas') }}
                </label>
            </div>
        </div>
    </div> 
    --}}

    <div>
        <div>
            <input id="exportOnlyTagged" type="checkbox" name="exportOnlyTagged" value="1" >
            <label for="exportOnlyTagged">
                {{ __('diccionario.exportarpdf__solo_etiquetadas') }}
            </label>            
        </div>
        <div class="p-3">
            <div id="tematicas" class="d-none">
            {{-- mostrar solo si checkbox esta selecionado en el formulario de edicion de acepciones --}}
            @include('layouts/partials/dpAcepcion/tematicas', ['datos' => $datos ])
            </div>
        </div>


        
        <div class="modal-footer" style="background-color: transparent;padding-bottom:0">
            <input type="submit" data-toggle="modal" data-target="#saveModal"
                value="{{ __('diccionario.exportarpdf__confirmar_exportar') }}"
                class="btn btn-primary">
                
                
            <button type="button" class="btn btn-danger" onclick="window.location.href='{{ url()->previous() }}'" 
                data-dismiss="modal">
                @lang('diccionario.boton_cancelar')
            </button>
        </div>

    </div>        
</form>