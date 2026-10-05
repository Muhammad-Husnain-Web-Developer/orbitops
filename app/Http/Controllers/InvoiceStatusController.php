<?php

namespace App\Http\Controllers;

use App\Actions\Invoices\SendInvoice;
use App\Enums\InvoiceStatus;
use App\Events\InvoicePaid;
use App\Http\Requests\RecordPaymentRequest;
use App\Models\Activity;
use App\Models\Invoice;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceStatusController extends Controller
{
    public function send(Invoice $invoice, SendInvoice $sender): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $reminder = $invoice->status !== InvoiceStatus::Draft;
        $sender->handle($invoice);

        $this->toast($reminder ? 'Reminder sent' : 'Invoice sent', description: "{$invoice->number} was emailed to {$invoice->client->email}");

        return back();
    }

    public function recordPayment(RecordPaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        if (! in_array($invoice->status, [InvoiceStatus::Sent, InvoiceStatus::Overdue], true)) {
            $this->toast('Send the invoice before recording a payment', 'error');

            return back();
        }

        $paidOn = Carbon::parse($request->validated('paid_on'));
        $amount = round((float) $request->validated('amount'), 2);

        DB::transaction(function () use ($invoice, $amount, $paidOn, $request) {
            $invoice->amount_paid = round((float) $invoice->amount_paid + $amount, 2);

            if ($invoice->balance() <= 0) {
                $invoice->status = InvoiceStatus::Paid;
                $invoice->paid_at = $paidOn->setTimeFrom(now());
            }

            $invoice->save();

            Activity::record('invoice.payment', 'recorded a '.Money::format($amount, $invoice->currency).' payment on', $invoice, [
                'amount' => $amount,
                'method' => $request->validated('method'),
                'reference' => $request->validated('reference'),
                'paid_on' => $paidOn->toDateString(),
            ]);
        });

        if ($invoice->status === InvoiceStatus::Paid) {
            Activity::record('invoice.paid', 'marked as paid', $invoice);
            InvoicePaid::dispatch($invoice);
            $this->toast('Invoice paid in full', description: "{$invoice->number} · ".Money::format($invoice->total, $invoice->currency));
        } else {
            $this->toast('Payment recorded', description: Money::format($invoice->balance(), $invoice->currency).' still due');
        }

        return back();
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        if (in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::Cancelled], true)) {
            $this->toast("{$invoice->status->label()} invoices can't be cancelled", 'error');

            return back();
        }

        $invoice->update(['status' => InvoiceStatus::Cancelled]);
        Activity::record('invoice.cancelled', 'cancelled invoice', $invoice);
        $this->toast('Invoice cancelled', description: $invoice->number);

        return back();
    }

    public function duplicate(Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);
        $this->authorize('create', Invoice::class);

        $copy = DB::transaction(function () use ($invoice) {
            $copy = $invoice->replicate(['number', 'status', 'amount_paid', 'sent_at', 'paid_at', 'created_by']);
            $copy->forceFill([
                'number' => $this->workspace()->nextInvoiceNumber(),
                'status' => InvoiceStatus::Draft,
                'amount_paid' => 0,
                'issue_date' => today(),
                'due_date' => today()->addDays($this->workspace()->payment_terms),
                'created_by' => request()->user()->id,
            ])->save();

            $invoice->items->each(fn ($item) => $copy->items()->create($item->only(['description', 'quantity', 'unit_price', 'position'])));
            $copy->recalculate();

            return $copy;
        });

        Activity::record('invoice.created', 'created invoice', $copy, ['from' => $invoice->number]);
        $this->toast('Invoice duplicated', description: "{$copy->number} is a new draft based on {$invoice->number}");

        return to_route('invoices.edit', $copy);
    }
}
