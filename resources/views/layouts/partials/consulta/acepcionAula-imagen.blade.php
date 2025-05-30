@if( daMedio_getImagen($acepcion) && $acepcion->envioEntrada->dpEnvio->dicAula->dicAulaCampos->where('mst_campo_entrada_id', config('ctes.mst_campos_entrada.imagen'))->first()->visible == config('ctes.visibilidad.visible'))

<a href='{{ URL::to('/') . "/storage/" . config("ctes.path_medios.imagen") . '/' .  daMedio_getImagen($acepcion)->url_interna }}' download='{{ daMedio_getImagen($acepcion)->nombre }}'>
    <div style="background-image:url( {{ URL::to('/') .'/storage/' . config('ctes.path_medios.imagen') . '/' . getNombreFichero(daMedio_getImagen($acepcion)->url_interna) . config('ctes.video_thumbnail_sufijo') . ".jpg" }} )" class="dc-entrada-imagen-img"></div>
</a>

@else

<div class="dc-entrada-imagen-img"></div>

@endif