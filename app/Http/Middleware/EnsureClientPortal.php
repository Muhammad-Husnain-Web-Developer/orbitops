<?php

namespace App\Http\Middleware;

use App\Support\CurrentWorkspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only client users (memberships linked to a client record) can enter the portal.
 */
class EnsureClientPortal
{
    public function __construct(protected CurrentWorkspace $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        $membership = $request->user()?->membershipFor($this->current->id());

        if (! $membership?->isClient()) {
            return redirect()->route('dashboard');
        }

        $request->attributes->set('portal_client_id', $membership->client_id);

        return $next($request);
    }
}
