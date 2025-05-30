{{-- 
    Fernando Ramirez <fernando.ramirez@altias.es>
    
    $datos->route_nentradas 
        Ruta a la que lleva el link si no existo o esta vacio pone el enlace a 
        todas las entradas del diccianario personal
    $datos->nentradas
        Numero de entradas, si es 0 muestra el mensaje de no hay entradas
    $texto (opcional)
        Texto que se muestra en lugar de "Ver todas las entradsas"
    
    debug:
        <p> $datos->route_nentradas : {{ $datos->route_nentradas }}</p>
        <p> $datos->nentradas : {{ $datos->nentradas }}</p>
        <p> route( $datos->route_nentradas ): {{ route( $datos->route_nentradas ) }} </p>
        <p> route( 'aula.consulta.all' ): {{ route( 'aula.consulta.all' ) }} </p>
        <p> route( 'personal.consulta.all' ) : {{  route('personal.consulta.all') }} </p>
        <p> {{ (isset($datos->route_nentradas) && route( $datos->route_nentradas )) ? route( $datos->route_nentradas ) : route('personal.consulta.all') }} </p>
 --}}
<div class="dc-centerdiv dc-home-nentradas">
    <a href="{{ (isset($datos->route_nentradas) && route( $datos->route_nentradas )) ? route( $datos->route_nentradas ) : route('personal.consulta.all') }}">
        @if( $datos->nentradas && $datos->nentradas>=0 )
            @if( !isset($texto) )
                {{__('diccionario.nentradas_ver_todas')}} ({{$datos->nentradas}})
            @else
                {{ $texto }} ({{$datos->nentradas}})
            @endif
            {{-- <img style="padding-bottom:3px" src="{{ asset('imagenes/ojo.svg') }}" /> --}}
        @else
        {{__('diccionario.nentradas_cero_entradas')}}
        @endif    
    </a>
</div>
