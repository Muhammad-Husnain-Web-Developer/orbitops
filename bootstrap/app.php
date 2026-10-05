<?php

use App\Http\Middleware\EnsureClientPortal;
use App\Http\Middleware\EnsureTeamMember;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ProtectDemoAccounts;
use App\Http\Middleware\ResolveTokenWorkspace;
use App\Http\Middleware\SetCurrentWorkspace;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ProtectDemoAccounts::class,
        ]);

        $middleware->alias([
            'workspace' => SetCurrentWorkspace::class,
            'team' => EnsureTeamMember::class,
            'portal' => EnsureClientPortal::class,
            'api.workspace' => ResolveTokenWorkspace::class,
        ]);

        // The tenant must be resolved before route model binding so bound models are workspace-scoped.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: SetCurrentWorkspace::class);
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: ResolveTokenWorkspace::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || ($request->expectsJson() && ! $request->header('X-Inertia')),
        );

        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $status = $response->statusCode();

            if ($status === 419) {
                Inertia::flash('toast', ['type' => 'warning', 'message' => 'Your session expired. Please try again.']);

                return back();
            }

            if (in_array($status, [403, 404, 429, 503], true) || ($status >= 500 && ! config('app.debug'))) {
                return $response->render('Errors/Error', ['status' => $status])->withSharedData();
            }
        });
    })->create();
