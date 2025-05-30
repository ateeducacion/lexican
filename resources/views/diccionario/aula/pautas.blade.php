@if(isset($datos->dicAulaActivo))
  <div class="aside-panel dc-pautas">
    <input type="checkbox" class="check" id="{{$pauta_chk_id}}" name="{{$pauta_chk_id}}">
    
    <label class="position-relative" style="background: red" for="{{$pauta_chk_id}}">
      <div class="w-100 cursor-pointer colored td-underline">
        <a>{{ __('diccionario.Pautas') }}</a>
      </div>
    </label>    
    
    {{-- Espacio gris a la derecha del panel --}}
    <label class="close-menu" for="{{$pauta_chk_id}}"></label>

    {{-- panel con pautas --}}
    <aside id="pautas-show" class="drawer-menu">      
      <div dir="ltr" class="mdc-drawer__content">
          <div class="container d-flex justify-content-between align-items-center">
            <h2>{{__('diccionario.Pautas').__(' de ').'"'.$datos->dicAulaActivo->titulo.'"'}}</h2>
            <label class="cursor-pointer pull-right" for="{{$pauta_chk_id}}">
              <img src="{{ asset('imagenes/ico-bocadillo-plus.svg') }}" class="fa-rotate-90 rotaded" alt="cerrar">
            </label>
          </div>
          <hr class="mdc-list-divider">
          <div class="bloqueCreditos" >
            <a class='hidefocus' href="#" rel="noopener noreferrer"></a>
            <textarea class="textarea-tiny-read-only w-100 h-100" >
              {{$datos->dicAulaActivo->pauta->texto}}
            </textarea>
          </div>
        </div>
    </aside>
  </div>
@endif
