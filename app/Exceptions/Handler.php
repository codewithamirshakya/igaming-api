<?php

namespace App\Exceptions;

use App\Helpers\Response\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    use Response;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render error exception
     */
    public function render($request, Throwable $exception)
    {

//        if ($exception instanceof ModelNotFoundException) {
//            return $this->errorResponse($exception->getMessage());
//        }
//
//        if ($exception instanceof ValidationException) {
////            return $this->errorResponse($exception, 422);
//        }

        return parent::render($request, $exception);
    }
}
