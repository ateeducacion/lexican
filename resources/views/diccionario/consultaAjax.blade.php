{{ $datos->scrollLastRow }}
@foreach($datos->listado as $item )
    @include('layouts.partials.consulta.entrada', [
        'entrada'=> $item->entrada,
        'confirm' => $item->confirm,
        'listaDiccionariosAula'=> $datos->listaDiccionariosAula,
        // 'curso_escolar' => $datos->curso_escolar,
        // 'nivelEstudios' => $datos->nivelEstudios,
    ])
@endforeach
