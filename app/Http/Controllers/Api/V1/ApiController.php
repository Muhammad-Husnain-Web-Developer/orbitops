<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TaskStatus;
use App\Events\TaskAssigned;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TimeEntryResource;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Read access to a workspace, plus task writes, for tokens created under Settings → API.
 * Every endpoint checks the token's ability and the user's workspace permissions.
 */
class ApiController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $workspace = $this->workspace();

        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'email']),
            'workspace' => $workspace->only(['id', 'name', 'slug', 'currency']),
            'abilities' => array_values(array_intersect($request->user()->currentAccessToken()->abilities ?? [], ['read', 'write'])),
        ]);
    }

    public function projects(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Project::class);

        return ProjectResource::collection(
            Project::withProgress()->with('client:id,name')
                ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
                ->orderBy('name')->paginate($this->perPage($request))
        );
    }

    public function project(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        return new ProjectResource($project->load(['client:id,name', 'milestones'])->loadCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', TaskStatus::Done)]));
    }

    public function clients(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Client::class);

        return ClientResource::collection(Client::orderBy('name')->paginate($this->perPage($request)));
    }

    public function tasks(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        $request->validate([
            'project' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'assignee' => ['nullable', 'integer'],
        ]);

        return TaskResource::collection(
            Task::with(['project:id,name,code', 'assignee'])
                ->when($request->integer('project'), fn ($query, $project) => $query->where('project_id', $project))
                ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
                ->when($request->integer('assignee'), fn ($query, $assignee) => $query->where('assignee_id', $assignee))
                ->latest()->paginate($this->perPage($request))
        );
    }

    public function storeTask(StoreTaskRequest $request): JsonResponse
    {
        $this->requireWrite($request);

        $data = $request->validated();
        $task = Task::create([
            ...collect($data)->except('estimate_hours')->all(),
            'estimate_minutes' => isset($data['estimate_hours']) ? (int) round($data['estimate_hours'] * 60) : null,
            'creator_id' => $request->user()->id,
            'position' => (int) Task::where('status', $data['status'])->max('position') + 1,
        ]);

        Activity::record('task.created', 'created task', $task);

        if ($task->assignee_id && $task->assignee_id !== $request->user()->id) {
            TaskAssigned::dispatch($task, $task->assignee, $request->user());
        }

        return (new TaskResource($task->load(['project:id,name,code', 'assignee'])))->response()->setStatusCode(201);
    }

    public function updateTask(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->requireWrite($request);

        $data = $request->validated();
        if (array_key_exists('estimate_hours', $data)) {
            $data['estimate_minutes'] = $data['estimate_hours'] !== null ? (int) round($data['estimate_hours'] * 60) : null;
            unset($data['estimate_hours']);
        }

        $task->update($data);

        return new TaskResource($task->load(['project:id,name,code', 'assignee']));
    }

    public function timeEntries(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->can('time.track') || $request->user()->can('time.view_all'), 403);

        return TimeEntryResource::collection(
            TimeEntry::completed()->with(['project:id,name,color', 'task:id,title', 'user'])
                ->unless($request->user()->can('time.view_all'), fn ($query) => $query->where('user_id', $request->user()->id))
                ->when($request->query('from'), fn ($query, $from) => $query->where('started_at', '>=', $from))
                ->latest('started_at')->paginate($this->perPage($request))
        );
    }

    public function invoices(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invoice::class);

        return InvoiceResource::collection(
            Invoice::with('client:id,name')
                ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
                ->latest('issue_date')->paginate($this->perPage($request))
        );
    }

    protected function requireWrite(Request $request): void
    {
        abort_unless($request->user()->tokenCan('write'), 403, 'This token is read-only.');
    }

    protected function perPage(Request $request): int
    {
        return max(1, min(100, $request->integer('per_page', 25)));
    }
}
