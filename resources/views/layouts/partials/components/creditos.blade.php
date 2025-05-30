<div id="dc-creditos">
  <input type="checkbox" class="check" id="checked">
  <label class="menu-btn creditos" for="checked">
    <span class="menu-btn__text"> {{ __('diccionario.creditos', ['to'=> date("Y") ]) }}</span>
  </label>
  <label class="menu-btn enlaces">
    <span class="menu-enlaces">
      <a href="{{ __('diccionario.aviso_legal') }}" target="_blank">Aviso Legal</a> - 
      <a href="{{ __('diccionario.politica_privacidad') }}" target="_blank">Política de privacidad</a>
    </span>
  </label>

  <label class="close-menu" for="checked"></label>
  <aside class="drawer-menu">
    <div dir="ltr" class="mdc-drawer__content">
      <div class="container d-flex justify-content-between align-items-center">
        <h2>Créditos</h2>
        <label class="cursor-pointer pull-right" for="checked">
          <img src="{{ asset('imagenes/ico-bocadillo-plus.svg') }}" class="fa-rotate-90 rotaded" alt="cerrar">
        </label>
      </div>
      <hr class="mdc-list-divider">
      <div class="bloqueCreditos">
        <a class='hidefocus' href="#" rel="noopener noreferrer"></a>
        <div>
          <div>
            <img class="logoCreditos nivel1" alt="Gobierno de canarias" id="logoConsejeria" src="{{ asset('imagenes/creditos/01_Gob de Canarias DGOIPE-CEUCD.plain.svg') }}">
            
            <img class="logoCreditos nivel1" alt="Unión europea" id="logoUE" src="{{ asset('imagenes/creditos/UNION-EUROPEA.png') }}">
          </div>
          <p>
            Los contenidos y programas que constituyen esta obra son propiedad del Gobierno de Canarias que ha promovido su creación y desarrollo con el propósito de que la comunidad educativa haga libre uso de los mismos.
          </p>
          
          <div>
            <img id="logoUcticee" class="logoCreditos nivel2" src="{{ asset('imagenes/creditos/UCTICEE.png') }}" alt="ucticee">
          </div>
          
          <div class="content">
            <div class="logosSeparados row">
              <div class="col">
                <img class="logoCreditos nivel3" alt="ate" src="{{ asset('imagenes/creditos//ATE.png') }}">
                <img class="logoCreditos nivel3" alt="canarias-avanza" src="{{ asset('imagenes/creditos//canarias-avanza.png') }}">
              </div>
              
              <img class="col logoCreditos nivel3" alt="Fondo Europeo De Desarrollo Regional" src="{{ asset('imagenes/creditos/fondo-europeo.plain.svg') }}">
            </div>
          </div>
          
          <div>
            <p>
              <strong>Empresas que han intervenido en el desarrollo de esta obra:</strong>
            </p>
          </div>
          
          <p>
            <img class="logoCreditos nivel4" alt="netex" src="{{ asset('imagenes/creditos//NETEX.png') }}">
            <img id="logoAltia" class="logoCreditos nivel4" alt="altia" src="{{ asset('imagenes/creditos//ALTIA.png') }}">
            <br>
            <br>{{ __('diccionario.creditos', ['to'=> date("Y") ]) }}
          </p>
          
        </div>
      </div>
    </div>
  </aside>
</div>