<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\MilestoneResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Membership;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Services\Insights;
use App\Support\TaskDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Inertia\Response;

class ProjectController extends Controller
{
    private const SORTS = ['name', 'due_date', 'created_at', 'budget'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => in_array($request->query('status'), [...ProjectStatus::values(), 'open'], true) ? $request->query('status') : 'open',
            'client' => (string) $request->query('client', ''),
            'sort' => in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'due_date',
            'direction' => $request->query('direction') === 'desc' ? 'desc' : 'asc',
            'view' => $request->query('view') === 'list' ? 'list' : 'grid',
        ];

        $projects = Project::query()
            ->withProgress()
            ->with(['client:id,name', 'members', 'owner'])
            ->when($filters['search'], fn ($query, $search) => $query->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")))
            ->when($filters['status'] === 'open', fn ($query) => $query->open(), fn ($query) => $query->when($filters['status'], fn ($inner, $status) => $inner->where('status', $status)))
            ->when($filters['client'], fn ($query, $client) => $query->where('client_id', $client))
            ->orderByRaw("{$filters['sort']} is null")
            ->orderBy($filters['sort'], $filters['direction'])
            ->paginate(12)
            ->withQueryString();

        return inertia('Projects/Index', [
            'projects' => ProjectResource::collection($projects),
            'filters' => $filters,
            'clients' => fn () => Client::orderBy('name')->get(['id', 'name']),
            // Not "counts": that name is shared with the sidebar badges.
            'statusCounts' => fn () => (object) Project::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $project = Project::create([
            ...Arr::except($data, ['member_ids']),
            'owner_id' => $data['owner_id'] ?? $request->user()->id,
        ]);

        $project->members()->sync(collect($data['member_ids'] ?? [])->push($project->owner_id)->unique());

        Activity::record('project.created', 'created project', $project);
        $this->toast('Project created', description: 'Add milestones and tasks to get the team moving.');

        return redirect()->route('projects.show', $project);
    }

    public function show(Request $request, Project $project, Insights $insights): Response
    {
        $this->authorize('view', $project);

        $user = $request->user();
        $project->load(['client', 'owner', 'members'])->loadCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')]);

        return inertia('Projects/Show', [
            'project' => fn () => new ProjectResource($project),
            'tab' => in_array($request->query('tab'), ['overview', 'board', 'milestones', 'files', 'messages', 'activity'], true) ? $request->query('tab') : 'overview',
            'stats' => fn () => [
                'tracked_seconds' => (int) TimeEntry::where('project_id', $project->id)->sum('duration_seconds'),
                'billable_seconds' => (int) TimeEntry::where('project_id', $project->id)->where('billable', true)->sum('duration_seconds'),
                'expenses' => round((float) $project->expenses()->whereIn('status', [ExpenseStatus::Approved, ExpenseStatus::Reimbursed])->sum('amount'), 2),
                'invoiced' => $user->can('invoices.view') ? round((float) $project->invoices()->whereNotIn('status', [InvoiceStatus::Draft, InvoiceStatus::Cancelled])->sum('total'), 2) : null,
                'performance' => $insights->projectPerformance(collect([$project]))->first(),
            ],
            'milestones' => fn () => MilestoneResource::collection(
                $project->milestones()->with('approver')->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')])->get()
            ),
            'tasks' => fn () => TaskResource::collection(
                $project->tasks()->with(['assignee', 'project:id,name,code,color'])->withCount(['comments', 'attachments'])->orderBy('position')->get()
            ),
            'files' => fn () => $user->can('files.view')
                ? AttachmentResource::collection(Attachment::where('project_id', $project->id)->with('uploader')->latest()->get())
                : [],
            'messages' => fn () => CommentResource::collection($this->messages($project)),
            'activity' => fn () => ActivityResource::collection(Activity::where('project_id', $project->id)->with('causer')->latest()->limit(40)->get()),
            'activeTask' => fn () => TaskDetail::for($request->integer('task'), $project->id),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $wasCompleted = $project->status === ProjectStatus::Completed;

        $project->fill(Arr::except($data, ['member_ids']));

        if ($project->status === ProjectStatus::Completed && ! $wasCompleted) {
            $project->completed_at = now();
        } elseif ($project->status !== ProjectStatus::Completed) {
            $project->completed_at = null;
        }

        $project->save();

        if (array_key_exists('member_ids', $data)) {
            $project->members()->sync(collect($data['member_ids'])->push($project->owner_id)->filter()->unique());
        }

        Activity::record('project.updated', $project->status === ProjectStatus::Completed && ! $wasCompleted ? 'completed project' : 'updated', $project);
        $this->toast('Project updated');

        return back();
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();
        Activity::record('project.deleted', 'deleted project', $project);

        $this->toast('Project deleted');

        return redirect()->route('projects.index');
    }

    /**
     * The client conversation, flagging which messages came from client users.
     *
     * @return Collection<int, Comment>
     */
    protected function messages(Project $project)
    {
        $comments = $project->messages()->with('author')->oldest()->get();
        $clientUserIds = Membership::where('workspace_id', $project->workspace_id)->whereNotNull('client_id')->pluck('user_id');

        return $comments->each(fn ($comment) => $comment->setAttribute('from_client', $clientUserIds->contains($comment->user_id)));
    }
}
