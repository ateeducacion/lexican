{{-- <form id="formdpEntradaBuscador" method="GET" action="{{ route('entrada.insert') }}">
@csrf

<div class="form-group">

    <label for="entrada_entrada">@lang('diccionario.entrada_entrada')</label>
    <div class="input-group">
        <input id="entrada_entrada" name="entrada_entrada" type="text" class="form-control" placeholder="@lang('diccionario.entrada_anadir_placeholder')">
        <input type="image" name="submit" src="{{ URL::to('/') }}/imagenes/boton_nueva_entrada.png" alt="@lang('diccionario.entrada_anadir')" />
    </div>

</div>
</form>
--}}

<style>
    .libro {
        height: 13.5rem;
        position: relative;
    }
    .libro #texto-imagen {
        position: absolute;
        height: fit-content;
        width: 12rem;
        left: 1.5rem;
        /* background: rgba(255,0,0,0.3); */
        /* Entada y acepcion */
        /* top: 2.2rem; */
        /* Entada: */
        top: 3.4rem;
        
        font-weight: bold;
        font-style: italic;
    }
</style>

<div class="container">
<div class="row">

    <div class="col-md-8 col-lg-9 col-xl-10">
        <div class="row justify-content-center">
            {{-- quite el col-md-4 de aqui por que se ve mejor simplenten centrado --}}
            <div class="">
                <div class="libro">
                    <img id="texto-imagen" src="{{ asset('/imagenes/addEntradaTxt.svg') }}" >
                    <img src="{{ asset( '/imagenes/addentradabook.svg' ) }}" >
                </div>
                <form 
                id="formdpEntradaBuscadormethod=" 
                type="GET" 
                action="{{ route('entrada.insert') }}">
                    @csrf
                    {{-- <label for="entrada_entrada">@lang('diccionario.entrada_entrada')</label> --}}
                    <div class="input-group mb-3 dc-input-group md-col-3">
                        <input name="entrada_entrada" id="entrada_entrada" type="text" class="form-control" placeholder="{{ __('diccionario.entrada_anadir_placeholder') }}" aria-label="{{ __('diccionario.entrada_entrada') }}">
                        <div class="input-group-append">
                            <button type="submit"><img src="{{ asset('imagenes/boton_nueva_entrada.svg') }}"></button>
                        </div>
                    </div>

                </form>

            </div>
        </div>
    </div>

    {{-- Botones acciones --}}
    <div class="col-md-4 col-lg-3 col-xl-2">
        @include('layouts.partials.components.actionbuttonPersonal', 
            ['anadir_entrada_active' => 'selected']
        )
    </div>

</div>
</div>{{-- Fin container --}}