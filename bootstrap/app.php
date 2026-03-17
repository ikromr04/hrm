<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (Application $app): void {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/api_v1.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $errors[] = [
                        'status' => 422,
                        'title' => __('api.unprocessable_content.title'),
                        'detail' => $message
                    ];
                }
            }

            return response()->json([
                'jsonapi' => ['version' => config('jsonapi.version')],
                'errors' => $errors
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            return response()->json([
                'jsonapi' => ['version' => config('jsonapi.version')],
                'errors' => [[
                    'status' => Response::HTTP_UNAUTHORIZED,
                    'title' => __('api.unauthenticated.title'),
                    'detail' => __('api.unauthenticated.detail'),
                ]]
            ], Response::HTTP_UNAUTHORIZED);
        });
    })->create();
