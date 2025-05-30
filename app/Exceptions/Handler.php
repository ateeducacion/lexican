<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        // Si hay un error con el token ( o si ha caducado )
        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {            
            $previousUrl = app('url')->previous();

            customLoggin(
                config('ctes.log_levels.error'),
                config('ctes.log_types.get_external_token'),
                ['file' => __FILE__, 'line' => __LINE__],
                $exception->getMessage(),
                PHP_EOL . 'url: ' . $request->url() .
                PHP_EOL . 'request: ' .json_encode($request->all(), JSON_PRETTY_PRINT) . 
                PHP_EOL . 'previousUrl: ' . $previousUrl
            );

            // no esta redirigiendo a la url que debería sino a a la pagina inicial:
            // return redirect( $previousUrl )
            //     ->withInput($request->except('password', '_token'))
            //     ->withError('This Validation token has been expired. Please try again');
        }

        
        return parent::render($request, $exception);
    }
}
