<?php

namespace App\Actions\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Activity;
use App\Models\Invoice;
use App\Notifications\InvoiceSentNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class SendInvoice
{
    /**
     * Email the invoice to the client's billing contact and mark it sent.
     * Re-sending an outstanding invoice works as a reminder.
     */
    public function handle(Invoice $invoice): void
    {
        $invoice->loadMissing('client');

        if (! in_array($invoice->status, [InvoiceStatus::Draft, InvoiceStatus::Sent, InvoiceStatus::Overdue], true)) {
            throw ValidationException::withMessages(['invoice' => 'Only draft or outstanding invoices can be sent.']);
        }

        if (blank($invoice->client->email)) {
            throw ValidationException::withMessages(['invoice' => "Add a billing email to {$invoice->client->name} before sending."]);
        }

        if ((float) $invoice->total <= 0) {
            throw ValidationException::withMessages(['invoice' => 'An invoice needs a total above zero before it can be sent.']);
        }

        $reminder = $invoice->status !== InvoiceStatus::Draft;

        $invoice->forceFill([
            'status' => $invoice->due_date->isPast() && ! $invoice->due_date->isToday() ? InvoiceStatus::Overdue : InvoiceStatus::Sent,
            'sent_at' => $invoice->sent_at ?? now(),
        ])->save();

        Notification::route('mail', [$invoice->client->email => $invoice->client->contact_name ?: $invoice->client->name])
            ->notify(new InvoiceSentNotification($invoice));

        Activity::record('invoice.sent', $reminder ? 'sent a reminder for' : 'sent invoice', $invoice, ['to' => $invoice->client->email]);
    }
}
