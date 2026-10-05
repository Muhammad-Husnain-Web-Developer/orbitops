<?php

namespace Tests\Concerns;

use App\Actions\Workspaces\AddWorkspaceMember;
use App\Actions\Workspaces\CreateWorkspace;
use App\Enums\WorkspaceRole;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;

trait BuildsWorkspaces
{
    protected function createWorkspace(?User $owner = null, string $name = 'Acme Studio'): Workspace
    {
        $owner ??= User::factory()->create();

        return app(CreateWorkspace::class)->handle($owner, ['name' => $name, 'invoice_prefix' => strtoupper(substr($name, 0, 3))]);
    }

    protected function addMember(Workspace $workspace, WorkspaceRole $role, ?Client $client = null): User
    {
        $user = User::factory()->create();
        app(AddWorkspaceMember::class)->handle($workspace, $user, $role, $client?->id);

        return $user->fresh();
    }

    /**
     * Run code with a workspace as the tenant, e.g. to create fixtures inside it.
     */
    protected function within(Workspace $workspace, callable $callback): mixed
    {
        return app(CurrentWorkspace::class)->runAs($workspace, $callback);
    }

    /**
     * A client and an active project for it, inside the workspace.
     *
     * @return array{0: Client, 1: Project}
     */
    protected function clientWithProject(Workspace $workspace, array $project = []): array
    {
        return $this->within($workspace, function () use ($project) {
            $client = Client::factory()->create();

            return [$client, Project::factory()->create(['client_id' => $client->id, ...$project])];
        });
    }

    /**
     * Act as a freshly loaded user so cached role relations never leak between requests.
     */
    protected function as(User $user): static
    {
        return $this->actingAs($user->fresh());
    }
}
