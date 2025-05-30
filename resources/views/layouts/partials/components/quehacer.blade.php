
@if ( isset($datos) && isset($datos->tabs[1]['active']) && $datos->tabs[1]['active'] === true  )
{{--  Aula: --}}
    {{--<a target="_blank" class="d-none d-md-block"
            href="{{ asset('/imagenes/' . rawurlencode('Qué_puedo_hacer_aulav1-A.pdf') ) }}">
        <img style="position: absolute; top: -107px; right: 300px;z-index: 1001;"
            src="{{ asset( '/imagenes/quehacer.png' ) }}">
    </a>--}}
    <div class="cursor-pointer quepuedohacer" data-event="quepuedohacer">
        <img
            src="{{ asset( '/imagenes/quehacer.png' ) }}">
    </div>
    <div class="d-none position-absolute que-puedo-hacer-menu row" data-cause="menu">
        <div class="col-2">
            <div>
                <svg width="100%" height="22.545" viewBox="0 0 70 55">
                    <use xlink:href="#ico-bocadillo-exportarpdf" />
                </svg>
            </div>
            
            <div>
                <svg width="100%" height="22.545" viewBox="0 0 70 55">
                    <use xlink:href="#ico-bocadillo-exportarpdf" />
                </svg>
            </div>
            @can( 'createDicAula', App\DicAula::class )
            
            <div>
                <svg width="100%" height="22.545" viewBox="0 0 70 55">
                    <use xlink:href="#ico-bocadillo-exportarpdf" />
                </svg>
            </div>
            @endcan
            
            <div>
                <svg width="100%" height="22.545" viewBox="0 0 70 55">
                    <use xlink:href="#list-ol" />
                </svg>
            </div>
        </div>
        <div class="col-10">
            @include('layouts.partials.components.quepuedohacer-aula-menuLinks', ['pauta_chk'=>'pauta_chk2'])
        </div>
    </div>
@else
{{--  Personal: --}}
    {{--<a target="_blank" class="d-none d-md-block"
            href="{{ asset('/imagenes/' . rawurlencode('Qué_puedo_hacer_personal_v1-B.pdf') ) }}">
        <img style="position: absolute; top: -107px; right: 300px;z-index: 1001;"
            src="{{ asset( '/imagenes/quehacer.png' ) }}">
    </a>--}}
    <div class="cursor-pointer quepuedohacer" data-event="quepuedohacer">
        <img
            src="{{ asset( '/imagenes/quehacer.png' ) }}">
    </div>
    <div class="d-none position-absolute que-puedo-hacer-menu row" data-cause="menu">
        <div class="col-2">
            <div>
                <svg width="100%" height="22.545" viewBox="0 0 70 55">
                    <use xlink:href="#ico-bocadillo-exportarpdf" />
                </svg>
            </div>
            <div>
                <svg width="100%" height="22.545" viewBox="0 0 70 55">
                    <use xlink:href="#ico-bocadillo-exportarpdf" />
                </svg>
            </div>
        </div>
        <div class="col-10">
            @include('layouts.partials.components.quepuedohacer-personal-menuLinks')
        </div>
    </div>
@endif
