{{--
    Fernando ramires <fernando.ramirez@altia.es>

    variables: 
    $ajax 
    $route <a href="{{ route($route, ['letra'=>$value]) }}">
    $letraSel : Letra actualmente seleccionada 

--}}
@section('css')
<style>
.abecedario {
    margin: 0 auto;
    width: max-content;
    max-width: 100%;
    
    /* colores */
    --abcolor: var(--gris4);
    --abcolorSel: var(--color1);

}
.abecedario .letra {
    --diametro: 32px;
    --altoLetra: 20px;

    position: relative;
    height: var(--diametro);
    width: var(--diametro);

    display: inline-block;
    background-image: url("{{asset('imagenes/letras.svg')}}");
    background-repeat: no-repeat;
    color: transparent;
}
.abecedario .letra.sel {
    background-image: url("{{asset('imagenes/letrasSel.svg')}}");
}

.horizontal-scroll {
  overflow-x: auto;
  white-space: nowrap;
  height: 55px;
}
.horizontal-scroll > div {
  display: inline-block;
  float: none;
  background-color: white;
  border: 1px solid black;
}
.abecedario a:hover{
    text-decoration: none;
}

@if (!isset($datos->alfabeto))
<?php $datos->alfabeto = getAlfabeto() ?>
@endif
@foreach($datos->alfabeto as $key => $value)
.letra{{$value}} {
    /* --anchoLetra: calc( 65.138 - 30.716); */
    /* 65.134 - 30.716 */
    /* epsacio en blanco : 3.7 */
    /* 34.422 - 3.7/2 =32.572 */
    /* 34.422px */
    background-position: calc( 34.422px * {{ - $key}}) 0px;
}
@endforeach
/* fix d */
/* .letraD {
    background-position: -103px 0px;
} */
</style>
@append

<div class="abecedario">
    <div class="horizontal-scroll">
@foreach($datos->alfabeto as $key => $value)
    @if(isset($ajax))
        <a href="javascript:void(0)" data-ajax="{{$event}}" data-letra="{{$value}}">
            <div
                class="letra 
                @if( isset($letraSel) && $letraSel == $value )
                sel
                @endif
                letra{{$value}}" 
            >{{$value}}</div>
        </a>
    @else
        <a href="{{ route($route, ['letra'=>$value]) }}">
            <div
                class="letra 
                @if( isset($letraSel) && $letraSel == $value )
                sel
                @endif
                letra{{$value}}" 
            >{{$value}}</div>
        </a>
    @endif
@endforeach
    </div>
</div>
