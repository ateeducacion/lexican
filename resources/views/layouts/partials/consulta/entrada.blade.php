<!-- partials/consulta/entrada.blade.php -->

<div class="dc-entrada-container">
    <div class="dc-entrada-top row">
        <div class="dc-entrada-titulo col-6 col-xs-6 col-sm-8 col-lg-9 col-xl-10">
            <a href="{{ route('entrada.get', [$entrada->dic_personal_id,$entrada->id]) }}">
                <div>{{ $entrada->entrada }}</div>
            </a>
        </div>
        <div class="dc-entrada-botones-top col-6 col-xs-6 col-sm-4 col-lg-3 col-xl-2 dc-entrada-botones d-flex">
            @include('layouts.partials.components.botones.entrada.get', [
                'diccionario_id' => $entrada->dic_personal_id,
                'entrada_id'=> $entrada->id])
            {{-- ocultar --}}
            @include('layouts.partials.components.botones.entrada.ocultar', [
                'entrada'=>$entrada,
            ])
            {{-- enviar a diccionario --}}
            @include('layouts.partials.components.botones.entrada.enviar', [
                // 'entrada'=> $entrada,
                // 'listaDiccionariosAula'=> $listaDiccionariosAula,
                // 'centro_denominacion' => $centro_denominacion,
                // 'curso_escolar' => $curso_escolar,
                // 'nivelEstudios' => $nivelEstudios
                'id' => 'uid_'.uniqid(),
                'img' => asset('/imagenes/ico-enviar.svg'),
                'tooltip' => __('diccionario.modal_enviar_entrada_un_diccionario_tooltip'),
            ])
            {{-- ver comentarios --}}
            @include('layouts.partials.components.botones.entrada.verComentariosEntrada', [
            // 'diccionario_id' => $entrada->dic_personal_id,
            'entrada'=> $entrada ,
            // 'nivelEstudios' => $nivelEstudios
            'id' => 'uid_'.uniqid(),
            'img' => asset('/imagenes/ico-coment.svg'),
            'tooltip' => __('diccionario.icono_vercomentarios'),
            ])

        </div>
        <div class="dc-entrada-top-right">

        </div>
    </div>
    {{-- acepciones --}}
    <div class="dc-acepciones">
        @foreach($entrada->dpAcepciones('asc')->get() as $acepcion)
            @include('layouts.partials.consulta.acepcion', $acepcion)
        @endforeach
    </div>
    {{-- fin acepciones --}}
    <div class="dc-entrada-bottom row">
        @if($entrada->dpAcepciones('asc')->count()>1)
            <div class="w-100 text-right">
                <img class="desplegar cursor-pointer" src="{{ asset('/imagenes/ico-desplegar.svg') }}" alt="desplegar">
            </div>
            <div class="w-100 text-right d-none">
                <img class="plegar flip-vertically cursor-pointer" src="{{ asset('/imagenes/ico-desplegar.svg') }}" alt="plegar">
            </div>
        @endif
    </div>

</div>