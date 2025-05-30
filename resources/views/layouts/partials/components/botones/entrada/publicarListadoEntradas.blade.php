<?php
$check = comprobarPublicarListadoEntradas( $entradas, $dicAula->id );
$n_entradasOk = count($check->ok);
// entradas que no se publictaran:
$yaPublicadas = $check->error->yaPublicadas; 
$duplicadasEnListado = $check->error->duplicadasEnListado;
$n_entradasError = count($yaPublicadas) + count($duplicadasEnListado);
$n_entradas = $n_entradasOk + $n_entradasError;
?>

{{-- Boton pra probar si se actuliza correctamente  --}}
{{-- <a class="d-none" id="cargarDatosPublicarTodos" data-actionurl="{{ route('diccionarioaula.publicarListado.statusEntradas', [
        'id'=> $dicAula->id ] ) }}">test</a> --}}

<div id="modal-publicar-listado" class="modal-confirmar dc-emergente">
    @include('layouts.partials.components.modal-publicar-listado', [
        'dicAula'=> $dicAula,
        'entradas' => $entradas,
        'n_entradasOk' => $n_entradasOk,
        'yaPublicadas' => $yaPublicadas,
        'duplicadasEnListado' => $duplicadasEnListado,
        'n_entradasError' => $n_entradasError,
        'n_entradas' => $n_entradas,    
    ])
</div>

<div style="height: 1.9rem;
display: inline-block;
margin-right: 3px;
top: -2px;
position: relative;">
    <a id="envios-publicar-todo" href="javascript:void(0)" 
        data-event="publicar-listado-entradas" 
        data-diccionarioid="{{ $dicAula->id }}"
        class="icon" data-toggle="tooltip" data-placement="left" title="{{ __('diccionario.publicar_todo') }}" >

        <img style="height: 100%" src="{{ asset('/imagenes/ico-pergamino-todos.svg') }}" >
    </a>

</div>