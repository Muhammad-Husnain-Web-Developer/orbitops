<?php

namespace App\Http\Requests\Concerns;

use App\Support\CurrentWorkspace;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Foreign keys submitted by users must point at records in the active workspace;
 * a plain "exists" rule would accept IDs belonging to another tenant.
 */
trait ValidatesTenantReferences
{
    protected function inWorkspace(string $table): Exists
    {
        return Rule::exists($table, 'id')
            ->where('workspace_id', app(CurrentWorkspace::class)->id())
            ->when(in_array($table, ['clients', 'projects', 'tasks', 'invoices', 'expenses'], true), fn ($rule) => $rule->whereNull('deleted_at'));
    }

    protected function teamMember(): Exists
    {
        return Rule::exists('memberships', 'user_id')
            ->where('workspace_id', app(CurrentWorkspace::class)->id())
            ->whereNull('client_id')
            ->where('status', 'active');
    }
}
