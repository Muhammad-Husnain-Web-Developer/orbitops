<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\ClientResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\ProjectResource;
use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class ClientController extends Controller
{
    private const SORTS = ['name', 'created_at', 'outstanding', 'projects_count', 'lifetime_value'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => in_array($request->query('status'), ClientStatus::values(), true) ? $request->query('status') : '',
            'sort' => in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'name',
            'direction' => $request->query('direction') === 'desc' ? 'desc' : 'asc',
        ];

        $clients = $this->withFinancials(Client::query())
            ->withCount(['projects', 'projects as active_projects_count' => fn ($query) => $query->whereIn('status', ProjectStatus::open())])
            ->when($filters['search'], fn ($query, $search) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('contact_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('industry', 'like', "%{$search}%")))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->orderBy($filters['sort'], $filters['direction'])
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return inertia('Clients/Index', [
            'clients' => ClientResource::collection($clients),
            'filters' => $filters,
            'stats' => fn () => [
                'total' => Client::count(),
                'active' => Client::where('status', ClientStatus::Active)->count(),
                'leads' => Client::where('status', ClientStatus::Lead)->count(),
                'outstanding' => round((float) Invoice::outstanding()->selectRaw('COALESCE(SUM(total - amount_paid), 0) as balance')->value('balance'), 2),
            ],
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::create([...$request->validated(), 'created_by' => $request->user()->id]);

        Activity::record('client.created', 'added client', $client);
        $this->toast('Client created', description: "{$client->name} has been added to your workspace.");

        return redirect()->route('clients.show', $client);
    }

    public function show(Request $request, Client $client): Response
    {
        $this->authorize('view', $client);

        $client = $this->withFinancials(Client::query())->withCount('projects')->findOrFail($client->id);

        return inertia('Clients/Show', [
            'client' => fn () => new ClientResource($client),
            'tab' => in_array($request->query('tab'), ['overview', 'projects', 'invoices', 'files', 'activity', 'notes'], true) ? $request->query('tab') : 'overview',
            'stats' => fn () => [
                'active_projects' => $client->projects()->whereIn('status', ProjectStatus::open())->count(),
                'tracked_seconds' => (int) TimeEntry::whereIn('project_id', $client->projects()->select('id'))->sum('duration_seconds'),
                'invoiced' => round((float) $client->invoices()->whereNotIn('status', [InvoiceStatus::Draft, InvoiceStatus::Cancelled])->sum('total'), 2),
            ],
            'projects' => fn () => ProjectResource::collection($client->projects()->withProgress()->with('members')->latest()->get()),
            'invoices' => fn () => $request->user()->can('invoices.view')
                ? InvoiceResource::collection($client->invoices()->latest('issue_date')->limit(20)->get())
                : [],
            'files' => fn () => $request->user()->can('files.view')
                ? AttachmentResource::collection(Attachment::where('client_id', $client->id)->with(['uploader', 'project:id,name,color'])->latest()->get())
                : [],
            'activity' => fn () => ActivityResource::collection(Activity::where('client_id', $client->id)->with('causer')->latest()->limit(30)->get()),
            'notes' => fn () => CommentResource::collection($client->notes()->with('author')->get()),
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        $this->toast('Client updated');

        return back();
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete();
        Activity::record('client.deleted', 'archived client', $client);

        $this->toast('Client archived', description: "{$client->name} and its history are kept for your records.");

        return redirect()->route('clients.index');
    }

    /**
     * Outstanding balance and lifetime paid value as SQL subqueries (no N+1).
     *
     * @param  Builder<Client>  $query
     * @return Builder<Client>
     */
    protected function withFinancials(Builder $query): Builder
    {
        return $query->addSelect([
            'clients.*',
            'outstanding' => Invoice::query()->withoutGlobalScopes()
                ->selectRaw('COALESCE(SUM(total - amount_paid), 0)')
                ->whereColumn('invoices.client_id', 'clients.id')
                ->whereNull('invoices.deleted_at')
                ->whereIn('status', InvoiceStatus::outstanding()),
            'lifetime_value' => Invoice::query()->withoutGlobalScopes()
                ->selectRaw('COALESCE(SUM(total), 0)')
                ->whereColumn('invoices.client_id', 'clients.id')
                ->whereNull('invoices.deleted_at')
                ->where('status', InvoiceStatus::Paid),
        ]);
    }
}
