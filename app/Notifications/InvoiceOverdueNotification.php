<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\Money;

class InvoiceOverdueNotification extends WorkspaceNotification
{
    public function __construct(public Invoice $invoice) {}

    public function type(): string
    {
        return 'invoice_overdue';
    }

    protected function content(object $notifiable): array
    {
        $this->invoice->loadMissing('client');

        return [
            'title' => "Invoice {$this->invoice->number} is overdue",
            'body' => Money::format($this->invoice->balance(), $this->invoice->currency).' from '.$this->invoice->client->name.' was due '.$this->invoice->due_date->toFormattedDateString().'.',
            'url' => route('invoices.show', $this->invoice),
            'workspace_id' => $this->invoice->workspace_id,
        ];
    }
}
