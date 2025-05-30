{{-- {{ dd($entrada) }}
{{ dd($entrada->acepciones ) }} --}}
<!-- entradaAula -->
<div class="dc-entrada-container">
    <div class="dc-entrada-top row">
        <div class="dc-entrada-titulo col-6 col-xs-6 col-sm-8 col-lg-9 col-xl-10">
                {{ $entrada->entrada }}
                {{-- @can( 'createDicAula', App\DicAula::class ) --}}
        </div>
        <div class="dc-entrada-botones-top col-6 col-xs-6 col-sm-4 col-lg-3 col-xl-2 dc-entrada-botones d-flex">
            @can('esCoordinador', $entrada->dicAula )
            <a href="{{ route('aula.entrada.get', [$entrada->dicAula->id , $entrada->id] ) }}" class="icon">
                <img 
                    data-toggle="tooltip" data-placement="top" title="{{ __('diccionario.icono_editar') }}"
                    src="{{ asset('/imagenes/ico-edit.svg') }}" alt="{{ __('diccionario.icono_editar') }}"
                >
            </a>

            @include('layouts.partials.components.botones.entrada.publicar', [
                            'diccionario' => $entrada->dpEnvio->dicAula,
                            'entrada'=> $entrada,
                            'estudiante' => $entrada->dpEntrada()->first()->dpDiccionario->persona
                    ])

            @endcan

        </div>

    </div>
    {{-- acepciones --}}
    <div class="dc-acepciones">
        @foreach( $entrada->acepciones as $acepcion )
            @include('layouts.partials.consulta.acepcionAula', $acepcion)
        @endforeach        
    </div>
    {{-- fin acepciones --}}
    <div class="dc-entrada-bottom row">
        @if( count($entrada->acepciones)>1 )
            <div class="w-100 text-right">
                <img class="desplegar cursor-pointer" src="{{ asset('/imagenes/ico-desplegar.svg') }}" alt="desplegar">
            </div>
            <div class="w-100 text-right d-none">
                <img class="plegar flip-vertically cursor-pointer" src="{{ asset('/imagenes/ico-desplegar.svg') }}" alt="plegar">
            </div>
        @endif
    </div>

</div>