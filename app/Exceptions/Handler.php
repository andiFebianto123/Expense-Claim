<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Illuminate\Session\TokenMismatchException;
use Backpack\CRUD\app\Exceptions\AccessDeniedException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        $this->renderable(function (NotFoundHttpException $exception) {
            $previous = $exception->getPrevious();
            if($previous != null && $previous instanceof ModelNotFoundException){
                return abort(404, trans('custom.model_not_found'));
            }
        });

        // $this->renderable(function (AccessDeniedException $exception, $request) {
        //     if ($request->wantsJson()) {
        //         return response()->json(['message' => trans('custom.error_permission_message')], 403);
        //     } else {
        //         abort(403, trans('custom.error_permission_message'));
        //     }
        // });

        $this->renderable(function (TokenMismatchException $exception, $request) {
            if (!$request->wantsJson()) {
                \Alert::error(trans('custom.token_invalid'))->flash();
                return redirect(backpack_url('login'));
            }
        });
    }
}
