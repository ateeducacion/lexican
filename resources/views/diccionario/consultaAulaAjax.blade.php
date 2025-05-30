{{ $datos->scrollLastRow }}
@foreach($datos->listado as $item )
    @include('layouts.partials.consulta.entradaAula', [
        'entrada'=> $item->entrada,
        // 'confirm' => $item->confirm,
        // 'listaDiccionariosAula'=> $datos->listaDiccionariosAula,
    ])
@endforeach
