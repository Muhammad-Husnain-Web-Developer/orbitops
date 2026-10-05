<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Option lists for quick-create forms, fetched only when a form opens so they
 * are not shipped with every page. Scoped to the active workspace.
 */
class LookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'clients' => $user->can('clients.view')
                ? Client::orderBy('name')->get(['id', 'name', 'status', 'currency'])
                : [],
            'projects' => $user->can('projects.view')
                ? Project::with('client:id,name')
                    ->orderByRaw('status in (?, ?) asc', [ProjectStatus::Completed->value, ProjectStatus::Cancelled->value])
                    ->orderBy('name')
                    ->get(['id', 'name', 'code', 'color', 'client_id', 'status', 'hourly_rate'])
                    ->map(fn ($project) => [
                        'id' => $project->id,
                        'name' => $project->name,
                        'code' => $project->code,
                        'color' => $project->color,
                        'client_id' => $project->client_id,
                        'client' => $project->client?->name,
                        'status' => $project->status->value,
                        'hourly_rate' => $project->hourly_rate,
                    ])
                : [],
            'members' => $this->workspace()->teamMembers()->orderBy('name')->get()
                ->map(fn ($member) => $member->only(['id', 'name', 'initials', 'avatar_url', 'title'])),
        ]);
    }
}
