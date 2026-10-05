<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\Money;

class InvoicePaidNotification extends WorkspaceNotification
{
    public function __construct(public Invoice $invoice) {}

    public function type(): string
    {
        return 'invoice_paid';
    }

    protected function content(object $notifiable): array
    {
        $this->invoice->loadMissing('client');

        return [
            'title' => "Invoice {$this->invoice->number} was paid",
            'body' => $this->invoice->client->name.' paid '.Money::format($this->invoice->total, $this->invoice->currency).'.',
            'url' => route('invoices.show', $this->invoice),
            'workspace_id' => $this->invoice->workspace_id,
        ];
    }
}
