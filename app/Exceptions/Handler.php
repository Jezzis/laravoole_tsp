<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        $e = $this->prepareException($e);
        if ($e instanceof HttpResponseException) {
            $response = $e->getResponse();
        } elseif ($e instanceof AuthenticationException) {
            $response = $this->unauthenticated($request, $e);
        } elseif ($e instanceof ValidationException) {
            $response = $this->convertValidationExceptionToResponse($e, $request);
        } else {
            $response = $this->prepareResponse($request, $e);
        }

        $json = $this->renderException($request, $e);
        return response()->json($json, $response->getStatusCode());
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Auth\AuthenticationException $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest(route('login'));
    }

    private function renderException($request, Exception $e)
    {
        $err = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
            'url' => $request->url(),
            'input' => $request->all(),
        ];

        return [
            'code' => array_get($err, 'code', 1),
            'msg' => $err['message'],
            'debug' => [
                'file' => $err['file'],
                'line' => $err['line'],
                'input' => $err['input'],
                'trace' => $this->formatTrace($e),
            ]
        ];

    }

    private function formatTrace(\Exception $e)
    {
        $debugTraceList = [];
        $traceList = $e->getTrace();
        foreach ($traceList as $index => $trace) {
            if (count($debugTraceList) > 35) {
                $debugTraceList[] = '...';
                break;
            }

            $debugTrace = '';

            // file
            if (empty($trace['file'])) {
                continue;
            }
            $file = basename($trace['file']);
            $debugTrace .= $file . ':' . $trace['line'];

            // class
            $class = !empty($trace['class']) ? $trace['class'] : 'core';
            if ($class == 'core' || $class == 'ReflectionMethod') {
                continue;
            }

            // args
            $argv = '';
            foreach ($trace['args'] as $arg) {
                $type = gettype($arg);
                $argv = ',' . $type;
            }

            $debugTrace = $class . '->' . $trace['function'] . '(' . substr($argv, 1) . '); [' . $debugTrace . ']';
            $debugTraceList[] = $debugTrace;
        }
        return $debugTraceList;
    }


}
