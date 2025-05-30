@extends('diccionario/home')

@section('title', 'Todas las entradas')

@section('content')
@parent

{{-- Seleccion letras Abecedario --}}
@include('layouts.partials.components.abecedario', [
'route'=> 'personal.consulta.byInitial' ])

{{-- Numero de entradas --}}
<div id="nentradas" class="dc-centerdiv">
    {{ $datos->nentradas }} __('diccionario.buscar_titulo')
</div>


<!-- LISTADO -->

<div class="row">
    <div class="col-9">
        @foreach($datos->listado as $item )
            @include('layouts.partials.buscar.entrada', [
            'entrada'=> $item->entrada,
            'confirm' => $item->confirm
            ])
        @endforeach
    </div>

    {{-- acciones --}}
    <div id="btn-acciones-vertical" class="col-3">
        <div class="text-center">
            @include('layouts.partials.components.actionbutton', [
            'icon'=>'addEntradaFB.svg',
            'text'=>__('diccionario.boton_anadir_entrada'),
            'href' => route('entrada.buscador')
            ])
        </div>
        <div class="text-center">
            @include('layouts.partials.components.actionbutton', [
            'icon'=>'envios-comentariosFB.svg',
            'text'=>__('diccionario.boton_envios_comentarios'),
            ])
        </div>
        <div class="text-center">
            @include('layouts.partials.components.actionbutton', [
            'icon'=>'gestionDicFB.svg',
            'text'=>__('diccionario.boton_gestion_diccionario'),
            ])
        </div>
    </div>

</div>

@endsection