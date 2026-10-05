<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskMoved;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\TaskDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Task::class);

        $user = $request->user();

        $filters = [
            'view' => in_array($request->query('view'), ['board', 'list'], true) ? $request->query('view') : 'board',
            'search' => (string) $request->query('search', ''),
            'project' => (string) $request->query('project', ''),
            'assignee' => (string) $request->query('assignee', ''),
            'priority' => in_array($request->query('priority'), TaskPriority::values(), true) ? $request->query('priority') : '',
            'status' => in_array($request->query('status'), TaskStatus::values(), true) ? $request->query('status') : '',
            'sort' => in_array($request->query('sort'), ['due_date', 'priority', 'created_at', 'title'], true) ? $request->query('sort') : 'due_date',
            'direction' => $request->query('direction') === 'desc' ? 'desc' : 'asc',
        ];

        $query = Task::query()
            ->with(['project:id,name,code,color', 'assignee'])
            ->withCount(['comments', 'attachments'])
            ->when($filters['search'], fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->when($filters['project'], fn ($q, $project) => $q->where('project_id', $project))
            ->when($filters['assignee'] === 'me', fn ($q) => $q->where('assignee_id', $user->id))
            ->when($filters['assignee'] === 'unassigned', fn ($q) => $q->whereNull('assignee_id'))
            ->when(ctype_digit($filters['assignee']), fn ($q) => $q->where('assignee_id', (int) $filters['assignee']))
            ->when($filters['priority'], fn ($q, $priority) => $q->where('priority', $priority))
            ->whereHas('project', fn ($q) => $q->whereNotIn('status', ['cancelled']));

        if ($filters['view'] === 'board') {
            // Keep the Done column focused on recent wins.
            $tasks = $query->where(fn ($q) => $q->where('status', '!=', TaskStatus::Done)->orWhere('completed_at', '>=', now()->subDays(21)))
                ->orderBy('position')->orderBy('id')->get();
            $tasks = TaskResource::collection($tasks);
        } else {
            $tasks = $query
                ->when($filters['status'], fn ($q, $status) => $q->where('status', $status))
                ->when($filters['sort'] === 'priority', fn ($q) => $q->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END ".$filters['direction']), fn ($q) => $q->orderByRaw("{$filters['sort']} is null")->orderBy($filters['sort'], $filters['direction']))
                ->orderBy('id')
                ->paginate(25)
                ->withQueryString();
            $tasks = TaskResource::collection($tasks);
        }

        return inertia('Tasks/Index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'projects' => fn () => Project::open()->orderBy('name')->get(['id', 'name', 'color']),
            'members' => fn () => $this->workspace()->teamMembers()->orderBy('name')->get()->map(fn (User $member) => $member->only(['id', 'name', 'initials', 'avatar_url'])),
            'activeTask' => fn () => TaskDetail::for($request->integer('task')),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $status = TaskStatus::from($data['status']);

        $task = Task::create([
            ...Arr::except($data, ['estimate_hours']),
            'estimate_minutes' => isset($data['estimate_hours']) ? (int) round($data['estimate_hours'] * 60) : null,
            'creator_id' => $request->user()->id,
            'position' => (int) Task::where('status', $status)->max('position') + 1,
        ]);

        Activity::record('task.created', 'created task', $task);

        if ($task->assignee_id && $task->assignee_id !== $request->user()->id) {
            TaskAssigned::dispatch($task, $task->assignee, $request->user());
        }

        $this->toast('Task created', description: $task->title);

        return back();
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $previousStatus = $task->status;
        $previousAssignee = $task->assignee_id;

        if (array_key_exists('estimate_hours', $data)) {
            $data['estimate_minutes'] = $data['estimate_hours'] !== null ? (int) round($data['estimate_hours'] * 60) : null;
            unset($data['estimate_hours']);
        }

        $task->update($data);

        if ($task->assignee_id && $task->assignee_id !== $previousAssignee) {
            TaskAssigned::dispatch($task, $task->assignee, $user);
            Activity::record('task.assigned', "assigned {$task->assignee->firstName()} to", $task);
        }

        if ($task->status !== $previousStatus) {
            $this->recordStatusChange($task, $user);
        }

        return back();
    }

    /**
     * Drag-and-drop: move a card into a column after another card (or to the top).
     */
    public function move(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'after_id' => ['nullable', 'integer'],
        ]);

        $status = TaskStatus::from($data['status']);
        $previousStatus = $task->status;

        DB::transaction(function () use ($task, $status, $data) {
            $after = isset($data['after_id']) ? Task::where('status', $status)->find($data['after_id']) : null;
            $position = $after ? $after->position + 1 : 1;

            // Open a gap at the target position; positions are workspace-wide per column.
            Task::where('status', $status)->where('id', '!=', $task->id)->where('position', '>=', $position)->increment('position');

            $task->forceFill(['status' => $status, 'position' => $position])->save();
        });

        if ($status !== $previousStatus) {
            $this->recordStatusChange($task, $request->user());
        }

        broadcast(new TaskMoved($task, $request->user()->id));

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();
        Activity::record('task.deleted', 'deleted task', $task);

        $this->toast('Task deleted');

        return back();
    }

    protected function recordStatusChange(Task $task, User $user): void
    {
        if ($task->status === TaskStatus::Done) {
            Activity::record('task.completed', 'completed task', $task);
            TaskCompleted::dispatch($task, $user);

            return;
        }

        Activity::record('task.moved', "moved to {$task->status->label()}", $task, ['to' => $task->status->label()]);
    }
}
