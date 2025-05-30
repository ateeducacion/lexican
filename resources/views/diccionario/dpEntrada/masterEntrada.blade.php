@extends('diccionario/home')

@section('content')
@include('layouts.partials.components.quehacer')

{{-- Migas de pan --}}
@include('layouts.partials.components.breadcrumb', ['breadcrumbs' => $datos->breadcrumb] )

@include('layouts.partials.alerts.alertas')
{{-- ya esta en diccionario/home

@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $mensaje)
                <h4>
                    <li>{{ $mensaje }}</li>
                </h4>
            @endforeach
        </ul>
    </div>
@endif

@if(\Session::has('success'))
    <div class="alert alert-success">
        <ul>
            <h4>
                <li>{{ \Session::get('success') }}</li>
            </h4>
        </ul>
    </div>
@endif

@if(\Session::has('successArray'))
    <div class="alert alert-success">
        <ul>
            @foreach(\Session::get('successArray') as $mensaje)
                <h4>
                    <li>{{ $mensaje }}</li>
                </h4>
            @endforeach
        </ul>
    </div>
@endif
--}}



@if(isset($datos->modo))
{{-- OJO! EL CODIGO HTML SUELTO EN ESTE SWITCH NO SE ESTA CARGANDOO EN
    VARIOS CASOS  --}}
    @switch($datos->modo)

        @case('BUSCADOR_ENTRADA')
            @section('title', 'Buscador entrada')
            @include('layouts.partials.dpEntrada.dpEntradaBuscador')
            @break

        @case('ALTA_ENTRADA')
            @section('title', 'Alta entrada')

            @include('layouts.partials.dpEntrada.dpEntradaEntrada',[
            "entrada"=> $datos->entrada,
            "confirm" => $datos->confirm
            ] )
            @break

        @case('CREAR_ENTRADA')

            @section('title', 'Crear Entrada')
            {{-- @include('layouts.partials.dpEntrada.dpEntradaEntrada',[
                                                                                    "entrada"=> $datos->entrada,
                                                                                    "confirm" => $datos->confirm
                                                                                    ] ) --}}
            @include('layouts.partials.dpAcepcion.dpAcepcionFormulario',[
                "mensaje"=> $datos->mensaje,
            ] )
            @break

        @case('EDITAR_ENTRADA')
            @section('title', 'Editar entrada')
            {{-- @include('layouts.partials.dpEntrada.dpEntradaEntrada',[
                                                                                "entrada"=> $datos->entrada,
                                                                                "confirm" => $datos->confirm
                                                                                ] ) --}}
            @include('layouts.partials.dpEntrada.dpEntradaEdit')
            @break

        @case('MOSTRAR_ENTRADA')
            @section('title', 'Entrada')
    
            {{-- Alerta: Se ha añadido la acepción para drago --}}
            @if(\Session::has('acepcion_success'))
                <div class="alert alert-success" role="alert">
                    {{ \Session::get('acepcion_success') }}
                </div>
            @endif
    
            {{-- Alerta: La entrada "drago" ya existe --}}
            @if(\Session::has('acepcion_info'))
                <div class="alert alert-warning" role="alert">
                    {{ \Session::get('acepcion_info') }}
                </div>
            @endif

            <div class="container">
                <div class="row">
                    <div class="col-md-8 col-lg-9 col-xl-10">
            
                        {{-- Buscador de entrada: Escriba aquí una entrada y pulse ... --}}
                        {{-- @include('layouts.partials.dpEntrada.dpEntradaBuscador') --}}
                
                        {{-- Mensaje: Acepciones añadidas --}}
                        {{-- @lang('diccionario.entrada_acepciones_anadidas') --}}
                        @include('layouts.partials.dpEntrada.dpEntradaEntrada')
                    </div>
                    {{-- Botones acciones --}}
                    <div class="col-md-4 col-lg-3 col-xl-2">
                        @include('layouts.partials.components.actionbuttonPersonal')
                    </div>
                </div>
            </div>
    
            @break

        @case('ANADIR_ACEPCION')

            @section('title', 'Añadir Acepción')
            @include('layouts.partials.dpAcepcion.dpAcepcionFormulario')
            <script>
                window.scrollTo(0, document.body.scrollHeight);
            </script>
            @break


        @default
            <h1>ERROR en la aplicación. Error al seleccionar un MODO en la creación de la ENTRADA</h1>
            @break

    @endswitch
@endif

{{-- Esto es necesario por que en este masterEntrada.blade carga mal diccionario/home.blade --}}
<div id="padre_modal_ajax">
</div>

@endsection
