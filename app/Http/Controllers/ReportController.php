<?php

namespace App\Http\Controllers;

use App\Services\Reports;
use App\Support\ReportPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(Request $request, Reports $reports): Response
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $period = ReportPeriod::fromRequest($request);
        $workspace = $this->workspace();

        return inertia('Reports/Index', [
            'period' => $period->toArray(),
            'presets' => collect(ReportPeriod::PRESETS)->map(fn ($label, $value) => compact('value', 'label'))->values(),
            'summary' => fn () => $reports->summary($workspace, $period),
            'cashflow' => Inertia::defer(fn () => $reports->cashflow($period), 'charts'),
            'hours' => Inertia::defer(fn () => $reports->hours($period), 'charts'),
            'categories' => Inertia::defer(fn () => $reports->categories($period), 'charts'),
            'clients' => Inertia::defer(fn () => $reports->revenueByClient($period), 'tables'),
            'team' => Inertia::defer(fn () => $reports->team($workspace, $period), 'tables'),
            'projects' => Inertia::defer(fn () => $reports->projects($period), 'tables'),
        ]);
    }
}
