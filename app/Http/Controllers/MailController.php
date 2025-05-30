<?php

namespace App\Http\Controllers;

use Mail;


/**
 * MailController
 * 
 * @category Laravel
 * @package  App\Http\Controllers
 * @author   Javier Pérez Batista <javier.perez@altia.es>
 * @access   public
 * @version  Release: <package_version>
 */
class MailController extends Controller
{
    /**
     * Esta función se encarga de mandar un mail a uno o varios contenedores siendo el contenido del
     * mail un texto plano.
     *
     * @author Trillo <josecarlos.trillo@altia.es>
     * @version 1.0.0
     *
     * @return void
     */
    public function sendPlainTextMail() {

        $emails     = ['trillo.altia@gmail.com'];
        $emailsCC   = [];
        $emailsBCC  = [];

        // Aquí definimos el contenido de las variables que irán en la vista.
        $data = array(
                    'nombre'    =>"Inma",
                    'mensaje'   =>"Eres la mejor!!!"
                );
     
        Mail::send(['text'=>'layouts.partials.mail.mailview'], $data, function($message) use ($emails, $emailsCC, $emailsBCC) {
            $message->to($emails);
            $message->cc($emailsCC);
            $message->bcc($emailsBCC);
            $message->subject('Mail de prueba de Láravel');
            //$message->attach('C:\laravel\public\uploads\test.txt');
            //$message->attach('C:\laravel\public\uploads\image.png');
            $message->from('noreply@canarismos.com','Diccionario');
        });

        return "Email básico en texto plano enviado. Comprueba el Inbox.";
    }

    /**
     * Esta función se encarga de mandar un mail a uno o varios contenedores siendo el contenido del
     * mail un HTML con formato.
     *
     * @author Trillo <josecarlos.trillo@altia.es>
     * @version 1.0.0
     *
     * @return void
     */
    public function sendHtmlMail() {

        /*
        $emails     = ['trillo.altia@gmail.com'];
        $emailsCC   = [];
        $emailsBCC  = [];
        //$emailsBCC  = ['inma.h.ballesteros@gmail.com'];

        // Aquí definimos el contenido de las variables que irán en la vista.
        $data = array(
                    'nombre'    =>"Inma",
                    'mensaje'   =>"Eres la mejor!!!. Este mensaje nos llega a ti y a mi. Pero a ti en copia oculta."
                );
     
        Mail::send('layouts.partials.mail.mailview', $data, function($message) use ($emails, $emailsCC, $emailsBCC) {
            $message->to($emails);
            $message->cc($emailsCC);
            $message->bcc($emailsBCC);
            $message->subject('Mail de prueba de Láravel');
            //$message->attach('C:\laravel\public\uploads\test.txt');
            //$message->attach('C:\laravel\public\uploads\image.png');
            $message->from('noreply@canarismos.com','Diccionario');
         });

        return "Email con HTML enviado. Comprueba el Inbox.";
        */

        $emailList  = ['trillo.altia@gmail.com', 'inma.h.ballesteros@gmail.com'];
        $data = array(
            'nombre'    =>"Inma",
            'mensaje'   =>"Eres la mejor!!!. Este mensaje nos llega a ti y a mi. Pero a ti en copia oculta."
        );
        $viewMail   = "layouts.partials.mail.mailview";
        $asunto     = "este es un mail de prueba desde el helper";
        $emailFrom  = "Pedro@gmail.com";

        return sendHtmlMailwithView ($data, $viewMail, $asunto, $emailList, null, null, $emailFrom, null);
    }

}
