<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Invoice;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use BuildsWorkspaces, RefreshDatabase;

    public function test_records_from_another_workspace_are_not_found(): void
    {
        $acme = $this->createWorkspace(name: 'Acme Studio');
        $nova = $this->createWorkspace(name: 'Nova Labs');

        [$novaClient, $novaProject] = $this->clientWithProject($nova);
        [$novaTask, $novaInvoice] = $this->within($nova, fn () => [
            Task::factory()->create(['project_id' => $novaProject->id]),
            Invoice::factory()->create(['client_id' => $novaClient->id]),
        ]);

        $this->as($acme->owner)->get(route('projects.show', $novaProject))->assertNotFound();
        $this->as($acme->owner)->get(route('clients.show', $novaClient))->assertNotFound();
        $this->as($acme->owner)->get(route('invoices.show', $novaInvoice))->assertNotFound();
        $this->as($acme->owner)->patch(route('tasks.update', $novaTask), ['title' => 'Hijacked'])->assertNotFound();
        $this->as($acme->owner)->delete(route('projects.destroy', $novaProject))->assertNotFound();

        $this->assertSame($novaTask->title, Task::withoutGlobalScopes()->find($novaTask->id)->title);
    }

    public function test_lists_only_include_the_current_workspace(): void
    {
        $acme = $this->createWorkspace(name: 'Acme Studio');
        $nova = $this->createWorkspace(name: 'Nova Labs');

        $this->clientWithProject($acme, ['name' => 'Acme Website']);
        $this->clientWithProject($nova, ['name' => 'Nova Secret Project']);

        $this->as($acme->owner)->get(route('projects.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Projects/Index')
                ->has('projects.data', 1)
                ->where('projects.data.0.name', 'Acme Website'));
    }

    public function test_forms_reject_ids_that_belong_to_another_workspace(): void
    {
        $acme = $this->createWorkspace(name: 'Acme Studio');
        $nova = $this->createWorkspace(name: 'Nova Labs');
        [$novaClient] = $this->clientWithProject($nova);

        $this->as($acme->owner)
            ->post(route('projects.store'), [
                'name' => 'Sneaky project',
                'client_id' => $novaClient->id,
                'status' => 'active',
                'priority' => 'medium',
                'color' => 'violet',
                'billing_type' => 'fixed',
            ])
            ->assertSessionHasErrors('client_id');

        $this->assertDatabaseMissing('projects', ['name' => 'Sneaky project']);
    }

    public function test_cannot_switch_into_a_workspace_you_do_not_belong_to(): void
    {
        $acme = $this->createWorkspace(name: 'Acme Studio');
        $nova = $this->createWorkspace(name: 'Nova Labs');

        $this->as($acme->owner)->put(route('workspaces.switch', $nova))->assertForbidden();
        $this->assertSame($acme->id, $acme->owner->fresh()->current_workspace_id);
    }

    public function test_files_from_another_workspace_cannot_be_downloaded(): void
    {
        Storage::fake('local');

        $acme = $this->createWorkspace(name: 'Acme Studio');
        $nova = $this->createWorkspace(name: 'Nova Labs');
        [, $novaProject] = $this->clientWithProject($nova);

        $file = $this->within($nova, function () use ($novaProject, $nova) {
            $path = UploadedFile::fake()->create('contract.pdf', 10)->store("workspaces/{$nova->id}/files", 'local');

            return Attachment::create(['project_id' => $novaProject->id, 'name' => 'contract.pdf', 'disk' => 'local', 'path' => $path, 'mime_type' => 'application/pdf', 'size' => 10240, 'uploaded_by' => $nova->owner_id]);
        });

        $this->as($acme->owner)->get(route('files.download', $file))->assertNotFound();
        $this->as($nova->owner)->get(route('files.download', $file))->assertOk();
    }

    public function test_api_tokens_only_reach_their_own_workspace(): void
    {
        $acme = $this->createWorkspace(name: 'Acme Studio');
        $nova = $this->createWorkspace(owner: $acme->owner, name: 'Nova Labs');
        [, $novaProject] = $this->clientWithProject($nova);
        [, $acmeProject] = $this->clientWithProject($acme);

        $token = $acme->owner->createToken('script', ['read', "workspace:{$acme->id}"])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/projects')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $acmeProject->id);
        $this->withToken($token)->getJson("/api/v1/projects/{$novaProject->id}")->assertNotFound();
        $this->withToken($token)->postJson('/api/v1/tasks', ['title' => 'x', 'project_id' => $acmeProject->id, 'status' => 'todo', 'priority' => 'low'])->assertForbidden();
    }
}
