<?php

namespace App\Policies;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspace;

class ExpensePolicy
{
    use ChecksWorkspace;

    public function viewAny(User $user): bool
    {
        return $user->can('expenses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('expenses.manage');
    }

    /**
     * Submitters can edit their own pending expenses; approvers can edit any.
     */
    public function update(User $user, Expense $expense): bool
    {
        if (! $this->inWorkspace($user, $expense)) {
            return false;
        }

        return $user->can('expenses.approve')
            || ($user->can('expenses.manage') && $expense->user_id === $user->id && $expense->status === ExpenseStatus::Pending);
    }

    public function approve(User $user, Expense $expense): bool
    {
        return $this->allowed($user, 'expenses.approve', $expense);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->update($user, $expense);
    }
}
