<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops visitors from locking everyone else out of the shared demo accounts.
 */
class ProtectDemoAccounts
{
    /**
     * Routes that change how the demo personas sign in, or remove the demo workspace.
     */
    protected const BLOCKED = [
        'user-password.update',
        'two-factor.enable',
        'two-factor.confirm',
        'two-factor.disable',
        'two-factor.regenerate-recovery-codes',
        'settings.security.sessions.destroy',
        'settings.danger.leave',
        'settings.danger.transfer',
        'settings.danger.destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $blocked = in_array($request->route()?->getName(), self::BLOCKED, true)
            || ($request->routeIs('settings.general.update') && $request->filled('email') && strcasecmp((string) $request->input('email'), (string) $user?->email) !== 0);

        if (! $user?->isDemo() || ! $blocked) {
            return $next($request);
        }

        $message = 'This is a shared demo account, so sign-in and workspace-wide changes are disabled. Create your own workspace to try them.';

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['message' => $message], 403);
        }

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Not available in the demo', 'description' => $message]);

        return back();
    }
}
