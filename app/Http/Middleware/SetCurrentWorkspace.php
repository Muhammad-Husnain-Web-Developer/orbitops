<?php

namespace App\Http\Middleware;

use App\Support\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the signed-in user's active workspace and makes it the tenant for this request.
 * Must run before route model binding so tenant scopes apply to bound models.
 */
class SetCurrentWorkspace
{
    public function __construct(protected CurrentWorkspace $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $memberships = $user->memberships()->where('status', 'active')->with('workspace')->get();
        $user->setRelation('memberships', $memberships);

        $membership = $memberships->firstWhere('workspace_id', $user->current_workspace_id) ?? $memberships->first();

        if (! $membership) {
            return $request->routeIs('onboarding.*')
                ? $next($request)
                : redirect()->route('onboarding.workspace');
        }

        if ($user->current_workspace_id !== $membership->workspace_id) {
            $user->forceFill(['current_workspace_id' => $membership->workspace_id])->save();
        }

        $this->current->set($membership->workspace);

        $this->touchActivity($user, $membership);

        return $next($request);
    }

    /**
     * Record presence at most every five minutes to keep writes cheap.
     */
    protected function touchActivity($user, $membership): void
    {
        if ($membership->last_active_at === null || $membership->last_active_at->lt(now()->subMinutes(5))) {
            $membership->forceFill(['last_active_at' => now()])->saveQuietly();
            $user->forceFill(['last_active_at' => now()])->saveQuietly();
        }
    }
}
