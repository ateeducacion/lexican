@extends('diccionario/home')
@section('title', __('diccionario.title_diccionario-modificar-ver'))



@section('content')
@parent


<div class="row">
    <div class="col-md-12">
        <h5 class="card-title">{{ __('diccionario.title_diccionario-modificar-ver') }}</h5>
    </div>
</div>

<div class="row">
    {{--  --}}
    <div class="col-md-8 col-lg-9 col-xl-10"> 
        <div class="dc-panel-gris card" >
        <form class="my-form" method="POST" action="{{ route('diccionario.modificar.update', ['diccionario_id' => $datos->diccionario->id, 'titulo_viejo'=> $datos->diccionario->titulo, 'avatar_viejo'=> 'default.svg']) }}" enctype="multipart/form-data">
            @csrf

            <div class="my-3 form-row">
            
                <label for="nombre_diccionario">
                    {{ __('diccionario.Nombre del diccionario') }}
                </label>
                <input class="ml-2 col-md" type="text" 
                    id="nombre_diccionario" name="nombre_diccionario" 
                    value="{{ $datos->diccionario->titulo }}" 
                    disabled
                    />
                <img 
                    onclick="enableNomDic()" 
                    data-toggle="tooltip" 
                    title="{{ __('diccionario.Editar') }}" 
                    data-placement="top" 
                    style="bottom: 4px; position: relative; height: 30px;"
                    class="d-block cursor-pointer" 
                    src="{{ asset('imagenes/ico-edit.svg') }}">
            </div>

            <div class="form-row ">
                <div style="
                display: flex;
                align-items: center;
                margin-bottom: 0.5rem;
                justify-content: space-between;
                right: 0;
                position: relative;
                height: 5rem;
                width: 100%;">
                    <div >
                        <label for="avatar_id">{{ __('diccionario.selecione_nuevo_avatar') }}</label>
                    </div>
                    <div class="
                        align-items: center;
                        display: flex;">
                        <span >Avatar actual: </span>
                        <img 
                            src="{{ URL::to('/') . '/storage/avatares/oficiales/' . $datos->avatar }}" 
                            width="70" 
                            alt="Avatar"
                        >
                    </div>
                </div>

                
                
                <div class="swiper-container">
                    <!-- Additional required wrapper -->
                    <div class="swiper-wrapper">
                        <!-- Slides -->
                        @foreach($datos->lista_avatares as $avatar)
                        <div class="swiper-slide" data-id="{{ $avatar }}">
                            <input class="" type="radio" name="avatar_nuevo" id="chk_{{ $loop->iteration }}" value="{{ $avatar }}"
                            >
                            <label for="chk_{{ $loop->iteration }}" class="cursor-pointer">
                                <div class="green-checker-label" ></div>
                                <img src="{{ URL::to('/') . '/storage/' .
                                    config('ctes.path_medios.avatares') . '/'.
                                    $avatar }}" alt="{{ $avatar }}" /> 
                                <div class="small">
                                    {{ $avatar }}
                                  </div>
                            </label>
                            
                        </div>
                        @endforeach
                        
                    </div>
                    <!-- If we need pagination -->
                    {{-- <div class="swiper-pagination"></div> --}}
                
                    <!-- If we need navigation buttons -->
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                    
                    <!-- If we need scrollbar -->
                    {{-- -<div class="swiper-scrollbar"></div> --}}
                </div>

            </div>
            {{--$datos->avatares = $avatarListFiles; --}}
{{-- swiper prueba --}}
<div class="row">
</div>
{{-- fin swiper --}}

            <input type="hidden" name="formBackUrl" value="{{ $datos->formBackUrl }}">
            {{-- Botones del formulario --}}
            <div class="text-center">
                <button type="submit" class="btn btn-primary">@lang('diccionario.acepcion_guardar')</button>

                <a href="{{ $datos->formBackUrl }}" class="btn btn-danger">@lang('diccionario.acepcion_cancelar')</a>
            </div>

        </form>
        </div>
    </div>

    {{-- Botones acciones --}}
    <div class="col-md-4 col-lg-3 col-xl-2">
        @include('layouts.partials.components.actionbuttonPersonal')
    </div>
</div>


@endsection

@section('scripts')
<script>
function enableNomDic() {
    $('#nombre_diccionario').prop('disabled', false);
}
</script>
@endsection