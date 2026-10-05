<?php

namespace App\Http\Controllers;

use App\Actions\Invoices\SendInvoice;
use App\Enums\InvoiceStatus;
use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\RecordPaymentRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\InvoiceResource;
use App\Models\Activity;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

class InvoiceController extends Controller
{
    protected const SORTS = ['number', 'issue_date', 'due_date', 'total'];

    protected const TABS = ['draft', 'sent', 'overdue', 'paid', 'cancelled'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => in_array($request->query('status'), self::TABS, true) ? $request->query('status') : '',
            'client' => (string) $request->query('client', ''),
            'sort' => in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'issue_date',
            'direction' => $request->query('direction') === 'asc' ? 'asc' : 'desc',
        ];

        $invoices = Invoice::query()
            ->with(['client:id,name,email', 'project:id,name'])
            ->when($filters['search'], fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->where('number', 'like', "%{$search}%")
                ->orWhereHas('client', fn ($client) => $client->where('name', 'like', "%{$search}%"))))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['client'], fn ($query, $client) => $query->where('client_id', $client))
            ->orderBy($filters['sort'], $filters['direction'])
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return inertia('Invoices/Index', [
            'invoices' => InvoiceResource::collection($invoices),
            'filters' => $filters,
            // Not "counts": that name is shared with the sidebar badges.
            'statusCounts' => fn () => (object) Invoice::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status')->all(),
            'stats' => fn () => $this->stats(),
            'clients' => fn () => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Invoice::class);

        $workspace = $this->workspace();
        $client = $request->filled('client') ? Client::find($request->integer('client')) : null;
        $project = $request->filled('project') ? Project::find($request->integer('project')) : null;

        return inertia('Invoices/Form', [
            'invoice' => null,
            'defaults' => [
                'number' => $workspace->nextInvoiceNumber(),
                'client_id' => $client?->id ?? $project?->client_id ?? '',
                'project_id' => $project?->id ?? '',
                'issue_date' => today()->toDateString(),
                'due_date' => today()->addDays($workspace->payment_terms)->toDateString(),
                'currency' => $client?->currency ?? $workspace->currency,
                'tax_rate' => (float) $workspace->default_tax_rate,
                'payment_terms' => $workspace->payment_terms,
                'terms' => "Payment due within {$workspace->payment_terms} days. Thank you for your business.",
            ],
            ...$this->formOptions(),
        ]);
    }

    public function store(InvoiceRequest $request, SendInvoice $sender): RedirectResponse
    {
        $invoice = DB::transaction(function () use ($request) {
            $invoice = Invoice::create([
                ...$this->attributes($request),
                'number' => $this->workspace()->nextInvoiceNumber(),
                'status' => InvoiceStatus::Draft,
                'created_by' => $request->user()->id,
            ]);

            $this->syncItems($invoice, $request->validated('items'));

            return $invoice;
        });

        Activity::record('invoice.created', 'created invoice', $invoice);

        if ($request->boolean('send')) {
            $sender->handle($invoice);
            $this->toast('Invoice sent', description: "{$invoice->number} was emailed to {$invoice->client->email}");
        } else {
            $this->toast('Draft saved', description: $invoice->number);
        }

        return to_route('invoices.show', $invoice);
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['client', 'project:id,name,code', 'items', 'creator']);
        $user = request()->user();

        $timeline = Activity::query()
            ->with('causer')
            ->where('subject_type', $invoice->getMorphClass())
            ->where('subject_id', $invoice->id)
            ->latest()
            ->get();

        return inertia('Invoices/Show', [
            'invoice' => new InvoiceResource($invoice),
            'from' => [
                'name' => $this->workspace()->name,
                'logo_url' => $this->workspace()->logo_url,
                'email' => $this->workspace()->owner?->email,
            ],
            'timeline' => ActivityResource::collection($timeline),
            'payments' => $timeline->where('event', 'invoice.payment')->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'amount' => (float) ($activity->properties['amount'] ?? 0),
                'method' => RecordPaymentRequest::METHODS[$activity->properties['method'] ?? 'other'] ?? 'Other',
                'reference' => $activity->properties['reference'] ?? null,
                'paid_on' => $activity->properties['paid_on'] ?? $activity->created_at->toDateString(),
            ])->values(),
            'paymentMethods' => collect(RecordPaymentRequest::METHODS)->map(fn ($label, $value) => compact('value', 'label'))->values(),
            'can' => [
                'update' => $user->can('update', $invoice) && $invoice->isEditable(),
                'manage' => $user->can('update', $invoice),
                'delete' => $user->can('delete', $invoice) && in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Cancelled], true),
            ],
        ]);
    }

    public function edit(Invoice $invoice): Response|RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (! $invoice->isEditable()) {
            $this->toast("{$invoice->status->label()} invoices can't be edited", 'info', 'Duplicate it to start a new draft.');

            return to_route('invoices.show', $invoice);
        }

        return inertia('Invoices/Form', [
            'invoice' => new InvoiceResource($invoice->load(['items', 'client:id,name,email,currency'])),
            'defaults' => ['payment_terms' => $this->workspace()->payment_terms],
            ...$this->formOptions(),
        ]);
    }

    public function update(InvoiceRequest $request, Invoice $invoice, SendInvoice $sender): RedirectResponse
    {
        if (! $invoice->isEditable()) {
            $this->toast("{$invoice->status->label()} invoices can't be edited", 'error');

            return to_route('invoices.show', $invoice);
        }

        DB::transaction(function () use ($request, $invoice) {
            $invoice->update($this->attributes($request));
            $this->syncItems($invoice, $request->validated('items'));

            // Keep an outstanding invoice's status in step with its due date.
            if ($invoice->status !== InvoiceStatus::Draft) {
                $invoice->update(['status' => $invoice->due_date->lt(today()) ? InvoiceStatus::Overdue : InvoiceStatus::Sent]);
            }
        });

        if ($request->boolean('send')) {
            $sender->handle($invoice);
            $this->toast('Invoice saved and sent', description: "Emailed to {$invoice->client->email}");
        } else {
            $this->toast('Invoice updated', description: $invoice->number);
        }

        return to_route('invoices.show', $invoice);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);

        if (! in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Cancelled], true)) {
            $this->toast('Cancel the invoice before deleting it', 'error', 'Sent and paid invoices are kept for your records.');

            return back();
        }

        $invoice->delete();
        $this->toast('Invoice deleted', description: $invoice->number);

        return to_route('invoices.index');
    }

    /**
     * Billable time on a project since its last invoice, grouped into line items.
     */
    public function billableTime(Request $request): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $project = Project::findOrFail($request->integer('project'));
        $rate = (float) ($project->hourly_rate ?: config('orbitops.blended_rate'));

        $lastInvoiced = Invoice::where('project_id', $project->id)
            ->where('status', '!=', InvoiceStatus::Cancelled)
            ->when($request->filled('except'), fn ($query) => $query->whereKeyNot($request->integer('except')))
            ->max('issue_date');

        $from = $lastInvoiced ? now()->parse($lastInvoiced)->addDay()->startOfDay() : $project->start_date?->startOfDay() ?? now()->subYear();

        $groups = TimeEntry::query()
            ->completed()
            ->where('project_id', $project->id)
            ->where('billable', true)
            ->where('started_at', '>=', $from)
            ->with('task:id,title')
            ->get()
            ->groupBy(fn (TimeEntry $entry) => $entry->task?->title ?? 'General project work')
            ->map(fn ($entries, $title) => ['description' => $title, 'seconds' => $entries->sum('duration_seconds')])
            ->sortByDesc('seconds')
            ->values();

        // Keep the invoice readable: the biggest pieces of work, then the rest together.
        $lines = $groups->take(6);
        if ($groups->count() > 6) {
            $lines->push(['description' => 'Other project work', 'seconds' => $groups->slice(6)->sum('seconds')]);
        }

        $hours = fn (int $seconds) => round($seconds / 3600 * 4) / 4;

        return response()->json([
            'from' => $from->toDateString(),
            'hours' => $hours((int) $groups->sum('seconds')),
            'rate' => $rate,
            'lines' => $lines
                ->map(fn ($line) => ['description' => "{$project->name} — {$line['description']}", 'quantity' => $hours($line['seconds']), 'unit_price' => $rate])
                ->filter(fn ($line) => $line['quantity'] > 0)
                ->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(InvoiceRequest $request): array
    {
        return [
            ...$request->safe()->only(['client_id', 'project_id', 'issue_date', 'due_date', 'currency', 'discount_type', 'notes', 'terms']),
            'tax_rate' => (float) $request->validated('tax_rate', 0),
            'discount_value' => (float) $request->validated('discount_value', 0),
        ];
    }

    /**
     * @param  list<array{description: string, quantity: numeric, unit_price: numeric}>  $items
     */
    protected function syncItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();

        foreach (array_values($items) as $position => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'position' => $position,
            ]);
        }

        $invoice->recalculate();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'clients' => Client::orderBy('name')->get(['id', 'name', 'email', 'currency', 'contact_name']),
            'projects' => Project::orderBy('name')->get(['id', 'name', 'client_id', 'hourly_rate', 'color']),
            'currencies' => ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'PKR', 'AED', 'INR'],
        ];
    }

    /**
     * @return array<string, float|int>
     */
    protected function stats(): array
    {
        $balance = 'COALESCE(SUM(total - amount_paid), 0)';

        return [
            'outstanding' => round((float) Invoice::outstanding()->selectRaw("{$balance} as v")->value('v'), 2),
            'outstanding_count' => Invoice::outstanding()->count(),
            'overdue' => round((float) Invoice::where('status', InvoiceStatus::Overdue)->selectRaw("{$balance} as v")->value('v'), 2),
            'overdue_count' => Invoice::where('status', InvoiceStatus::Overdue)->count(),
            'paid_30' => round((float) Invoice::where('status', InvoiceStatus::Paid)->where('paid_at', '>=', now()->subDays(30))->sum('total'), 2),
            'paid_30_count' => Invoice::where('status', InvoiceStatus::Paid)->where('paid_at', '>=', now()->subDays(30))->count(),
            'draft' => round((float) Invoice::where('status', InvoiceStatus::Draft)->sum('total'), 2),
            'draft_count' => Invoice::where('status', InvoiceStatus::Draft)->count(),
        ];
    }
}
