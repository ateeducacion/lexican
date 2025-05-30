<div class="last-page diccionario-personal">
    <img src="{{ public_path() }}/imagenes/pdf02_Contra_Portada.png" width="700" />
    <div class="contraportada-descripcion">
        <h1> {{ mb_strtoupper(__('diccionario.contraportada_personal_titulo')) }}</h1>
        <p class="mt-2">{{ __('diccionario.contraportada_descripcion') }}</p>
    </div>
    <div class="box-contra" >
        <h3>{{ mb_strtoupper($datos['titulo']) }}</h3>

        <h4>{{ mb_ucfirst( __('diccionario.autor') ) }}</h4>
        {{ mb_strtoupper($datos['nombreCompleto']) }} 
    </div>
</div>