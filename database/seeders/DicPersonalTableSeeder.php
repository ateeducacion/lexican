<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiccionarioPersonal;
use App\Models\UserPersona;
use TCG\Voyager\Models\User;
use TCG\Voyager\Models\Persona;
// use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;


//  Ejecutar con :
//  php artisan db:seed --class=DicPersonalTableSeeder

class DicPersonalTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // get user  alumno@alumno.com
        $user = User::where('email', 'docente@docente.com')->firstOrFail();
        $user_id = $user->id;

        // $alfabeto = [
        //     "A","B","C","D","E", 
        //     "F","G","H","I","J",
        //     "K","L","M","N", "Ñ",
        //     "O","P","Q","R","S", 
        //     "T","U","V","W","X", "Y","Z",

        //     "a","b","c","d","e", 
        //     "f","g","h","i","j",
        //     "k","l","m","n","ñ",
        //     "o","p","q","r","s", 
        //     "t","u","v","w","x", "y","z",

        //     "á", "é", "í", "ó", "ú", "ü"
    
        // ];
        
        $alfabeto = array_merge(
                range('A', 'N'), 
                ['Ñ'], range('O', 'Z'),
                range('a', 'n'), 
                ['ñ'], range('o', 'z'),
                ['á', 'é', 'í', 'ó', 'ú', 'ü'],
                ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü']
        );

        // si no hay dic personal crea uno 
        if (! $diccionario = dpDiccionario_GetDiccionarioByPersonaId($user_id) ) {
            $diccionario = dpDiccionario_AddDiccionario($user_id);
        }
        $lipsum = "
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus a fermentum orci. Sed egestas tincidunt ligula, eget varius velit finibus nec. Vivamus vitae ipsum eget elit consequat dignissim mattis ut ex. Vestibulum vitae metus luctus, maximus diam a, laoreet erat. Aenean ornare risus mi, nec egestas sapien congue mattis. Nullam ultrices vestibulum est a sollicitudin. Duis nec volutpat mauris, et maximus urna. Curabitur ullamcorper metus ut metus pretium, varius sagittis ligula aliquam. Morbi aliquet, diam vitae varius placerat, orci lorem iaculis elit, sit amet tempor leo mauris at tellus. Praesent et turpis fermentum, imperdiet enim at, convallis tellus. Ut at velit non nisl pulvinar consectetur.
                Duis ac pulvinar lectus. Vestibulum dui velit, auctor eget semper sit amet, suscipit nec tellus. Duis id ornare massa, in mollis nisl. Aenean pharetra sem ut mauris luctus, non porttitor quam volutpat. Donec hendrerit sit amet erat id dapibus. Cras id velit sed leo rutrum maximus. Cras commodo enim ac vehicula feugiat. Morbi volutpat ultrices tellus, ut congue velit pellentesque at. Mauris varius massa nec lorem tincidunt molestie. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nullam varius varius sapien, vel porta purus luctus ac. Phasellus id est magna. Donec sit amet lacinia eros. Nunc blandit feugiat condimentum. Integer ut risus vitae massa viverra egestas et ac urna.                
                Cras blandit tellus ante, sit amet luctus odio tempor et. Nunc a imperdiet ex. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer in dictum magna. Vivamus sollicitudin interdum libero a pellentesque. Sed accumsan lobortis massa, vel malesuada est faucibus sed. Pellentesque sed augue consequat, semper metus ac, bibendum dui. Sed in nulla ac odio semper lobortis. Interdum et malesuada fames ac ante ipsum primis in faucibus. Phasellus nunc sem, molestie vitae consectetur ut, ullamcorper dignissim orci. Mauris nec nisl non nibh semper molestie. Pellentesque eu lorem in risus gravida tempor. Nunc sagittis facilisis quam. Nullam sit amet tortor fringilla, venenatis enim vel, cursus dolor.                
                Nunc pretium nisl nec risus mollis, vitae faucibus massa tristique. In non nibh id mauris placerat viverra. In imperdiet justo imperdiet magna viverra pharetra. Sed tempor placerat nibh, ut blandit libero. Proin odio magna, lacinia vel magna at, pellentesque tempor neque. Mauris mattis eu velit ac venenatis. Sed id odio auctor, molestie ante vitae, euismod nunc. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque cursus neque eu sem tempor, eget accumsan sem varius. Suspendisse a dolor quis mauris tristique lacinia non sed augue. Nam rhoncus, diam eget suscipit hendrerit, sem augue iaculis est, nec elementum felis velit nec nisl. Phasellus nunc est, pharetra non vestibulum at, rutrum a quam. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Integer lacinia gravida mi, sed rutrum mi efficitur et. Integer dapibus velit ac est pulvinar ultricies. Nunc luctus risus vitae massa facilisis, sed egestas odio hendrerit.                
                Sed sapien mi, fermentum non imperdiet ac, consequat eget velit. Donec ultrices aliquam maximus. Integer quis eleifend magna. Etiam lobortis velit vel risus mollis aliquam. Pellentesque consectetur aliquam nisi, vel vulputate elit. Nulla turpis urna, vulputate ut quam vitae, pulvinar fringilla nibh. Praesent porttitor tortor at consequat facilisis. Vestibulum vel lectus ut orci interdum finibus quis nec dui. Aenean nec nisl sem. Fusce vestibulum ex augue, id fermentum elit congue non. Phasellus gravida, velit in rutrum facilisis, lectus dolor luctus nisl, vel suscipit est risus in nibh. Etiam sit amet dolor est. Sed id maximus quam.";

        for ($i=0; $i < 20; $i++) { 
            // generar palabra random 
            shuffle($alfabeto);
            $len = rand(5, 10);
            $newWord = mb_substr(implode($alfabeto), 0, $len);
            $entrada = dpEntrada_AddToDiccionario($newWord, $diccionario->id);
            echo "entrada: $entrada \n";
            $n_acepciones = rand(1,3);
            echo $n_acepciones."\n";
            
            echo "--\n";
            for ($j=0; $j < $n_acepciones; $j++) { 
                $ini = rand(0,568);
                $fin = rand($ini,568);
                $textoAcepcion = mb_substr($lipsum,$ini,$fin);
                $datosAcepcion = new \stdClass();
                $datosAcepcion->dic_entrada_id = $entrada->id;
                $datosAcepcion->definicion = $textoAcepcion;

                $datosAcepcion->orden = $j+1;
                $datosAcepcion->cat_gramatical_id = rand(1,10) ;
                $datosAcepcion->cat_gramatical_id = $datosAcepcion->cat_gramatical_id==10 ? null : $datosAcepcion->cat_gramatical_id;
                $datosAcepcion->genero_id = rand(10,15);
                $datosAcepcion->genero_id = $datosAcepcion->genero_id==15 ? null : $datosAcepcion->genero_id;
                $datosAcepcion->numero_id = rand(15,17);
                $datosAcepcion->numero_id = $datosAcepcion->numero_id==17 ? null : $datosAcepcion->numero_id;
                // $datosAcepcion->tipologia_id = 1;
                $datosAcepcion->idioma_palabra = $newWord.$newWord;
                // $datosAcepcion->idioma_id = rand(56,250); 
                $datosAcepcion->idioma_id = 57; 
                $datosAcepcion->frase_ejemplo = 'frase ejemplo';
                // $acepcion->estado = config('ctes.estados.activo');
                
                // FIXME: temporalmente desactivado por que falla por el campo tipologia_id
                $acepcion = dpAcepcion_Add($datosAcepcion);
                echo 'acepción' . $acepcion . "\n";
            }

            if ($entrada) {
                echo "creada entrada : " . $newWord . "\n";
            }
        }

        // // Crear dos dicionairos de aula 
        // // $dic1 = new \stdClass();
        // $dic1 = [
        //     'tipoDiccionario' => 1,
        //     'nombreDiccionario' => 'dic1',
        //     'grupoLetra' => 'A',
        //     'ensenanza' => '1', // primaria
        //     'estudio' => '1', // 1ro primaria
        //     'areaMateria' => '1', // 1 multi area 2 artes escenicas 30 ambito lenguas extrajeras..
        //     'correoAvisos' => 'correo@example.com',
        //     'nivelEstudioFinal'
        //     'grupoFinal'
        //     'codigoAleatorio'

        // ];
        // $create = createOrUpdateDicAula($dic1);

        // Meter entradas en dic de aula
        // EnvioEntrada::all()->last()
        // EnvioEntrada::all()->last()->id
        // New DicAulaEntrada
        // $dae = new DicAulaEntrada;
        // $dae->envio_entrada_id: = 260;

        // Enviar entradas al diccionario:
        
        // Pubilcar entradas
        // publicarEntrada($dicAulaId,$envioEntradaId)

    }


}