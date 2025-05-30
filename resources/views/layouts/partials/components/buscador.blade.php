<div class="row">
<div class="dc-home-buscador col-12 col-md-8 offset-md-2 ">
<form id="formBuscador" method="POST" 
    @if( isset($aula) && $aula = true ) 
    action="{{ route('aula.consulta.busqueda') }}" 
    @else
      action="{{ route('personal.consulta.busqueda') }}" 
    @endif
    enctype="multipart/form-data">
  @csrf
  <div class="mb-3">
    <div class="typeahead__container">
      <div class="typeahead__field">
        <div class="typeahead__query input-group dc-input-group">
          <input autocomplete="off" name="entrada_entrada" id="entrada_entrada" type="text" class="form-control js-typeahead-input" 
            placeholder="{{__('diccionario.Buscar')}}" aria-label="{{__('diccionario.Buscar')}}"
            value={{ $datos->consulta??'' }}
            >
          
          <div class="input-group-append p-0 m-0" style="width:10rem;margin-left: 4px !important;">
           {{-- btn desplegar filtro categorias --}}
           <div id="labelCategorias"  style="border-left: 1px solid #ccc;"> 
            {{-- boton filtarr por categorias --}}
            <a 
            {{-- style="position: relative;top:0.3rem" --}}
            class="text-decoration-none  d-inline-block" href="#"><div class="dc-TematicasBtn align-middle m-0">
                  <div class="d-inline-block mr-2">
                      @lang('diccionario.buscador_filtrarCategorias')
                  </div>
                  <img 
                  
                      class="dpAcepcionAnadirImg" 
                      src="{{ asset('/imagenes/ico-desplegar-bn.svg') }}" 
                      alt="@lang('diccionario.buscador_filtrarCategorias')">
              </div></a>
              {{-- fin boton filtar por categorias  --}}
            </div>
            
            {{-- fin --}}

            <button type="submit"><img src="{{ asset('imagenes/lupa.svg')}}"></button>
          </div>
        </div>
      </div>
      <div id="selectCategorias" class="d-none">
        <div class="col-12 col-md-6 col-lg-5 col-xl-4">
        @foreach($datos->listaTematicasDisponibles as $tematica)
          <span data-id="{{ $tematica->id }}" class="cursor-pointer badge badge-secondary">{{ $tematica->descripcion }}</span>  
        @endforeach
        </div>
        </div>
    </div>
    
    <div>
      <div class="infoLabel">{{ __('diccionario.buscador_filtrandoCategorias') }}</div>
      <div class="info" ></div>
      <div class="noFilter cursor-pointer d-none badge badge-dark">{{ __('diccionario.buscador_borrarFiltros') }}</div>
    </div>
    {{-- <input hidden multiple id="list_tematica_id" name="list_tematica_id[]"> --}}
    <div class="d-none">
      <input type="hidden" name='tematicas_ids' id="tematicas_ids"> 
      <select multiple id="list_tematica_id" name="list_tematica_id[]" class="input-group ">      
            @foreach($datos->listaTematicasDisponibles as $tematica)
            <option value="{{ $tematica->id }}" 
              @isset($datos->list_tematica_id)                
              {{ ( in_array($tematica->id, $datos->list_tematica_id)  ? "selected":"") }}
              @endisset
              >{{ $tematica->descripcion }}</option>
            @endforeach
      </select>
    </div>
  </div>
</form>
</div>
</div>  

@section('scripts')
<script>
  @if( isset($aula) && $aula = true ) 
    window.routeSuggestions = '{{ route('aula.consulta.all.json') }}';
  @else
    window.routeSuggestions = '{{ route('personal.consulta.all.json') }}';
  @endif
</script>
@append