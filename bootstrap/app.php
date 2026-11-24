<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Todo error en /api/* (404, 405, 500...) se responde en JSON.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson(),
        );

        // Errores de validación: {"success": false, "message": "..."} con HTTP 422.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->validator->errors()->first(),
                ], $e->status);
            }
        });

        // Errores HTTP (404, 405...): mismo formato {"success": false, "message": "..."}.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*')) {
                $messages = [
                    404 => 'La ruta solicitada no existe.',
                    405 => 'Método HTTP no permitido para esta ruta.',
                ];

                return response()->json([
                    'success' => false,
                    'message' => $messages[$e->getStatusCode()] ?? Response::$statusTexts[$e->getStatusCode()],
                ], $e->getStatusCode(), $e->getHeaders());
            }
        });
    })->create();
