<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspace;

class InvoicePolicy
{
    use ChecksWorkspace;

    public function viewAny(User $user): bool
    {
        return $user->can('invoices.view');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->allowed($user, 'invoices.view', $invoice);
    }

    public function create(User $user): bool
    {
        return $user->can('invoices.manage');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->allowed($user, 'invoices.manage', $invoice);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $this->allowed($user, 'invoices.delete', $invoice);
    }
}
