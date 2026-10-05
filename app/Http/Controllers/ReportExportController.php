<?php

namespace App\Http\Controllers;

use App\Services\Reports;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    /**
     * The report for the selected period as a spreadsheet-friendly CSV.
     */
    public function __invoke(Request $request, Reports $reports): StreamedResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $period = ReportPeriod::fromRequest($request);
        $workspace = $this->workspace();
        $filename = Str::slug($workspace->name).'-report-'.$period->from->toDateString().'-to-'.$period->to->toDateString().'.csv';

        return response()->streamDownload(function () use ($reports, $workspace, $period) {
            $out = fopen('php://output', 'w');
            $row = fn (array $values) => fputcsv($out, array_map(fn ($value) => $this->safe($value), $values));

            $row(["{$workspace->name} report", $period->label(), $period->from->toDateString().' to '.$period->to->toDateString()]);
            $row([]);

            $summary = $reports->summary($workspace, $period);
            $row(['Summary']);
            foreach (['revenue' => 'Revenue', 'expenses' => 'Expenses', 'profit' => 'Profit', 'margin' => 'Margin %', 'utilization' => 'Utilization %', 'billable_share' => 'Billable share %', 'invoiced' => 'Invoiced', 'outstanding' => 'Outstanding', 'overdue' => 'Overdue', 'average_days_to_pay' => 'Average days to pay'] as $key => $label) {
                $row([$label, $summary[$key] ?? '']);
            }
            $row(['Hours tracked', round($summary['hours'] / 3600, 1)]);
            $row([]);

            $cashflow = $reports->cashflow($period);
            $row(['Month', 'Revenue', 'Expenses', 'Profit']);
            foreach ($cashflow['labels'] as $index => $label) {
                $row([$label, $cashflow['revenue'][$index], $cashflow['expenses'][$index], $cashflow['profit'][$index]]);
            }
            $row([]);

            $row(['Client', 'Revenue', 'Invoices', 'Share %']);
            foreach ($reports->revenueByClient($period) as $client) {
                $row([$client['name'], $client['revenue'], $client['invoices'], $client['share']]);
            }
            $row([]);

            $row(['Project', 'Client', 'Status', 'Progress %', 'Hours', 'Budget', 'Cost', 'Invoiced', 'Budget burn %']);
            foreach ($reports->projects($period) as $project) {
                $row([$project['name'], $project['client'], $project['status'], $project['progress'], $project['hours'], $project['budget'], $project['cost'], $project['invoiced'], $project['burn']]);
            }
            $row([]);

            $row(['Team member', 'Hours', 'Billable hours', 'Utilization %']);
            foreach ($reports->team($workspace, $period) as $member) {
                $row([$member['name'], $member['hours'], $member['billable_hours'], $member['utilization']]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Neutralise spreadsheet formulas in exported text (CSV injection).
     */
    protected function safe(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
