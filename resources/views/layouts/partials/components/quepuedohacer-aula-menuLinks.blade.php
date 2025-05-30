<div><a target="_blank" href="{{ asset('/imagenes/' . rawurlencode('que_puedo_hacer_diccionario_Docente.pdf') ) }}">{{__('Aspectos generales')}}</a></div>
<div><a target="_blank" href="{{ asset('/imagenes/' . rawurlencode('Manual Usuario Lexican.pdf') ) }}">{{__('Manual de uso')}}</a></div>
@can( 'createDicAula', App\DicAula::class )
<div><a target="_blank"  href="{{ asset('/imagenes/' . rawurlencode('Manual de coordinación.pdf') ) }}">{{__('Manual de Coordinación')}}</a></div>
@endcan

<div>@include('diccionario.aula.pautas', ['pauta_chk_id' => $pauta_chk?? 'pauta_chk'])</div>
{{-- <div>@include('diccionario.aula.pautas', ['pauta_chk_id' => 'pauta_chk_2'])</div> --}}

