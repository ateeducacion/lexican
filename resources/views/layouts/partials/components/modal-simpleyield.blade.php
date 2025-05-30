@if(isset($txtBoton))
    <button type="button" class="btn btn-primary {{ $class }}" data-toggle="modal" data-target="#modal_{{ $id }}" 
    @isset($tooltip)        
    title="{{ $tooltip }}"
    @endisset    
    >
        {{-- <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal_ajax}" title="{{ $tooltip }}"> --}}
        @isset($tooltip)
            <span data-toggle="tooltip" data-placement="top" title="{{ $tooltip }}">{{ $txtBoton }}</span>
        @else
            <span>{{ $txtBoton }}</span>
        @endisset        
    </button>
@endif

@if(isset($img))
    <a data-toggle="modal" href="#modal_{{ $id }}" data-target="#modal_{{ $id }}">
        {{-- <a data-toggle="modal" href="#modal_ajax" data-target="#modal_ajax"> --}}
        @if(isset($class))
            <img data-toggle="tooltip" data-placement="top" src="{{ asset($img) }}" title="{{ $tooltip }}" class="{{ $class }}" @if(isset($imgstyle)) $imgstyle @endif>
        @else
            <img data-toggle="tooltip" data-placement="top" src="{{ asset($img) }}" title="{{ $tooltip }}" @if(isset($imgstyle)) $imgstyle @endif>
        @endif
    </a>
@endif

@if(isset($bocadillo))
    <a data-toggle="modal" href="#modal_{{ $id }}" data-target="#modal_{{ $id }}" class="col-4 dc-actionbutton-menu-btn-enlace d-none">
        {{-- <a data-toggle="modal" href="#modal_ajax" data-target="#modal_ajax" class="col-4 dc-actionbutton-menu-btn-enlace d-none"> --}}
        <div class="d-flex flex-column dc-actionbutton-menu-btn">
            <svg width="100%" height="54.545" viewBox="0 0 70 55">
                <use xlink:href="#{{ $bocadillo }}" />
            </svg>
            <span>{{ $caption }}</span>
        </div>
    </a>
@endif

<!-- Modal -->
<div class="modal fade" id="modal_{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $id . 'Label' }}" aria-hidden="true">
    {{-- <div class="modal fade" id="modal_ajax" tabindex="-1" role="dialog" aria-labelledby="{{ $id . 'Label' }}" aria-hidden="true"> --}}

    @if(isset($modal_width))
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: {{ $modal_width }};">
        @else
            <div class="modal-dialog modal-dialog-centered" role="document">
    @endif

    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">
                @yield('modal-title')
            </h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body text-center">
            @yield('modal-body')
        </div>

        <div class="modal-footer text-center">
            @yield('modal-footer')
        </div>
    </div>
</div>
</div>

<!-- Scripts Modal -->
@yield('modal-scripts')