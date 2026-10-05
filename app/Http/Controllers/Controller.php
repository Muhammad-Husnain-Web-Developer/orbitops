<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * The tenant resolved for this request.
     */
    protected function workspace(): Workspace
    {
        return app(CurrentWorkspace::class)->get();
    }

    /**
     * Queue a one-time toast for the next page the user sees.
     */
    protected function toast(string $message, string $type = 'success', ?string $description = null): void
    {
        Inertia::flash('toast', array_filter([
            'type' => $type,
            'message' => $message,
            'description' => $description,
        ]));
    }
}
