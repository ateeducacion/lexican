@if(isset($txtBoton))
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#{{ $id }}">
        {{ $txtBoton }}
    </button>
@endif

@if(isset($img))
    <a data-toggle="modal" href="#{{ $id }}" data-target="#{{ $id }}" class="icon">
        <img src="{{ asset($img) }}" alt="@lang('Enviar')">
    </a>
@endif

<!-- Modal -->
<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $id . 'Label' }}" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">@yield('modal-title')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                @yield('modal-body')
                {{ $id }}
            </div>
            <div class="modal-footer text-center">
                @yield('modal-footer')
            </div>
        </div>
    </div>
</div>