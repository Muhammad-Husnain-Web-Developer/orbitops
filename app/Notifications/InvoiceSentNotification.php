<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emailed to the client's billing contact when an invoice is sent.
 */
class InvoiceSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->invoice->loadMissing('client', 'workspace');

        return (new MailMessage)
            ->subject("Invoice {$this->invoice->number} from {$this->invoice->workspace->name}")
            ->greeting('Hi '.($this->invoice->client->contact_name ?: $this->invoice->client->name).',')
            ->line("{$this->invoice->workspace->name} has sent you invoice {$this->invoice->number} for ".Money::format($this->invoice->total, $this->invoice->currency).'.')
            ->line('Payment is due '.$this->invoice->due_date->toFormattedDateString().'.')
            ->action('View invoice', route('portal.invoices.show', $this->invoice))
            ->line('Thank you for your business.');
    }
}
