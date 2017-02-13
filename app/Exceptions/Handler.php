<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

/**
 * Class Handler
 * @package App\Exceptions
 */
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        MethodNotAllowedHttpException::class,
        ValidationException::class,
        DynamicValidationException::class,
        FileUploadException::class,
        TokenExpiredException::class,
        NotFoundHttpException::class
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        if ($e instanceof JWTException) {
            return response()->json(
                [
                    'error' => true,
                    'errors' => [
                        'JWT invalid'
                    ]
                ],
                400
            );
        } else if ($e instanceof TokenExpiredException) {
            return response()->json(
                [
                    'error' => true,
                    'errors' => [
                        'JWT expired'
                    ]
                ],
                403
            );
        } else if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json(
                [
                    'error' => true,
                    'errors' => [
                        $e->getMessage() ? $e->getMessage() : 'Method not allowed'
                    ]
                ],
                403
            );
        } else if ($e instanceof DynamicValidationException) {
            return response()->json(
                ['errors' => $e->getMessages()],
                400
            );
        } else if ($e instanceof NotFoundHttpException) {
            return response()->json(
                ['errors' => 'Route not found'],
                501
            );
        } else if ($e instanceof FileUploadException) {
            return response()->json(
                ['errors' => $e->getMessage()],
                400
            );
        }

        $errors = ['Internal server error.'];

        if (empty($e->getMessage()) === false) {
            $errors[] = $e->getMessage();
        }

        $response = ['errors' =>$errors];

        if (getenv('APP_DEBUG')) {
            $response['exceptionClass'] = get_class($e);
            $response['line'] = $e->getLine();
            $response['file'] = $e->getFile();
            $response['trace'] = $e->getTrace();
        }

        return response()->json(
            $response,
            500
        );
    }
}
