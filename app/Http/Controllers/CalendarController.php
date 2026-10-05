<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Response;

class CalendarController extends Controller
{
    /**
     * Month view of everything with a date: tasks, milestones, project deadlines and invoices.
     */
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewAny', Task::class);

        $month = rescue(fn () => CarbonImmutable::createFromFormat('Y-m', (string) $request->query('month'))->startOfMonth(), now()->toImmutable()->startOfMonth(), false);
        $from = $month->startOfWeek();
        $to = $month->endOfMonth()->endOfWeek();
        $mine = $request->query('scope') === 'mine';
        $user = $request->user();

        $events = collect();

        Task::query()->with('project:id,name,color,code')
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->when($mine, fn ($query) => $query->where('assignee_id', $user->id))
            ->get()
            ->each(fn (Task $task) => $events->push([
                'id' => "task-{$task->id}",
                'type' => 'task',
                'title' => $task->title,
                'date' => $task->due_date->toDateString(),
                'color' => $task->project->color,
                'meta' => $task->key().' · '.$task->project->name,
                'done' => $task->status->value === 'done',
                'url' => route('tasks.index', ['task' => $task->id]),
            ]));

        Milestone::query()->with('project:id,name,color')
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->each(fn (Milestone $milestone) => $events->push([
                'id' => "milestone-{$milestone->id}",
                'type' => 'milestone',
                'title' => $milestone->name,
                'date' => $milestone->due_date->toDateString(),
                'color' => $milestone->project->color,
                'meta' => 'Milestone · '.$milestone->project->name,
                'done' => $milestone->status === 'completed',
                'url' => route('projects.show', ['project' => $milestone->project_id, 'tab' => 'milestones']),
            ]));

        if (! $mine) {
            Project::query()->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])->get()
                ->each(fn (Project $project) => $events->push([
                    'id' => "project-{$project->id}",
                    'type' => 'project',
                    'title' => "{$project->name} due",
                    'date' => $project->due_date->toDateString(),
                    'color' => $project->color,
                    'meta' => 'Project deadline',
                    'done' => $project->status->value === 'completed',
                    'url' => route('projects.show', $project),
                ]));
        }

        if ($user->can('invoices.view') && ! $mine) {
            Invoice::query()->with('client:id,name')
                ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])
                ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
                ->get()
                ->each(fn (Invoice $invoice) => $events->push([
                    'id' => "invoice-{$invoice->id}",
                    'type' => 'invoice',
                    'title' => "{$invoice->number} due",
                    'date' => $invoice->due_date->toDateString(),
                    'color' => $invoice->status === InvoiceStatus::Overdue ? 'rose' : 'amber',
                    'meta' => $invoice->client->name,
                    'done' => false,
                    'url' => route('invoices.show', $invoice),
                ]));
        }

        return inertia('Calendar/Index', [
            'month' => $month->format('Y-m'),
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'scope' => $mine ? 'mine' : 'all',
            'events' => $events->sortBy(fn ($event) => [$event['date'], $event['type'] === 'task' ? 1 : 0])->values(),
        ]);
    }
}
