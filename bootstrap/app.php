<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Ensure API routes return JSON responses
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
        
        // Add status code to all API responses
        $middleware->api(append: [
            \App\Http\Middleware\AddStatusCodeToResponse::class,
        ]);
        
        // Add JSON response middleware for API routes
        $middleware->group('api', [
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle API exceptions to return JSON responses
        $exceptions->render(function (\Illuminate\Http\Exceptions\HttpResponseException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], $e->getResponse()->getStatusCode());
            }
        });
        
        // Handle authentication exceptions for API routes
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'status_code' => 401,
                ], 401);
            }
        });
        
        // Handle method not allowed for API routes
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Method not allowed',
                    'error' => 'The requested method is not allowed for this endpoint',
                    'status_code' => 405,
                ], 405);
            }
        });
        
        // Handle route not found for API routes
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Not found',
                    'error' => 'The requested endpoint was not found',
                    'status_code' => 404,
                ], 404);
            }
        });
    })->create();
