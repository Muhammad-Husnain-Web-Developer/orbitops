<?php

namespace App\Listeners;

use App\Events\InvoiceBecameOverdue;
use App\Notifications\InvoiceOverdueNotification;
use Illuminate\Support\Facades\Notification;

class SendInvoiceOverdueNotification
{
    public function handle(InvoiceBecameOverdue $event): void
    {
        $recipients = $event->invoice->workspace->membersWithPermission('invoices.manage');

        Notification::send($recipients, new InvoiceOverdueNotification($event->invoice));
    }
}
