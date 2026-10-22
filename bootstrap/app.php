<?php

use App\Http\Middleware\Api\V1\ConvertTokenHeader;
use App\Models\Article;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ConvertTokenHeader::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request) =>
            $request->is('api/*') ? null : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Validation errors in the RealWorld format: {"errors": {"field": ["message"]}}
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                $status = collect($e->errors())->flatten()->contains("has already been taken")
                    ? 409
                    : 422;

                return response()->json(['errors' => $e->errors()], $status);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['errors' => ['token' => ['is missing']]], 401);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $resource = match (true) {
                $request->is('api/profiles/*') => 'profile',
                $request->is('api/articles/*') && $request->is('api/articles/*/comments') => 'comment',
                $request->is('api/articles/*') => 'article',
                default => 'resource',
            };

            return response()->json(['errors' => [$resource => ['not found']]], 404);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $resource = match (true) {
                    $request->is('api/profiles/*') => 'profile',
                    $request->is('api/articles/*/comments/*')
                        && $request->route('article') instanceof Article => 'comment',
                    $request->is('api/articles/*') => 'article',
                    default => 'resource',
                };

                return response()->json(['errors' => [$resource => ['not found']]], 404);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*') && $e->getStatusCode() === 403) {
                $resource = $request->is('api/articles/*/comments/*') ? 'comment' : 'article';

                return response()->json(['errors' => [$resource => ['forbidden']]], 403);
            }
        });

        // Always respond with JSON on API routes (e.g. 401 instead of a redirect to "login")
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
