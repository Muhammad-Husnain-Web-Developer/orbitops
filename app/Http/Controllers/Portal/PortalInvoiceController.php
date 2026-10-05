<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InvoiceStatus;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Inertia\Response;

class PortalInvoiceController extends PortalController
{
    public function index(): Response
    {
        $invoices = Invoice::with('project:id,name')
            ->where('client_id', $this->clientId())
            ->where('status', '!=', InvoiceStatus::Draft)
            ->orderByRaw("status = 'cancelled'")
            ->latest('issue_date')
            ->get();

        return inertia('Portal/Invoices/Index', [
            'invoices' => InvoiceResource::collection($invoices),
            'summary' => [
                'outstanding' => round($invoices->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::Overdue])->sum(fn ($invoice) => $invoice->balance()), 2),
                'overdue' => round($invoices->where('status', InvoiceStatus::Overdue)->sum(fn ($invoice) => $invoice->balance()), 2),
                'paid' => round((float) $invoices->where('status', InvoiceStatus::Paid)->sum('total'), 2),
            ],
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        $this->ensureOwn($invoice->client_id);
        abort_if($invoice->status === InvoiceStatus::Draft, 404);

        $workspace = $this->workspace();

        return inertia('Portal/Invoices/Show', [
            'invoice' => new InvoiceResource($invoice->load(['client', 'project:id,name', 'items'])),
            'from' => ['name' => $workspace->name, 'logo_url' => $workspace->logo_url, 'email' => $workspace->owner?->email],
        ]);
    }
}
