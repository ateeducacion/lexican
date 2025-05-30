<?php

use App\Models\CampoValor;

if (!function_exists('mstEntradaValor_GetValoresByEntrada')) {
    function mstEntradaValor_GetValoresByEntrada($mst_campo_entrada_id, $orden = 'asc')
    {
        $ListaValores = CampoValor::where('mst_campo_entrada_id', '=', $mst_campo_entrada_id)
            ->orderBy('descripcion', $orden)
            ->get();

        return $ListaValores;
    }
}
