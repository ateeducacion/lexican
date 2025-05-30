{{--
    * html tabs 
    Carga las tabs en el array @datos->tabs [
        {
            tabName : 'texto tab", 
            tabUrl: 'url a donde enlaza'
        },
        ...
    ]/
    --}}

<div class="dc-tabs">
    @foreach( ($datos->tabs ?? getDatosTabs()) as $key => $value)
        @include('layouts.partials.components.tab', $value )
    @endforeach
</div>

