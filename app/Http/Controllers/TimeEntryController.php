<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeEntryRequest;
use App\Http\Resources\TimeEntryResource;
use App\Models\Activity;
use App\Models\Project;
use App\Models\TimeEntry;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Response;

class TimeEntryController extends Controller
{
    /**
     * Weekly timesheet. People see their own time; managers can look at anyone's.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->can('time.track') || $user->can('time.view_all'), 403);

        $viewAll = $user->can('time.view_all');
        $week = rescue(fn () => CarbonImmutable::parse((string) $request->query('week'))->startOfWeek(), now()->toImmutable()->startOfWeek(), false);
        $end = $week->endOfWeek();

        $filters = [
            'week' => $week->toDateString(),
            'project' => $request->query('project', ''),
            // Everyone starts on their own timesheet; managers can widen it.
            'member' => $viewAll ? (string) $request->query('member', 'me') : 'me',
        ];

        $scope = function (Builder $query) use ($filters, $viewAll, $user) {
            $query->completed()
                ->when($filters['project'], fn ($query, $project) => $query->where('project_id', $project))
                ->when(! $viewAll, fn ($query) => $query->where('user_id', $user->id))
                ->when($filters['member'] === 'me', fn ($query) => $query->where('user_id', $user->id))
                ->when($viewAll && is_numeric($filters['member']), fn ($query) => $query->where('user_id', $filters['member']));
        };

        $entries = TimeEntry::query()
            ->tap($scope)
            ->with(['project:id,name,color,hourly_rate,client_id', 'project.client:id,name', 'task:id,title,number,project_id', 'user'])
            ->whereBetween('started_at', [$week, $end])
            ->orderByDesc('started_at')
            ->get();

        // For the current week compare like for like: last week up to this moment.
        $isCurrent = $week->isSameDay(now()->startOfWeek());
        $previousEnd = $isCurrent ? now()->subWeek() : $end->subWeek();
        $previous = (int) TimeEntry::query()->tap($scope)->whereBetween('started_at', [$week->subWeek(), $previousEnd])->sum('duration_seconds');

        return inertia('Time/Index', [
            'filters' => $filters,
            'week' => [
                'start' => $week->toDateString(),
                'end' => $end->toDateString(),
                'is_current' => $isCurrent,
                'previous' => $week->subWeek()->toDateString(),
                'next' => $week->addWeek()->toDateString(),
            ],
            'entries' => TimeEntryResource::collection($entries),
            'summary' => $this->summary($entries, $previous),
            'projects' => fn () => Project::orderBy('name')->get(['id', 'name', 'color']),
            'members' => fn () => $viewAll ? $this->workspace()->teamMembers()->orderBy('name')->get()->map->only(['id', 'name', 'initials', 'avatar_url']) : [],
            'recent' => fn () => $this->recent($user->id),
            'canViewAll' => $viewAll,
        ]);
    }

    public function store(TimeEntryRequest $request): RedirectResponse
    {
        $entry = TimeEntry::create([
            ...$request->safe()->only(['project_id', 'task_id', 'description']),
            'user_id' => $request->user()->id,
            'started_at' => $request->startedAt(),
            'ended_at' => $request->endedAt(),
            'billable' => $request->boolean('billable', true),
        ]);

        Activity::record('time.logged', 'logged '.$entry->durationLabel().' on', $entry->task ?? $entry->project);

        $this->toast('Time logged', description: $entry->durationLabel().' on '.$entry->project->name);

        return back();
    }

    public function update(TimeEntryRequest $request, TimeEntry $timeEntry): RedirectResponse
    {
        abort_if($timeEntry->isRunning(), 422, 'Stop the timer before editing this entry.');

        $timeEntry->update([
            ...$request->safe()->only(['project_id', 'task_id', 'description']),
            'started_at' => $request->startedAt(),
            'ended_at' => $request->endedAt(),
            'billable' => $request->boolean('billable', true),
        ]);

        $this->toast('Time entry updated');

        return back();
    }

    public function destroy(TimeEntry $timeEntry): RedirectResponse
    {
        $this->authorize('delete', $timeEntry);

        $timeEntry->delete();

        $this->toast('Time entry deleted');

        return back();
    }

    /**
     * @param  Collection<int, TimeEntry>  $entries
     * @return array<string, int|float|null>
     */
    protected function summary($entries, int $previous): array
    {
        $total = (int) $entries->sum('duration_seconds');
        $billable = (int) $entries->where('billable', true)->sum('duration_seconds');
        $rate = (float) config('orbitops.blended_rate');

        return [
            'total' => $total,
            'billable' => $billable,
            'billable_amount' => round($entries->where('billable', true)->sum(fn (TimeEntry $entry) => $entry->duration_seconds / 3600 * (float) ($entry->project->hourly_rate ?: $rate)), 2),
            'previous' => $previous,
            'delta' => $previous > 0 ? round(($total - $previous) / $previous * 100, 1) : null,
            'days_logged' => $entries->groupBy(fn (TimeEntry $entry) => $entry->started_at->toDateString())->count(),
        ];
    }

    /**
     * The user's most recent distinct pieces of work, to restart a timer in one click.
     *
     * @return list<array<string, mixed>>
     */
    protected function recent(int $userId): array
    {
        return TimeEntry::query()
            ->completed()
            ->where('user_id', $userId)
            ->with(['project:id,name,color', 'task:id,title'])
            ->latest('started_at')
            ->limit(40)
            ->get()
            ->unique(fn (TimeEntry $entry) => $entry->project_id.'|'.$entry->task_id.'|'.$entry->description)
            ->take(4)
            ->map(fn (TimeEntry $entry) => [
                'project_id' => $entry->project_id,
                'task_id' => $entry->task_id,
                'description' => $entry->description,
                'project' => ['name' => $entry->project->name, 'color' => $entry->project->color],
                'task' => $entry->task?->title,
            ])
            ->values()
            ->all();
    }
}
