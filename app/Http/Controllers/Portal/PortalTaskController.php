<?php

namespace App\Http\Controllers\Portal;

use App\Models\Task;
use App\Support\PortalPresenter;
use Illuminate\Http\Request;
use Inertia\Response;

class PortalTaskController extends PortalController
{
    /**
     * Tasks the team chose to share, across the client's projects.
     */
    public function __invoke(Request $request): Response
    {
        $project = $request->query('project');

        $tasks = Task::with('project:id,name,color,code')
            ->whereIn('project_id', $this->projects()->select('id'))
            ->where('visible_to_client', true)
            ->when(is_numeric($project), fn ($query) => $query->where('project_id', $project))
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->get();

        return inertia('Portal/Tasks', [
            'tasks' => $tasks->map(fn (Task $task) => PortalPresenter::task($task)),
            'projects' => $this->projects()->orderBy('name')->get(['id', 'name', 'color']),
            'filters' => ['project' => is_numeric($project) ? (string) $project : ''],
        ]);
    }
}
