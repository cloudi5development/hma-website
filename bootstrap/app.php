<?php

use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Admin panel routes mounted inside the "web" middleware group.
            Route::middleware('web')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Content-Security-Policy on every HTML response, so a third-party ad or
        // tracker cannot load from an origin the site does not use. Configured
        // in config/csp.php; CSP_ENABLED=false turns it off.
        $middleware->append(\App\Http\Middleware\ContentSecurityPolicy::class);

        // Switch off events and course batches past their start date on the
        // first web request of the day — see App\Support\ContentExpiry.
        $middleware->web(append: [\App\Http\Middleware\ExpirePastContent::class]);

        $middleware->alias([
            // Force a JSON response on any route/group that must always
            // answer in JSON, even when the client forgets the Accept header.
            'force.json' => \App\Http\Middleware\ForceJsonResponse::class,

            // Session-based guard for the admin panel (temporary until the real
            // auth module is built — see Backend\AuthController).
            'admin.auth' => \App\Http\Middleware\AdminAuthenticate::class,

            // Per-module access. Derives the module from the route name, so it
            // guards the whole panel from one place — see Support\AdminModules.
            'admin.module' => \App\Http\Middleware\EnsureModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render exceptions as the standard ApiResponse envelope for API /
        // JSON requests; web requests keep Laravel's default HTML error pages.
        $isApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $isApi($request));

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('Validation failed.', $e->errors(), 422);
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('Unauthenticated.', null, 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error($e->getMessage() ?: 'This action is unauthorized.', null, 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('Resource not found.', null, 404);
            }
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('Resource not found.', null, 404);
            }
        });

        $exceptions->render(function (QueryException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                report($e);

                return ApiResponse::error('A database error occurred.', app()->hasDebugModeEnabled() ? $e->getMessage() : null, 500);
            }
        });

        // Application-thrown API errors carry their own message/errors/status.
        $exceptions->render(function (\App\Exceptions\ApiException $e, Request $request) {
            return ApiResponse::error($e->getMessage(), $e->getErrors(), $e->getStatusCode());
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if ($isApi($request) && ! $e instanceof HttpExceptionInterface) {
                report($e);

                return ApiResponse::error('Server error.', app()->hasDebugModeEnabled() ? $e->getMessage() : null, 500);
            }
        });
    })->create();
