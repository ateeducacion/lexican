<?php

if (!function_exists('sendHtmlMailwithView')) {
    /**
     * Función genérica de envío de e-mails. Se ha de añadir obligatoriamente un objeto $data con los parámetros configurables de la vista del mail.
     * Opcionalmente se podrán enviar mails para copia oculta o copia visible y un fichero adjunto.
     *
     * @author josecarlos.trillo@altia.es
     * @version 1.0.0
     * 
     * @param array $dataMail Array que contiene los parámetros configurables de la vista del e-mail. ej:
     *              $data = array(
     *                  'nombre'    =>"José Luis",
     *                  'mensaje'   =>"Te ha sido otorgada la máxima condecoración que se concede en este centro."
     *              );
     * @param string $viewMail String con la vista que se utilizará como plantilla para el e-mail. Ej: "layouts.partials.mail.mailview"
     * @param string $asunto El título del e-mail.
     * @param array $emailList El e-mail o e-mails que se enviarán separados por comas en un array ['mail1','mail2'].
     * @param array $emailsCC El e-mail o e-mails que se enviarán separados por comas en un array ['mail1','mail2'] en copia visible (OPCIONAL).
     * @param array $emailsBCC El e-mail o e-mails que se enviarán separados por comas en un array ['mail1','mail2'] en copia oculta (OPCIONAL).
     * @param string $emailFrom El e-mail del remitente. (OPCIONAL). Si está definido por configuración (.ENV) no se podrá cambiar.
     * @param string $attached Ruta del archivo que será adjuntado al e-mail. ej: "c:\carpeta\archivo.txt" (OPCIONAL).
     * 
     * @return void Envía el e-mail usando plantilla HTML.
     */
    function sendHtmlMailwithView ($dataMail, $viewMail, $asunto, $emailList = [], $emailCCList = [], $emailBCCList = [], $emailFrom = null, $attached = null) {

        $emails     = $emailList;
        $emailsCC   = [];
        $emailsBCC  = [];

        if (!empty($emailCCList)) {
            $emailsCC = $emailCCList;
        }

        if (!empty($emailBCCList)) {
            $emailsBCC = $emailBCCList;
        }
     
        Mail::send($viewMail, $dataMail, function($message) use ($emails, $emailsCC, $emailsBCC, $asunto, $emailFrom) {
            $message->to($emails);
            $message->cc($emailsCC);
            $message->bcc($emailsBCC);
            $message->subject($asunto);

            if (!empty($emailFrom)) {
                $message->from($emailFrom);
            }
            if (!empty($attached)) {
                $message->attach($attached);
            }
         });

        return "Email con HTML enviado. Comprueba el Inbox.";
    }
}


