<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Notifications\InvoicePaidNotification;
use Illuminate\Support\Facades\Notification;

class SendInvoicePaidNotification
{
    public function handle(InvoicePaid $event): void
    {
        $recipients = $event->invoice->workspace->membersWithPermission('invoices.manage');

        Notification::send($recipients, new InvoicePaidNotification($event->invoice));
    }
}
