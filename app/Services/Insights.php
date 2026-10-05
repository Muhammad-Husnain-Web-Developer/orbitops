<?php

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Business metrics shared by the dashboard and reports. All queries run through
 * tenant-scoped models, so results only ever cover the active workspace.
 */
class Insights
{
    /**
     * Driver-aware "YYYY-MM" bucket for a date column (MySQL, SQLite, PostgreSQL).
     */
    public function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    /**
     * @return list<string> Month keys (YYYY-MM) covering the range.
     */
    public function months(CarbonInterface $from, CarbonInterface $to): array
    {
        return collect(CarbonPeriod::create($from->copy()->startOfMonth(), '1 month', $to->copy()->startOfMonth()))
            ->map(fn ($month) => $month->format('Y-m'))
            ->all();
    }

    /**
     * Cash revenue: invoices paid in each month.
     *
     * @return array<string, float>
     */
    public function revenueByMonth(CarbonInterface $from, CarbonInterface $to): array
    {
        return $this->fillMonths($from, $to, Invoice::query()
            ->where('status', InvoiceStatus::Paid)
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw($this->monthExpression('paid_at').' as bucket, SUM(total) as value')
            ->groupBy('bucket')
            ->pluck('value', 'bucket'));
    }

    /**
     * Approved and reimbursed spend in each month.
     *
     * @return array<string, float>
     */
    public function expensesByMonth(CarbonInterface $from, CarbonInterface $to, ?bool $projectOnly = null): array
    {
        return $this->fillMonths($from, $to, $this->countedExpenses($from, $to)
            ->when($projectOnly === true, fn ($query) => $query->whereNotNull('project_id'))
            ->when($projectOnly === false, fn ($query) => $query->whereNull('project_id'))
            ->selectRaw($this->monthExpression('spent_on').' as bucket, SUM(amount) as value')
            ->groupBy('bucket')
            ->pluck('value', 'bucket'));
    }

    /**
     * @return Collection<int, array{key: string, label: string, value: float}>
     */
    public function expensesByCategory(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $this->countedExpenses($from, $to)
            ->selectRaw('category, SUM(amount) as value')
            ->groupBy('category')
            ->get()
            ->map(fn ($row) => ['key' => $row->category->value, 'label' => $row->category->label(), 'value' => round((float) $row->value, 2)])
            ->sortByDesc('value')
            ->values();
    }

    /**
     * Tracked hours per ISO week, split into billable and non-billable.
     *
     * @return array{labels: list<string>, billable: list<float>, other: list<float>}
     */
    public function hoursByWeek(CarbonInterface $from, CarbonInterface $to, ?int $userId = null): array
    {
        $rows = TimeEntry::query()
            ->completed()
            ->whereBetween('started_at', [$from, $to])
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->selectRaw('DATE(started_at) as day, billable, SUM(duration_seconds) as seconds')
            ->groupBy('day', 'billable')
            ->get();

        $weeks = collect(CarbonPeriod::create(CarbonImmutable::parse($from)->startOfWeek(), '1 week', CarbonImmutable::parse($to)->startOfWeek()))
            ->mapWithKeys(fn ($week) => [$week->format('o-W') => ['label' => $week->format('M j'), 'billable' => 0.0, 'other' => 0.0]]);

        foreach ($rows as $row) {
            $key = CarbonImmutable::parse($row->day)->format('o-W');

            if ($weeks->has($key)) {
                $week = $weeks->get($key);
                $week[$row->billable ? 'billable' : 'other'] += $row->seconds / 3600;
                $weeks->put($key, $week);
            }
        }

        return [
            'labels' => $weeks->pluck('label')->all(),
            'billable' => $weeks->pluck('billable')->map(fn ($hours) => round($hours, 1))->all(),
            'other' => $weeks->pluck('other')->map(fn ($hours) => round($hours, 1))->all(),
        ];
    }

