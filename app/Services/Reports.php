<?php

namespace App\Services;

use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\Workspace;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Business reports for a period: profit and loss, cash collection, time and
 * utilization, client revenue and project profitability.
 */
class Reports
{
    public function __construct(protected Insights $insights) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(Workspace $workspace, ReportPeriod $period): array
    {
        $previous = $period->previous();

        $revenue = $this->revenue($period);
        $previousRevenue = $this->revenue($previous);
        $expenses = $this->expenses($period);
        $previousExpenses = $this->expenses($previous);
        $profit = $revenue - $expenses;
        $previousProfit = $previousRevenue - $previousExpenses;

        $seconds = $this->insights->trackedSeconds($period->from, $period->to);
        $billable = (int) TimeEntry::query()->completed()->where('billable', true)->whereBetween('started_at', [$period->from, $period->to])->sum('duration_seconds');
        $utilization = $this->insights->utilization($workspace, $period->from, $period->to);
        $previousUtilization = $this->insights->utilization($workspace, $previous->from, $previous->to);
        $collection = $this->insights->collection($period->from, $period->to);

        // Only compare with a previous period the workspace actually has records for.
        $comparable = ($since = $this->dataSince()) !== null && $previous->from->gte($since);

        return [
            'comparable' => $comparable,
            'revenue' => round($revenue, 2),
            'revenue_delta' => $comparable ? $this->insights->percentChange($revenue, $previousRevenue) : null,
            'expenses' => round($expenses, 2),
            'expenses_delta' => $comparable ? $this->insights->percentChange($expenses, $previousExpenses) : null,
            'profit' => round($profit, 2),
            'profit_delta' => $comparable && $previousProfit != 0.0 ? round(($profit - $previousProfit) / abs($previousProfit) * 100, 1) : null,
            'margin' => $revenue > 0 ? round($profit / $revenue * 100, 1) : null,
            'hours' => $seconds,
            'billable_share' => $seconds ? round($billable / $seconds * 100) : 0,
            'utilization' => $utilization['percent'],
            'utilization_delta' => $comparable ? $this->insights->percentChange($utilization['percent'], $previousUtilization['percent']) : null,
            'invoiced' => $collection['issued'],
            'collected_rate' => $collection['rate'],
            'average_days_to_pay' => $collection['average_days'],
            'outstanding' => $collection['outstanding'],
            'overdue' => $collection['overdue'],
        ];
    }

    /**
     * Revenue and expenses per month across the period.
     *
     * @return array{labels: list<string>, revenue: list<float>, expenses: list<float>, profit: list<float>}
     */
    public function cashflow(ReportPeriod $period): array
    {
        $months = $this->insights->months($period->from, $period->to);
        $revenue = array_values($this->insights->revenueByMonth($period->from, $period->to));
        $expenses = array_values($this->insights->expensesByMonth($period->from, $period->to));

        return [
            'labels' => array_map(fn ($month) => CarbonImmutable::createFromFormat('Y-m', $month)->format(count($months) > 12 ? 'M y' : 'M'), $months),
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => array_map(fn ($in, $out) => round($in - $out, 2), $revenue, $expenses),
        ];
    }

    /**
     * Paid revenue by client in the period, largest first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function revenueByClient(ReportPeriod $period): Collection
    {
        $rows = Invoice::query()
            ->with('client:id,name')
            ->where('status', InvoiceStatus::Paid)
            ->whereBetween('paid_at', [$period->from, $period->to])
            ->selectRaw('client_id, SUM(total) as revenue, COUNT(*) as invoices')
            ->groupBy('client_id')
            ->orderByDesc('revenue')
            ->get();

        $total = (float) $rows->sum('revenue');

        return $rows->map(fn ($row) => [
            'id' => $row->client_id,
            'name' => $row->client?->name ?? 'Deleted client',
            'revenue' => round((float) $row->revenue, 2),
            'invoices' => (int) $row->invoices,
            'share' => $total > 0 ? round((float) $row->revenue / $total * 100, 1) : 0,
        ])->values();
    }

    /**
     * Projects that were active at any point in the period.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function projects(ReportPeriod $period): Collection
    {
        $projects = Project::query()
            ->withProgress()
            ->with('client:id,name')
            ->where(fn ($query) => $query->whereNull('completed_at')->orWhere('completed_at', '>=', $period->from))
            ->where(fn ($query) => $query->whereNull('start_date')->orWhere('start_date', '<=', $period->to))
            ->get();

        return $this->insights->projectPerformance($projects)
            ->map(fn ($row) => [...$row, 'margin' => $row['invoiced'] > 0 ? round(($row['invoiced'] - $row['cost']) / $row['invoiced'] * 100) : null])
            ->sortByDesc('invoiced')
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function team(Workspace $workspace, ReportPeriod $period): Collection
    {
        return $this->insights->utilizationByMember($workspace, $period->from, $period->to);
    }

    /**
     * @return array{labels: list<string>, billable: list<float>, other: list<float>}
     */
    public function hours(ReportPeriod $period): array
    {
        return $this->insights->hoursByWeek($period->from, $period->to);
    }

    /**
     * @return Collection<int, array{key: string, label: string, value: float}>
     */
    public function categories(ReportPeriod $period): Collection
    {
        return $this->insights->expensesByCategory($period->from, $period->to);
    }

    /**
     * The earliest date the workspace has business records for.
     */
    protected function dataSince(): ?CarbonImmutable
    {
        $dates = array_filter([
            Invoice::query()->min('issue_date'),
            Expense::query()->min('spent_on'),
            TimeEntry::query()->min('started_at'),
        ]);

        return $dates ? CarbonImmutable::parse(min($dates))->startOfDay() : null;
    }

    protected function revenue(ReportPeriod $period): float
    {
        return (float) Invoice::query()->where('status', InvoiceStatus::Paid)->whereBetween('paid_at', [$period->from, $period->to])->sum('total');
    }

    protected function expenses(ReportPeriod $period): float
    {
        return (float) Expense::query()
            ->whereIn('status', [ExpenseStatus::Approved, ExpenseStatus::Reimbursed, ExpenseStatus::Pending])
            ->whereBetween('spent_on', [$period->from->toDateString(), $period->to->toDateString()])
            ->sum('amount');
    }
}
