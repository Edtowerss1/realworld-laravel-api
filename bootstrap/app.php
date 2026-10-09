<?php

use App\Http\Middleware\Api\V1\ConvertTokenHeader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ConvertTokenHeader::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Validation errors in the RealWorld format: {"errors": {"field": ["message"]}}
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['errors' => $e->errors()], 422);
            }
        });

        // Always respond with JSON on API routes (e.g. 401 instead of a redirect to "login")
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