    public function trackedSeconds(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) TimeEntry::query()->completed()->whereBetween('started_at', [$from, $to])->sum('duration_seconds');
    }

    /**
     * Tracked hours as a share of the team's weekly capacity over the period.
     *
     * @return array{percent: float, tracked: float, capacity: float}
     */
    public function utilization(Workspace $workspace, CarbonInterface $from, CarbonInterface $to): array
    {
        $weeks = max(1, $from->diffInDays($to) / 7);
        $capacity = (float) $workspace->memberships()->whereNull('client_id')->where('status', 'active')->sum('weekly_capacity') * $weeks;
        $tracked = $this->trackedSeconds($from, $to) / 3600;

        return [
            'percent' => $capacity > 0 ? round($tracked / $capacity * 100, 1) : 0.0,
            'tracked' => round($tracked, 1),
            'capacity' => round($capacity, 1),
        ];
    }

    /**
     * Per-person utilization for the period, highest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function utilizationByMember(Workspace $workspace, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $weeks = max(1, $from->diffInDays($to) / 7);

        $seconds = TimeEntry::query()->completed()->whereBetween('started_at', [$from, $to])
            ->selectRaw('user_id, SUM(duration_seconds) as total, SUM(CASE WHEN billable THEN duration_seconds ELSE 0 END) as billable')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        return $workspace->teamMembers()->get()->map(function ($member) use ($seconds, $weeks) {
            $row = $seconds->get($member->id);
            $hours = ($row->total ?? 0) / 3600;
            $capacity = $member->pivot->weekly_capacity * $weeks;

            return [
                'id' => $member->id,
                'name' => $member->name,
                'initials' => $member->initials,
                'avatar_url' => $member->avatar_url,
                'title' => $member->pivot->title,
                'hours' => round($hours, 1),
                'billable_hours' => round(($row->billable ?? 0) / 3600, 1),
                'utilization' => $capacity > 0 ? round($hours / $capacity * 100) : 0,
            ];
        })->sortByDesc('utilization')->values();
    }

    /**
     * Budget burn versus delivery for projects: tracked time valued at the project rate
     * (or the blended rate) plus project expenses, compared to the budget and progress.
     *
     * @param  Collection<int, Project>  $projects
     * @return Collection<int, array<string, mixed>>
     */
    public function projectPerformance(Collection $projects): Collection
    {
        $ids = $projects->pluck('id');

        $seconds = TimeEntry::query()->completed()->whereIn('project_id', $ids)
            ->selectRaw('project_id, SUM(duration_seconds) as seconds')->groupBy('project_id')->pluck('seconds', 'project_id');

        $spend = Expense::query()->whereIn('project_id', $ids)->whereIn('status', [ExpenseStatus::Approved, ExpenseStatus::Reimbursed])
            ->selectRaw('project_id, SUM(amount) as total')->groupBy('project_id')->pluck('total', 'project_id');

        $invoiced = Invoice::query()->whereIn('project_id', $ids)->where('status', '!=', InvoiceStatus::Cancelled)
            ->selectRaw('project_id, SUM(total) as total')->groupBy('project_id')->pluck('total', 'project_id');

        return $projects->map(function (Project $project) use ($seconds, $spend, $invoiced) {
            $hours = ($seconds[$project->id] ?? 0) / 3600;
            $rate = (float) ($project->hourly_rate ?: config('orbitops.blended_rate'));
            $cost = $hours * $rate + (float) ($spend[$project->id] ?? 0);
            $budget = (float) $project->budget;
            $burn = $budget > 0 ? round($cost / $budget * 100) : 0;
            $progress = $project->progress();

            return [
                'id' => $project->id,
                'name' => $project->name,
                'color' => $project->color,
                'client' => $project->client?->name,
                'status' => $project->status->value,
                'progress' => $progress,
                'hours' => round($hours, 1),
                'budget' => $budget,
                'cost' => round($cost, 2),
                'invoiced' => round((float) ($invoiced[$project->id] ?? 0), 2),
                'burn' => $burn,
                'health' => $budget <= 0 ? 'neutral' : ($burn > $progress + 15 ? 'danger' : ($burn > $progress + 5 ? 'warning' : 'success')),
            ];
        });
    }

    /**
     * How quickly invoices get paid and how much is still owed.
     *
     * @return array<string, mixed>
     */
    public function collection(CarbonInterface $from, CarbonInterface $to): array
    {
        $issued = Invoice::query()->whereBetween('issue_date', [$from, $to])->where('status', '!=', InvoiceStatus::Cancelled)->where('status', '!=', InvoiceStatus::Draft);
        $paid = (clone $issued)->where('status', InvoiceStatus::Paid)->get(['issue_date', 'paid_at', 'total']);

        $outstanding = Invoice::query()->outstanding();

        return [
            'issued' => round((float) (clone $issued)->sum('total'), 2),
            'collected' => round((float) $paid->sum('total'), 2),
            'rate' => (clone $issued)->count() ? round($paid->count() / (clone $issued)->count() * 100) : 0,
            'average_days' => $paid->count() ? round($paid->avg(fn ($invoice) => $invoice->issue_date->diffInDays($invoice->paid_at))) : null,
            'outstanding' => round((float) (clone $outstanding)->selectRaw('COALESCE(SUM(total - amount_paid), 0) as balance')->value('balance'), 2),
            'overdue' => round((float) Invoice::query()->where('status', InvoiceStatus::Overdue)->selectRaw('COALESCE(SUM(total - amount_paid), 0) as balance')->value('balance'), 2),
            'overdue_count' => Invoice::query()->where('status', InvoiceStatus::Overdue)->count(),
        ];
    }

    public function percentChange(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /**
     * @return Builder<Expense>
     */
    protected function countedExpenses(CarbonInterface $from, CarbonInterface $to): Builder
    {
        return Expense::query()
            ->whereIn('status', [ExpenseStatus::Approved, ExpenseStatus::Reimbursed, ExpenseStatus::Pending])
            ->whereBetween('spent_on', [$from->toDateString(), $to->toDateString()]);
    }

    /**
     * @param  Collection<string, mixed>  $values
     * @return array<string, float>
     */
    protected function fillMonths(CarbonInterface $from, CarbonInterface $to, Collection $values): array
    {
        return collect($this->months($from, $to))
            ->mapWithKeys(fn ($month) => [$month => round((float) ($values[$month] ?? 0), 2)])
            ->all();
    }
}
