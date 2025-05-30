<style>
/* Esta clase se utiliza para sobreescribir el header en la última página (contraportada)
                y hacer parecer que no hay header (pero lo hay). */



.last-page {
    page: last_page;
    background: white;
    position: relative;
    left: 0px;
    top: -130px;
    right: 0px;
    height: 100px;
}

@page {
    margin-top: 150px;
    margin-bottom: 100px;
    margin-left: 50px;
    margin-right: 50px;
}

@page :first {
    margin-top: 15px;
    margin-bottom: 10px;
    margin-left: 0px;
    margin-right: 0px;
    
}

body {
    color: #747474;
    background: #fff;
    font-family: 'Open Sans', sans-serif;
    font-size: 11px;
}

#header {
    position: fixed;
    left: 0px;
    top: -125px;
    right: 0px;
    height: 100px;
}

#footer {
    /* background: #faf; */
    position: fixed;
    left: 0px;
    bottom: -100px;
    right: 0px;
    height: 50px;
    text-align: center;
    font-size: 11px;
}

#footer .page:after {
    content: counter(page, upper-roman);
}

#content {}

/* Con esta clase obligamos al salto de página. */
.page-break {
    page-break-after: always;
}

.logo {
    float: right;
    width: 25px;
}

.text-cab {
    font-size: 17px;
    margin-top: 0.5rem;
    margin-right: 1.5rem;
    color: #509cd4;
}

/* ----------------------------------------------------------------- */
/* Ahora pondré los estilos para poder mostrar el contenido del Body */
/* ----------------------------------------------------------------- */

.col-10,
.col-2 {
    position: relative;
    width: 100%;
    display: inline-block;
    vertical-align: top;
}

.col-10 {
    max-width: 73%;
    padding: 0 2.5%;
    margin-right: 2.5%;
}

.col-2 {
    max-width: 16.6666666667%;
}

.card-wrapper {
    margin-left: 15px;
    margin-bottom: 1mm;
    /* background-color: #fff; */
    /* border-top: 1px solid #ddd; */
    /* border-bottom: 1px solid #ddd; */
    /* border-radius: 0.25rem; */
    padding: 10px 0;
}

.card-title {
    font-size: 20px;
    line-height: 24px;
    margin-right: 1.5rem;
    margin-bottom: 3mm;
}

.creditos-nombres {
    margin: 0.5em; 
    line-height: 1.5em;
    white-space: nowrap;
}

.cabecera-pagina{
    position:relative;
    margin-top:0.8cm;
}
.titulo-entrada {
    display: inline-block;
    background: #fff;
    font-size:0.5cm; 
    border: 0.5mm;
    color: #489cd0;
}

.titulo-linea {
    height: 0.3mm;
    width: 100%;
    background-color: #ddd;
    position: relative;
}


.acepcion-linea{
    height: 1px;
    width: 99%;
    background-color: #ddd;
    /* margin: 0 auto;     */
}
.box-contra{
    position: absolute;
    top: 11cm; 
    left: 0cm;
    background: #f9f9f9;
    width: 18.5cm;
    height: 7cm;
    text-align: center;
}

.portada{
    /* background: black; */
    text-align: center;
}
.imagen-portada{
    /* position: relative; */
    /* top: 0cm;  */
    left: 0.5mm;
    /* width: 100%; */
    /* height: 7cm; */
    /* margin: 0 1cm; */
    
}

.titulo-portada-box{
    position: absolute; 
    width:100%; 
    height: 3cm; 
    text-align: center; 
    /* border: 1mm solid black; */
    /* background: rgba(0,0,0,0.1); */
    padding:0;
    top: 27mm;
    /* left: -9mm; */
    color:#fff;
    font-size:6mm;
}
.subtitulo-portada{
    
    position: absolute; 
    width: 100%; 
    height: 3cm; 
    text-align: center; 
    /* background: rgba(0,0,0,0.1); */
    padding: 0;
    top: 40mm;
    /* left: -4mm; */
    color: #489cd0;    
    font-size: 6mm;
}
.last-page h3,
.last-page h4 
{
    /* color: #3EB1BA;
    color: #004994; */
    color: #489cd0;
    color: #699bcd;
}
.last-page 
{
    /* color: #776fd3; */
}

.contraportada-descripcion {
    position: absolute;
    top: 2.2cm; 
    left: 0cm;
    color: #699bcd;
    /* border: 1px solid blue; */
    width: 18.5cm;
    height: 4cm;
    text-align: center;
}

.contraportada-descripcion p {
    display: block;
    margin: 0 auto;
    width: 50%;
}

.dc-ejemplo2{
    font-style: italic;
}


</style>