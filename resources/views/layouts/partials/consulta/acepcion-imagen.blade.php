@if( dpMedio_getImagen($acepcion) )
    <a href='{{ URL::to('/') . "/storage/" . config("ctes.path_medios.imagen") . '/' .  dpMedio_getImagen($acepcion)->url_interna }}' download='{{ dpMedio_getImagen($acepcion)->nombre }}'>
        <div style="background-image:url( {{ URL::to('/') .'/storage/' . config('ctes.path_medios.imagen') . '/' . getNombreFichero(dpMedio_getImagen($acepcion)->url_interna) . config('ctes.video_thumbnail_sufijo') . ".jpg" }} )" class="dc-entrada-imagen-img"></div>
    </a>
@else
    <div class="dc-entrada-imagen-img"></div>
@endif