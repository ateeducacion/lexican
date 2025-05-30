<!-- partial.consulta.acepcion -->
{{-- -acepciones en  --}}
<div class="row dc-entrada-acepcion {{ $orden == 1 ? "dc-acepcion-principal" : "dc-acepcion-secundaria" }}">
    <div class="d-none d-sm-none d-md-block d-lg-block d-xxl-block w-100">

        <div class="row ">
            <div class="col-1 d-inline-flex" 
                style="flex-basis: 3.9rem">
                <div class="dc-entrada-botones dc-entrada-botones-acepcion d-block" >
                    @include( 'layouts.partials.components.botones.acepcion.ocultar', ['id'=> $acepcion->id])
                </div>
            </div>
            <div class="col-11">
                <div class="float-right" style="padding: 0 10px 2px 22px">
                    <div class="row"> 
                        <div class="dc-entrada-botones-media justify-content-center" 
                            style="padding: 0 1.3rem" >
                            @include('layouts.partials.consulta.acepcion-medios', [ 'acepcion' => $acepcion ])
                        </div>
                        <div class="dc-entrada-imagen d-inline-block">
                            @include('layouts.partials.consulta.acepcion-imagen', [ 'acepcion' => $acepcion ])
                        </div>
                    </div>
                </div>

                @include('layouts.partials.consulta.acepcion-texto', [ 'acepcion' => $acepcion ])
                <div class="dc-acepcion-leermas"> @lang('Leer mas...') </div>
            </div>
        </div>

        <div class="clearfix"></div>

    </div>

    <div class="d-flex d-sm-flex d-md-none d-lg-none d-xxl-none row">
        <div class="dc-entrada-botones dc-entrada-botones-acepcion col-2 col-sm-2 col-md-1">
            @include( 'layouts.partials.components.botones.acepcion.ocultar', ['id'=> $acepcion->id])
        </div>
        <div class="col-12 col-sm-12" >
            @include('layouts.partials.consulta.acepcion-texto', [ 'acepcion' => $acepcion ])
            <div class="dc-acepcion-leermas"> @lang('Leer mas...') </div>
        </div>
        <div class="dc-entrada-botones-media justify-content-center" style="padding: 0 1.3rem" >
            @include('layouts.partials.consulta.acepcion-medios', [ 'acepcion' => $acepcion ])
        </div>
        <div class="dc-entrada-imagen d-inline-block">
            @include('layouts.partials.consulta.acepcion-imagen', [ 'acepcion' => $acepcion ])
        </div>
    </div>

</div>