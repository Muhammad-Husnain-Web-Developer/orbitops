<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Activity;
use App\Models\Invoice;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\Insights;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Insights $insights): Response
    {
        $user = $request->user();
        $finance = $user->can('invoices.view');
        $reports = $user->can('reports.view');

        return inertia('Dashboard', [
            'kpis' => fn () => $finance ? $this->businessKpis($insights) : $this->personalKpis($user),
            'summary' => fn () => [
                'due_this_week' => Task::open()->where('assignee_id', $user->id)->whereBetween('due_date', [today(), today()->addDays(7)])->count(),
                'overdue_tasks' => Task::overdue()->where('assignee_id', $user->id)->count(),
                'overdue_invoices' => $finance ? Invoice::where('status', InvoiceStatus::Overdue)->count() : 0,
            ],
            'charts' => Inertia::defer(fn () => $this->charts($insights, $user, $reports), 'charts'),
            'performance' => Inertia::defer(fn () => $reports ? $insights->projectPerformance(
                Project::open()->withProgress()->with('client:id,name')->orderBy('due_date')->limit(6)->get()
            )->values() : [], 'charts'),
            'activity' => fn () => $user->can('activity.view')
                ? ActivityResource::collection(Activity::with('causer')->visibleTo($user)->latest()->limit(8)->get())
                : [],
            'deadlines' => fn () => $this->deadlines($user),
            'overdueInvoices' => fn () => $finance
                ? InvoiceResource::collection(Invoice::with('client:id,name')->whereIn('status', InvoiceStatus::outstanding())->where('due_date', '<', today())->orderBy('due_date')->limit(4)->get())
                : [],
            'activeProjects' => fn () => $user->can('projects.view')
                ? ProjectResource::collection(Project::open()->withProgress()->with(['client:id,name', 'members'])->orderByRaw('due_date is null')->orderBy('due_date')->limit(4)->get())
                : [],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function businessKpis(Insights $insights): array
    {
        $now = now();
        $revenue = $insights->revenueByMonth($now->copy()->subMonths(11)->startOfMonth(), $now);
        $last30 = (float) Invoice::where('status', InvoiceStatus::Paid)->whereBetween('paid_at', [$now->copy()->subDays(30), $now])->sum('total');
        $prior30 = (float) Invoice::where('status', InvoiceStatus::Paid)->whereBetween('paid_at', [$now->copy()->subDays(60), $now->copy()->subDays(30)])->sum('total');

        $collection = $insights->collection($now->copy()->subYear(), $now);
        $outstandingCount = Invoice::outstanding()->count();

        $utilization = $insights->utilization($this->workspace(), $now->copy()->subDays(28), $now);
        $previousUtilization = $insights->utilization($this->workspace(), $now->copy()->subDays(56), $now->copy()->subDays(28));

        $openProjects = Project::open()->count();
        $startedThisMonth = Project::where('created_at', '>=', $now->copy()->startOfMonth())->count();

        return [
            ['key' => 'revenue', 'label' => 'Revenue', 'value' => $last30, 'format' => 'money', 'delta' => $insights->percentChange($last30, $prior30), 'deltaLabel' => 'vs previous 30 days', 'trend' => array_values($revenue)],
            ['key' => 'outstanding', 'label' => 'Outstanding invoices', 'value' => $collection['outstanding'], 'format' => 'money', 'delta' => null, 'hint' => "{$outstandingCount} open · {$collection['overdue_count']} overdue", 'upIsGood' => false],
            ['key' => 'projects', 'label' => 'Active projects', 'value' => $openProjects, 'format' => 'number', 'delta' => null, 'hint' => $startedThisMonth ? "+{$startedThisMonth} started this month" : 'No new projects this month'],
            ['key' => 'utilization', 'label' => 'Team utilization', 'value' => $utilization['percent'], 'format' => 'percent', 'delta' => $insights->percentChange($utilization['percent'], $previousUtilization['percent']), 'deltaLabel' => 'vs previous 4 weeks'],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function personalKpis(User $user): array
    {
        $week = TimeEntry::completed()->where('user_id', $user->id)->where('started_at', '>=', now()->startOfWeek())->sum('duration_seconds');
        $lastWeek = TimeEntry::completed()->where('user_id', $user->id)->whereBetween('started_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->sum('duration_seconds');

        return [
            ['key' => 'tasks', 'label' => 'My open tasks', 'value' => Task::open()->where('assignee_id', $user->id)->count(), 'format' => 'number', 'delta' => null, 'hint' => 'Across all projects'],
            ['key' => 'hours', 'label' => 'Hours this week', 'value' => (int) $week, 'format' => 'duration', 'delta' => app(Insights::class)->percentChange((float) $week, (float) $lastWeek), 'deltaLabel' => 'vs last week'],
            ['key' => 'projects', 'label' => 'Active projects', 'value' => Project::open()->count(), 'format' => 'number', 'delta' => null, 'hint' => 'In this workspace'],
            ['key' => 'overdue', 'label' => 'Overdue tasks', 'value' => Task::overdue()->where('assignee_id', $user->id)->count(), 'format' => 'number', 'delta' => null, 'hint' => 'Assigned to you', 'upIsGood' => false],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function charts(Insights $insights, User $user, bool $reports): array
    {
        $now = now();
        $from = $now->copy()->subMonths(11)->startOfMonth();
        $months = collect($insights->months($from, $now));

        return [
            'months' => $months->map(fn ($month) => now()->createFromFormat('Y-m', $month)->format('M'))->all(),
            'revenue' => $reports ? array_values($insights->revenueByMonth($from, $now)) : [],
            'expenses' => $reports ? array_values($insights->expensesByMonth($from, $now)) : [],
            'hours' => $insights->hoursByWeek($now->copy()->subWeeks(11)->startOfWeek(), $now, $reports ? null : $user->id),
            'categories' => $reports ? $insights->expensesByCategory($now->copy()->subDays(90), $now) : [],
        ];
    }

    /**
     * Tasks and milestones due soon, overdue first.
     *
     * @return list<array<string, mixed>>
     */
    protected function deadlines(User $user): array
    {
        $everyone = $user->can('projects.manage');

        $tasks = Task::open()
            ->with('project:id,name,code,color')
            ->whereNotNull('due_date')
            ->where('due_date', '<=', today()->addDays(14))
            ->unless($everyone, fn ($query) => $query->where('assignee_id', $user->id))
            ->orderBy('due_date')
            ->limit(6)
            ->get()
            ->map(fn (Task $task) => [
                'id' => "task-{$task->id}",
                'type' => 'task',
                'title' => $task->title,
                'meta' => $task->project->name,
                'color' => $task->project->color,
                'due_date' => $task->due_date->toDateString(),
                'url' => route('tasks.index', ['task' => $task->id]),
                'task' => (new TaskResource($task))->resolve(),
            ]);

        $milestones = Milestone::query()
            ->with('project:id,name,color')
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<=', today()->addDays(14))
            ->orderBy('due_date')
            ->limit(3)
            ->get()
            ->map(fn (Milestone $milestone) => [
                'id' => "milestone-{$milestone->id}",
                'type' => 'milestone',
                'title' => $milestone->name,
                'meta' => $milestone->project->name.' · Milestone',
                'color' => $milestone->project->color,
                'due_date' => $milestone->due_date->toDateString(),
                'url' => route('projects.show', ['project' => $milestone->project_id, 'tab' => 'milestones']),
            ]);

        return $tasks->concat($milestones)->sortBy('due_date')->take(7)->values()->all();
    }
}
