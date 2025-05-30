<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (!function_exists('getVideoThumbnail')) {
    function getVideoThumbnail($tipo_medio, $filename)
    {
        // file type is video
        // set storage path to store the file (image generated for a given video)
        // $thumbnail_path   = storage_path() . '/images';
        $thumbnail_path = public_path() . dpMedio_getPublicURL(config('ctes.tipos_medios.video'));

        //$video_path       = $destination_path . '/' . $file_name;
        $video_path = public_path() . dpMedio_getPublicURL(config('ctes.tipos_medios.video')) . '/' . $filename;

        // set thumbnail image name
        // $posicionPunto = strrpos($filename, '.');
        // $nombre = substr($filename, 0, $posicionPunto);

        $nombre = getNombreFichero($filename);

        $thumbnail_image  = $nombre .  config('ctes.video_thumbnail_sufijo') . ".jpg";

        // set the thumbnail image "palyback" video button
        // $water_mark       = storage_path() . '/watermark/p.png';

        // get video length and process it
        // assign the value to time_to_image (which will get screenshot of video at that specified seconds)
        //$time_to_image    = floor(($data['video_length']) / 2);

        $thumbnail_status = Thumbnail::getThumbnail($video_path, $thumbnail_path, $thumbnail_image, 1);
        // if ($thumbnail_status) {
        //     echo "Thumbnail generated";
        // } else {
        //     echo "thumbnail generation has failed";
        // }
    }
}
