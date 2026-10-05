<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API tokens are issued for one workspace (a "workspace:{id}" ability). Requests made
 * with the token only ever see that workspace, and only while the user still belongs to it.
 */
class ResolveTokenWorkspace
{
    public function __construct(protected CurrentWorkspace $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        $workspaceId = collect($token?->abilities ?? [])
            ->first(fn ($ability) => str_starts_with($ability, 'workspace:'));

        $workspace = $workspaceId ? Workspace::find((int) substr($workspaceId, 10)) : null;
        $membership = $workspace ? $user->membershipFor($workspace->id) : null;

        if (! $workspace || ! $membership || $membership->isClient() || $membership->status !== 'active') {
            return response()->json(['message' => 'This token is not valid for any workspace you belong to.'], 403);
        }

        $this->current->set($workspace);

        return $next($request);
    }
}
