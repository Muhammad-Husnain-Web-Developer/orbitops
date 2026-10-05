<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Activity;
use App\Models\Expense;
use App\Models\Project;
use App\Services\Insights;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    protected const PERIODS = ['month', 'last_month', 'quarter', 'year', 'all'];

    protected const SORTS = ['spent_on', 'amount', 'vendor'];

    public function index(Request $request, Insights $insights): Response
    {
        $this->authorize('viewAny', Expense::class);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => in_array($request->query('status'), ExpenseStatus::values(), true) ? $request->query('status') : '',
            'category' => in_array($request->query('category'), ExpenseCategory::values(), true) ? $request->query('category') : '',
            'project' => (string) $request->query('project', ''),
            'period' => in_array($request->query('period'), self::PERIODS, true) ? $request->query('period') : 'quarter',
            'mine' => $request->boolean('mine'),
            'sort' => in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'spent_on',
            'direction' => $request->query('direction') === 'asc' ? 'asc' : 'desc',
        ];

        [$from, $to] = $this->range($filters['period']);

        $expenses = Expense::query()
            ->with(['project:id,name,color', 'user'])
            ->when($from, fn ($query) => $query->whereBetween('spent_on', [$from->toDateString(), $to->toDateString()]))
            ->when($filters['search'], fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->where('vendor', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category'], fn ($query, $category) => $query->where('category', $category))
            ->when($filters['project'] === 'none', fn ($query) => $query->whereNull('project_id'))
            ->when(is_numeric($filters['project']), fn ($query) => $query->where('project_id', $filters['project']))
            ->when($filters['mine'], fn ($query) => $query->where('user_id', $request->user()->id))
            ->orderBy($filters['sort'], $filters['direction'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return inertia('Expenses/Index', [
            'expenses' => ExpenseResource::collection($expenses),
            'filters' => $filters,
            'stats' => fn () => $this->stats($insights),
            'charts' => fn () => $this->charts($insights, $from, $to),
            'projects' => fn () => Project::orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $expense = new Expense([
            ...$request->safe()->except(['receipt', 'remove_receipt']),
            'billable' => $request->boolean('billable'),
            'user_id' => $request->user()->id,
            'status' => ExpenseStatus::Pending,
        ]);

        if ($request->hasFile('receipt')) {
            $this->attachReceipt($expense, $request->file('receipt'));
        }

        $expense->save();

        Activity::record('expense.created', 'submitted expense', $expense);
        $this->toast('Expense submitted', description: Money::format($expense->amount, $expense->currency).' · '.($expense->vendor ?: $expense->description));

        return back();
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->fill([
            ...$request->safe()->except(['receipt', 'remove_receipt']),
            'billable' => $request->boolean('billable'),
        ]);

        if ($request->hasFile('receipt') || $request->boolean('remove_receipt')) {
            $this->removeReceipt($expense);
        }

        if ($request->hasFile('receipt')) {
            $this->attachReceipt($expense, $request->file('receipt'));
        }

        $expense->save();

        $this->toast('Expense updated');

        return back();
    }

    /**
     * Approve, reject or mark an expense as reimbursed.
     */
    public function status(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('approve', $expense);

        $status = ExpenseStatus::from($request->validate([
            'status' => ['required', Rule::enum(ExpenseStatus::class)],
        ])['status']);

        $expense->update(['status' => $status]);

        if ($status !== ExpenseStatus::Pending) {
            Activity::record("expense.{$status->value}", "{$status->value} expense", $expense);
        }

        $this->toast(match ($status) {
            ExpenseStatus::Approved => 'Expense approved',
            ExpenseStatus::Rejected => 'Expense rejected',
            ExpenseStatus::Reimbursed => 'Marked as reimbursed',
            ExpenseStatus::Pending => 'Moved back to pending',
        }, description: $expense->vendor ?: $expense->description);

        return back();
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $expense->delete();
        $this->toast('Expense deleted');

        return back();
    }

    public function receipt(Expense $expense): StreamedResponse
    {
        $this->authorize('viewAny', Expense::class);
        abort_unless($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path), 404);

        $inline = in_array(Str::lower(pathinfo($expense->receipt_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true);

        return $inline
            ? Storage::disk('local')->response($expense->receipt_path, $expense->receipt_name, ['Content-Disposition' => 'inline; filename="'.addslashes($expense->receipt_name).'"'])
            : Storage::disk('local')->download($expense->receipt_path, $expense->receipt_name);
    }

    protected function attachReceipt(Expense $expense, UploadedFile $file): void
    {
        $expense->receipt_path = $file->storeAs(
            "workspaces/{$this->workspace()->id}/receipts",
            Str::uuid().'.'.Str::lower($file->getClientOriginalExtension()),
            'local',
        );
        $expense->receipt_name = Str::limit($file->getClientOriginalName(), 180, '');
    }

    protected function removeReceipt(Expense $expense): void
    {
        if ($expense->receipt_path) {
            Storage::disk('local')->delete($expense->receipt_path);
        }

        $expense->receipt_path = null;
        $expense->receipt_name = null;
    }

    /**
     * @return array{0: ?CarbonImmutable, 1: ?CarbonImmutable}
     */
    protected function range(string $period): array
    {
        $now = now()->toImmutable();

        return match ($period) {
            'month' => [$now->startOfMonth(), $now],
            'last_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'quarter' => [$now->subDays(89)->startOfDay(), $now],
            'year' => [$now->startOfYear(), $now],
            default => [null, null],
        };
    }

    /**
     * @return array<string, float|int>
     */
    protected function stats(Insights $insights): array
    {
        $now = now()->toImmutable();
        $counted = [ExpenseStatus::Pending, ExpenseStatus::Approved, ExpenseStatus::Reimbursed];

        $thisMonth = (float) Expense::whereIn('status', $counted)->whereBetween('spent_on', [$now->startOfMonth()->toDateString(), $now->toDateString()])->sum('amount');
        // Like-for-like: last month up to the same day.
        $lastMonth = (float) Expense::whereIn('status', $counted)->whereBetween('spent_on', [$now->subMonthNoOverflow()->startOfMonth()->toDateString(), $now->subMonthNoOverflow()->toDateString()])->sum('amount');

        return [
            'month' => round($thisMonth, 2),
            'month_delta' => $insights->percentChange($thisMonth, $lastMonth),
            'pending' => round((float) Expense::where('status', ExpenseStatus::Pending)->sum('amount'), 2),
            'pending_count' => Expense::where('status', ExpenseStatus::Pending)->count(),
            'billable' => round((float) Expense::whereIn('status', $counted)->where('billable', true)->where('spent_on', '>=', $now->subDays(89)->toDateString())->sum('amount'), 2),
            'top_category' => $insights->expensesByCategory($now->subDays(89), $now)->first(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function charts(Insights $insights, ?CarbonImmutable $from, ?CarbonImmutable $to): array
    {
        $now = now()->toImmutable();
        $start = $now->subMonths(5)->startOfMonth();

        return [
            'months' => collect($insights->months($start, $now))->map(fn ($month) => CarbonImmutable::createFromFormat('Y-m', $month)->format('M'))->all(),
            'project' => array_values($insights->expensesByMonth($start, $now, true)),
            'overhead' => array_values($insights->expensesByMonth($start, $now, false)),
            'categories' => $insights->expensesByCategory($from ?? $now->subYears(10), $to ?? $now),
        ];
    }
}
