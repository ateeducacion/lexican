{{-- inner html modal-publicar-listado --}}
{{-- <div id="modal-publicar-listado" class="modal-confirmar dc-emergente"> --}}
<div>
    <p class="mb-0">
        <strong>{{ __('diccionario.entradaPublicarListado_publicarMultiple', [ 'diccionario'=> $dicAula->titulo ]) }}</strong>
    </p>
    <ul>
        {{-- n resultados busqueda --}}
        <li>{{ __('diccionario.entradaPublicarListado_entradasBusqueda', [ 'n'=> $n_entradas ]) }}</li>
        @if($n_entradasOk>0)
        {{-- n entradas que se publicaran --}}
        <li>{{ __('diccionario.entradaPublicarListado_entradasOk', [ 'n'=> $n_entradasOk ]) }}</li>
        @endif
    </ul>
</div>
@if( !($n_entradasOk>0) )
    <div>
        <p>{{ __('diccionario.entradaPublicarListado_noHayEntradas') }}</p>
    </div>
@endif
@if( $n_entradasError>0 )
    <div>
        {{-- Detalle de entradas que no se publicaran --}}
        <p class="mb-0">
            <strong>{{ __('diccionario.entradaPublicarListado_noSePublicaran') }}</strong>
            @if( $yaPublicadas && count($yaPublicadas)>0 )
                <ul>
                    <li>{{ __('diccionario.entradaPublicarListado_yaPublicadas', ['n'=> count($yaPublicadas) ]) }}
                        @foreach($yaPublicadas as $e)
                        <a href="#{{ $e->entrada }}">{{ $e->entrada }}</a>{{ ($loop->last)? '.' : ',' }}
                        @endforeach
                    </li>
            @endif 
            {{-- Entradas repetidas en el listado --}}
            @if( $duplicadasEnListado && count($duplicadasEnListado)>0 )
                <li>{{ __('diccionario.entradaPublicarListado_duplicadaEnListado', ['n'=> count($duplicadasEnListado)]) }}
                    @foreach($duplicadasEnListado as $e)
                        <a href="#{{ $e->entrada }}">{{ $e->entrada }}</a>{{ ($loop->last)? '.' : ',' }}
                    @endforeach
                </li>
            @endif
        </p>
    </div>
@endif
{{-- Botones --}}
<div class="text-center">
    @if($n_entradasOk>0)
    <button 
        class="mr-2 btn btn-primary" 
        data-action="publicar-todas" 
        data-url="{{ route('diccionarioaula.publicarListado', $dicAula->id ) }}" 
        data-enviosid="{{ join(',' ,comprobarPublicarListadoEntradas( $entradas, $dicAula->id )->okIds) }}" 
        type="button">
        {{ __('diccionario.modal_entrada_publicar_tooltip') }}
    </button>
    <button data-action="close" class="btn btn-secondary btn-danger" type="button">
        {{ __('diccionario.boton_cancelar') }}
    </button>
    @else 
    <button data-action="close" class="btn btn-primary" type="button">{{ __('diccionario.boton_cerrar') }}</button>
    @endif        
</div>
{{-- </div> --}}