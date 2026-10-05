<?php

namespace App\Http\Middleware;

use App\Support\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps client-portal users out of the internal workspace.
 */
class EnsureTeamMember
{
    public function __construct(protected CurrentWorkspace $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $membership = $request->user()?->membershipFor($this->current->id());

        if ($membership?->isClient()) {
            return redirect()->route('portal.dashboard');
        }

        return $next($request);
    }
}
