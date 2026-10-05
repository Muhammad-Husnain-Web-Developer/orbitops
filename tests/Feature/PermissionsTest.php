<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use BuildsWorkspaces, RefreshDatabase;

    public function test_members_cannot_reach_finance_or_admin_areas_even_by_url(): void
    {
        $workspace = $this->createWorkspace();
        $member = $this->addMember($workspace, WorkspaceRole::Member);
        [$client] = $this->clientWithProject($workspace);

        $this->as($member)->get(route('invoices.index'))->assertForbidden();
        $this->as($member)->post(route('invoices.store'), ['client_id' => $client->id])->assertForbidden();
        $this->as($member)->get(route('reports'))->assertForbidden();
        $this->as($member)->get(route('settings.roles'))->assertForbidden();
        $this->as($member)->post(route('team.invitations.store'), ['emails' => 'x@example.com', 'role' => 'admin'])->assertForbidden();

        $role = Role::where('workspace_id', $workspace->id)->where('name', 'member')->first();
        $this->as($member)->put(route('settings.roles.update', $role->id), ['permissions' => []])->assertForbidden();
    }

    public function test_managers_can_run_finance(): void
    {
        $workspace = $this->createWorkspace();
        $manager = $this->addMember($workspace, WorkspaceRole::Manager);

        $this->as($manager)->get(route('invoices.index'))->assertOk();
        $this->as($manager)->get(route('reports'))->assertOk();
        $this->as($manager)->get(route('settings.billing'))->assertForbidden();
    }

    public function test_permission_changes_apply_immediately(): void
    {
        $workspace = $this->createWorkspace();
        $member = $this->addMember($workspace, WorkspaceRole::Member);
        $role = Role::where('workspace_id', $workspace->id)->where('name', 'member')->first();

        $this->as($member)->get(route('expenses.index'))->assertOk();

        $permissions = collect(WorkspaceRole::Member->defaultPermissions())->reject(fn ($name) => $name === 'expenses.view')->values()->all();
        $this->as($workspace->owner)->put(route('settings.roles.update', $role->id), ['permissions' => $permissions])->assertRedirect();

        $this->as($member)->get(route('expenses.index'))->assertForbidden();
    }

    public function test_owner_and_client_roles_cannot_be_edited(): void
    {
        $workspace = $this->createWorkspace();
        $owner = Role::where('workspace_id', $workspace->id)->where('name', 'owner')->first();

        $this->as($workspace->owner)->put(route('settings.roles.update', $owner->id), ['permissions' => []])->assertForbidden();
        $this->assertTrue($owner->fresh()->permissions()->exists());
    }

    public function test_roles_of_another_workspace_cannot_be_edited(): void
    {
        $acme = $this->createWorkspace(name: 'Acme Studio');
        $nova = $this->createWorkspace(name: 'Nova Labs');
        $novaMember = Role::where('workspace_id', $nova->id)->where('name', 'member')->first();

        $this->as($acme->owner)->put(route('settings.roles.update', $novaMember->id), ['permissions' => []])->assertNotFound();
    }

    public function test_client_users_are_kept_in_the_portal_and_team_out_of_it(): void
    {
        $workspace = $this->createWorkspace();
        [$client] = $this->clientWithProject($workspace);
        $contact = $this->addMember($workspace, WorkspaceRole::Client, $client);

        $this->as($contact)->get(route('dashboard'))->assertRedirect(route('portal.dashboard'));
        $this->as($contact)->get(route('invoices.index'))->assertRedirect(route('portal.dashboard'));
        $this->as($workspace->owner)->get(route('portal.dashboard'))->assertRedirect(route('dashboard'));
    }

    public function test_team_members_cannot_change_the_owner_or_themselves(): void
    {
        $workspace = $this->createWorkspace();
        $admin = $this->addMember($workspace, WorkspaceRole::Admin);

        $this->as($admin)->patch(route('team.members.update', $workspace->owner_id), ['role' => 'member'])->assertForbidden();
        $this->as($admin)->delete(route('team.members.destroy', $workspace->owner_id))->assertForbidden();
        $this->as($admin)->patch(route('team.members.update', $admin->id), ['role' => 'admin'])->assertRedirect();
        $this->assertSame(WorkspaceRole::Admin, $admin->fresh()->roleIn($workspace));
    }
}
