<header id="header">
    <div class="cabecera">
        <div class="col-10">
            <div>
                <img src="{{ public_path() }}/imagenes/lexican_azul.png" style="width: 120px;" />
            </div>
            <div class="text-cab">
                {{ mb_strtoupper(__('diccionario.diccionario')) }}: {{ mb_strtoupper($datos['titulo']) }}
                {{-- <br> --}}
                {{-- DICCIONARIO DE {{ mb_strtoupper($datos['nombreCompleto']) }} --}}
            </div>
        </div>

        <div class="col-2">
            <img class="logo" src="{{ public_path() }}/imagenes/escudo_gobierno_negro.png" />
        </div>
    </div>        
</header>
