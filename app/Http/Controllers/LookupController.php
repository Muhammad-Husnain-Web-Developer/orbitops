<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
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

        // Tasks of one project, for the timer and time entry forms.
        if ($request->filled('project')) {
            return response()->json([
                'tasks' => $user->can('tasks.view')
                    ? Task::where('project_id', $request->integer('project'))
                        ->orderByRaw('status = ? asc', [TaskStatus::Done->value])
                        ->orderBy('number')
                        ->limit(200)
                        ->get(['id', 'title', 'number', 'status', 'project_id'])
                        ->map(fn (Task $task) => ['id' => $task->id, 'title' => $task->title, 'done' => $task->status === TaskStatus::Done])
                    : [],
            ]);
        }

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
