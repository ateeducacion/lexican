<a href="{{ URL::to( $tabUrl) }}">
    @if( 
    // getDatosTabsurl()->current() == $tabUrl || 
    (isset($active) && $active==true ) )
    <div class="dc-tab active" id="{{$id}}" >
        <img src="{{ asset('/imagenes/tab-active.svg') }} ">    
    @else
    <div class="dc-tab" id="{{$id}}" >
        <img src="{{ asset('/imagenes/tab-inactive.svg') }}">
    @endif       
        <div class="name">{{$tabName}}</div>
    </div>
</a>