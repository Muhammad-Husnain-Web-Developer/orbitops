<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Events\InvoiceBecameOverdue;
use App\Events\InvoicePaid;
use App\Models\Invoice;
use App\Notifications\InvoiceSentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use BuildsWorkspaces, RefreshDatabase;

    protected function payload(int $clientId, array $overrides = []): array
    {
        return [
            'client_id' => $clientId,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'currency' => 'USD',
            'tax_rate' => 8,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'items' => [
                ['description' => 'Design sprint', 'quantity' => 2, 'unit_price' => 1500],
                ['description' => 'Development', 'quantity' => 10.5, 'unit_price' => 120],
            ],
            ...$overrides,
        ];
    }

    public function test_totals_are_calculated_on_the_server_and_numbers_increment(): void
    {
        $workspace = $this->createWorkspace(name: 'Acme Studio');
        [$client] = $this->clientWithProject($workspace);

        $this->as($workspace->owner)->post(route('invoices.store'), $this->payload($client->id))->assertRedirect();
        $this->as($workspace->owner)->post(route('invoices.store'), $this->payload($client->id))->assertRedirect();

        $invoices = Invoice::withoutGlobalScopes()->orderBy('id')->get();

        // 3000 + 1260 = 4260; 10% off = 3834; 8% tax = 306.72; total 4140.72
        $this->assertEquals(4260, $invoices[0]->subtotal);
        $this->assertEquals(426, $invoices[0]->discount_amount);
        $this->assertEquals(306.72, $invoices[0]->tax_amount);
        $this->assertEquals(4140.72, $invoices[0]->total);
        $this->assertSame(['ACM-1001', 'ACM-1002'], $invoices->pluck('number')->all());
    }

    public function test_line_items_are_required_and_validated(): void
    {
        $workspace = $this->createWorkspace();
        [$client] = $this->clientWithProject($workspace);

        $this->as($workspace->owner)->post(route('invoices.store'), $this->payload($client->id, ['items' => []]))->assertSessionHasErrors('items');
        $this->as($workspace->owner)->post(route('invoices.store'), $this->payload($client->id, ['items' => [['description' => 'X', 'quantity' => 0, 'unit_price' => 10]]]))->assertSessionHasErrors('items.0.quantity');
        $this->as($workspace->owner)->post(route('invoices.store'), $this->payload($client->id, ['due_date' => now()->subDay()->toDateString()]))->assertSessionHasErrors('due_date');
    }

    public function test_sending_emails_the_client_and_payments_settle_the_invoice(): void
    {
        Notification::fake();
        Event::fake([InvoicePaid::class]);

        $workspace = $this->createWorkspace();
        [$client] = $this->clientWithProject($workspace);
        $this->as($workspace->owner)->post(route('invoices.store'), $this->payload($client->id, ['send' => true]));

        $invoice = Invoice::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(InvoiceStatus::Sent, $invoice->status);
        Notification::assertSentOnDemand(InvoiceSentNotification::class, fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === [$client->email => $client->contact_name]);

        $this->as($workspace->owner)->post(route('invoices.payments.store', $invoice), ['amount' => $invoice->total + 1, 'paid_on' => now()->toDateString(), 'method' => 'card'])->assertSessionHasErrors('amount');

        $this->as($workspace->owner)->post(route('invoices.payments.store', $invoice), ['amount' => 1000, 'paid_on' => now()->toDateString(), 'method' => 'bank_transfer']);
        $this->assertSame(InvoiceStatus::Sent, $invoice->fresh()->status);
        Event::assertNotDispatched(InvoicePaid::class);

        $this->as($workspace->owner)->post(route('invoices.payments.store', $invoice), ['amount' => $invoice->fresh()->balance(), 'paid_on' => now()->toDateString(), 'method' => 'bank_transfer']);
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertEquals(0, $invoice->fresh()->balance());
        Event::assertDispatched(InvoicePaid::class);

        // Paid invoices are locked.
        $this->as($workspace->owner)->put(route('invoices.update', $invoice), $this->payload($client->id))->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->as($workspace->owner)->delete(route('invoices.destroy', $invoice));
        $this->assertNotNull(Invoice::withoutGlobalScopes()->find($invoice->id));
    }

    public function test_invoices_cannot_be_sent_without_a_billing_email(): void
    {
        $workspace = $this->createWorkspace();
        [$client] = $this->clientWithProject($workspace);
        $client->update(['email' => null]);

        $this->as($workspace->owner)->post(route('invoices.store'), $this->payload($client->id, ['send' => true]))->assertSessionHasErrors('invoice');
    }

    public function test_the_scheduler_flags_overdue_invoices_once(): void
    {
        Event::fake([InvoiceBecameOverdue::class]);

        $workspace = $this->createWorkspace();
        [$client] = $this->clientWithProject($workspace);
        [$late, $onTime] = $this->within($workspace, fn () => [
            Invoice::factory()->create(['client_id' => $client->id, 'status' => InvoiceStatus::Sent, 'issue_date' => now()->subDays(30), 'due_date' => now()->subDays(2)]),
            Invoice::factory()->create(['client_id' => $client->id, 'status' => InvoiceStatus::Sent, 'due_date' => now()->addDays(3)]),
        ]);

        $this->artisan('invoices:flag-overdue')->assertSuccessful();
        $this->artisan('invoices:flag-overdue')->expectsOutputToContain('No invoices became overdue')->assertSuccessful();

        $this->assertSame(InvoiceStatus::Overdue, $late->fresh()->status);
        $this->assertSame(InvoiceStatus::Sent, $onTime->fresh()->status);
        Event::assertDispatchedTimes(InvoiceBecameOverdue::class, 1);
    }
}
