@if(isset($href))
    <a href="{{ $href ?? '#' }}">
        <div class="actionbutton {{ $selected ?? '' }} {{ (isset($disable) && $disable)? 'disable': 'cursor-pointer ' }}"
        @if( isset($disable)) && $disable)
        data-toggle="tooltip" title="{{ __('diccionario.tooltip_faltaDiccionarioAula') }}" data-placement="bottom"
        @endif
        >
            @if(isset($svginline))
                <svg class="icon" width="100%" height="55" viewBox="0 0 70 55">
                    <use xlink:href="#{{ $svginline }}" />
                </svg>
            @else
                <img class="icon" src="{{ asset('imagenes/'.$icon) }}" />
            @endif
            <span class="text">{{ $text }}</span>
        </div>
    </a>
@else

    <div class="actionbutton {{ $selected ?? '' }} {{ (isset($disable) && $disable)? 'disable': 'cursor-pointer ' }}"
    @if( isset($disable) && $disable)
        data-toggle="tooltip" title="{{ __('diccionario.tooltip_faltaDiccionarioAula') }}" data-placement="bottom"
    @endif
    >
        @if(isset($svginline))
                <svg class="icon" {{-- viewBox="0 0 105 85" --}} >
                    <use xlink:href="#{{ $svginline }}" />
                </svg>
            @else
                <img class="icon" src="{{ asset('imagenes/'.$icon) }}" />
            @endif
        <span class="text">{{ $text }}</span>
    </div>

@endif