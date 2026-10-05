<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InvoiceStatus;
use App\Enums\TaskStatus;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Support\PortalPresenter;
use Illuminate\Http\Request;
use Inertia\Response;

class PortalProjectController extends PortalController
{
    public function index(): Response
    {
        return inertia('Portal/Projects/Index', [
            'projects' => $this->projects()->withProgress()->with(['milestones', 'owner'])
                ->orderByRaw('completed_at is not null')->orderBy('due_date')->get()
                ->map(fn (Project $project) => [
                    ...PortalPresenter::project($project),
                    'milestones_total' => $project->milestones->count(),
                    'milestones_done' => $project->milestones->where('status', 'completed')->count(),
                ]),
        ]);
    }

    public function show(Request $request, Project $project): Response
    {
        $this->ensureOwn($project->client_id);

        $project->loadCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', TaskStatus::Done)])
            ->load(['milestones', 'owner']);

        $tasks = Task::with('project:id,name,color,code')->where('project_id', $project->id)->where('visible_to_client', true)
            ->orderByRaw('due_date is null')->orderBy('due_date')->get();

        return inertia('Portal/Projects/Show', [
            'project' => PortalPresenter::project($project),
            'milestones' => $project->milestones->map(fn ($milestone) => PortalPresenter::milestone($milestone))->values(),
            'tasks' => $tasks->map(fn (Task $task) => PortalPresenter::task($task)),
            'files' => $project->attachments()->with('uploader')->where('visible_to_client', true)->latest()->get()
                ->map(fn ($file) => PortalPresenter::file($file)),
            'messages' => $project->messages()->with('author')->oldest()->get()
                ->map(fn ($comment) => PortalPresenter::message($comment, $this->clientUserIds(), $request->user()->id)),
            'invoices' => InvoiceResource::collection(Invoice::where('project_id', $project->id)->where('client_id', $this->clientId())
                ->where('status', '!=', InvoiceStatus::Draft)->latest('issue_date')->get()),
            'tab' => in_array($request->query('tab'), ['overview', 'tasks', 'files', 'messages', 'invoices'], true) ? $request->query('tab') : 'overview',
        ]);
    }
}
