<div class="last-page" style="position:relative">
  <img src="{{ public_path() }}/imagenes/pdf02_Contra_Portada.png" width="700" />
  <div class="contraportada-descripcion">
    <h1> {{ mb_strtoupper(__('diccionario.contraportada_aula_titulo')) }}</h1>
    <p class="mt-2">{{ __('diccionario.contraportada_descripcion') }}</p>
  </div>
    <div class="box-contra" >
        {{-- <h3>Creditos</h3> --}}
      <h3>{{ mb_strtoupper($datos['titulo']) }}</h3>
      {{-- <h4>{{ __('diccionario.coordinador') }}:</h4> --}}
      <h4>{{ __('diccionario.coordinadores') }}</h4>
      <span class="creditos-nombres" >{{ $datos['nombreCompleto'] }}</span>
      @foreach($datos['participantes'] as $key => $value)
        @if( $value->rol_diccionario_id == config('ctes.rol.docente') )
        <?php $particpante= $value->persona()->first()->nombre.' '.$value->persona()->first()->apellidos ?>
        @if( $particpante !== $datos['nombreCompleto'])            
                <span class="creditos-nombres" >
                    {{ $particpante }}
                </span>
              @endif
        @endif 
      @endforeach
      

      <h4>{{ __('diccionario.Participantes') }}</h4>
      <div>
          @foreach($datos['participantes'] as $key => $value)
            @if( $value->rol_diccionario_id != config('ctes.rol.docente') )
              <?php $particpante= $value->persona()->first()->nombre.' '.$value->persona()->first()->apellidos ?>
              @if( $particpante !== $datos['nombreCompleto'])
            
                <span class="creditos-nombres" >
                    {{ $particpante }}
                </span>
              @endif
            @endif

          {{-- haciendo bulto    (nombres inventados)
          <span class="creditos-nombres"> Iván	Pérez	Gutiérrez	    </span>
          <span class="creditos-nombres"> Ana María	Gaitán	Espada	    </span>
          <span class="creditos-nombres"> Marta	Fernández	Martínez	</span>
          <span class="creditos-nombres"> María	García	González	    </span>
          <span class="creditos-nombres"> Adrián	Abad	Bermejo	        </span>
          <span class="creditos-nombres"> José	Alonso	García	        </span>
          <span class="creditos-nombres"> Laura	Radu	Ruiz	        </span>
          <span class="creditos-nombres"> Rosario	Fornes	Mateo	        </span>
          <span class="creditos-nombres"> Javier	Ramírez	Rodríguez	    </span>
          <span class="creditos-nombres"> Pilar	Arjona	Vera	        </span>
            --}}
          {{-- fin --}}
          @endforeach
      </div>
  </div>
</div>