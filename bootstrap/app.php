<?php

use App\Exceptions\BusinessException;
use App\Http\Middleware\CheckPassportToken;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\NoBrowserCacheMiddleware;
use App\Http\Middleware\RedirectIfAuthenticatedPassport;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            NoBrowserCacheMiddleware::class,
            StartSession::class,
            ShareErrorsFromSession::class
        ]);

        $middleware->alias([
            'auth.passport' => CheckPassportToken::class,
            'guest.passport' => RedirectIfAuthenticatedPassport::class,
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ========================================
        // MANEJO DE EXCEPCIONES DE NEGOCIO
        // ========================================

        // Business exceptions ya tienen su propio render() y report()
        // Laravel las manejará automáticamente

        // ========================================
        // VALIDACIÓN DE LARAVEL
        // ========================================
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'VALIDATION_ERROR',
                    'message' => 'Error de validación',
                    'errors' => $e->errors(),
                    'timestamp' => now()->toIso8601String()
                ], 422);
            }
        });

        // ========================================
        // MODELO NO ENCONTRADO
        // ========================================
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'error' => 'NOT_FOUND',
                    'message' => "{$model} no encontrado",
                    'timestamp' => now()->toIso8601String()
                ], 404);
            }
        });

        // ========================================
        // RUTA NO ENCONTRADA
        // ========================================
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'ROUTE_NOT_FOUND',
                    'message' => 'Endpoint no encontrado',
                    'timestamp' => now()->toIso8601String()
                ], 404);
            }
        });

        // ========================================
        // ERRORES INESPERADOS
        // ========================================
        $exceptions->render(function (\Throwable $e, $request) {
            // No manejar BusinessExceptions aquí (ya tienen su render())
            if ($e instanceof BusinessException) {
                return null; // Laravel usará el render() de BusinessException
            }

            if ($request->expectsJson() && !config('app.debug')) {
                // En producción, ocultar detalles del error
                \Log::error('Unhandled exception', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'user_id' => auth()->id() ?? null,
                    'request_url' => $request->fullUrl(),
                    'request_method' => $request->method(),
                    'ip_address' => $request->ip()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'UNEXPECTED_ERROR',
                    'message' => 'Ha ocurrido un error inesperado. Por favor, contacta al administrador.',
                    'timestamp' => now()->toIso8601String()
                ], 500);
            }

            // En desarrollo o peticiones no-JSON, dejar que Laravel maneje normalmente
            return null;
        });
    })->create();
