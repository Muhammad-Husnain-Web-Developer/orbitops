<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Events\InvoiceBecameOverdue;
use App\Models\Activity;
use App\Models\Invoice;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('invoices:flag-overdue')]
#[Description('Mark sent invoices past their due date as overdue and notify the people who manage billing')]
class FlagOverdueInvoices extends Command
{
    public function handle(CurrentWorkspace $current): int
    {
        $flagged = 0;

        // Run per workspace so permission lookups and activity are scoped to that tenant.
        Workspace::query()->each(function (Workspace $workspace) use ($current, &$flagged) {
            $current->runAs($workspace, function () use (&$flagged) {
                Invoice::query()
                    ->where('status', InvoiceStatus::Sent)
                    ->whereDate('due_date', '<', today())
                    ->each(function (Invoice $invoice) use (&$flagged) {
                        $invoice->update(['status' => InvoiceStatus::Overdue]);
                        Activity::record('invoice.overdue', 'raised an overdue alert for', $invoice);
                        InvoiceBecameOverdue::dispatch($invoice);
                        $flagged++;
                    });
            });
        });

        $this->components->info($flagged ? "Flagged {$flagged} overdue ".str('invoice')->plural($flagged).'.' : 'No invoices became overdue.');

        return self::SUCCESS;
    }
}
