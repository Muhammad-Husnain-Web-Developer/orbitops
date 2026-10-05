<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\WorkspaceRole;
use App\Events\MilestoneReviewed;
use App\Models\Attachment;
use App\Models\Invoice;
use App\Models\Milestone;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use BuildsWorkspaces, RefreshDatabase;

    public function test_contacts_only_see_their_own_clients_work(): void
    {
        $workspace = $this->createWorkspace();
        [$northstar, $ourProject] = $this->clientWithProject($workspace, ['name' => 'Northstar Website']);
        [$vertex, $theirProject] = $this->clientWithProject($workspace, ['name' => 'Vertex Dashboard']);
        $contact = $this->addMember($workspace, WorkspaceRole::Client, $northstar);

        $theirInvoice = $this->within($workspace, fn () => Invoice::factory()->create(['client_id' => $vertex->id, 'status' => InvoiceStatus::Sent]));

        $this->as($contact)->get(route('portal.projects.index'))
            ->assertInertia(fn (Assert $page) => $page->has('projects', 1)->where('projects.0.name', 'Northstar Website'));

        $this->as($contact)->get(route('portal.projects.show', $ourProject))->assertOk();
        $this->as($contact)->get(route('portal.projects.show', $theirProject))->assertNotFound();
        $this->as($contact)->get(route('portal.invoices.show', $theirInvoice))->assertNotFound();
        $this->as($contact)->post(route('portal.messages.store', $theirProject), ['body' => 'Hello'])->assertNotFound();
    }

    public function test_portal_never_exposes_budgets_or_hidden_tasks(): void
    {
        $workspace = $this->createWorkspace();
        [$client, $project] = $this->clientWithProject($workspace, ['budget' => 84000, 'hourly_rate' => 140]);
        $contact = $this->addMember($workspace, WorkspaceRole::Client, $client);

        $this->within($workspace, function () use ($project) {
            Task::factory()->create(['project_id' => $project->id, 'title' => 'Shared task', 'visible_to_client' => true]);
            Task::factory()->create(['project_id' => $project->id, 'title' => 'Internal refactor', 'visible_to_client' => false]);
        });

        $response = $this->as($contact)->get(route('portal.projects.show', $project));

        $response->assertInertia(fn (Assert $page) => $page
            ->missing('project.budget')
            ->missing('project.hourly_rate')
            ->has('tasks', 1)
            ->where('tasks.0.title', 'Shared task'));
        $this->assertStringNotContainsString('84000', $response->getContent());
        $this->assertStringNotContainsString('Internal refactor', $response->getContent());
    }

    public function test_drafts_and_unshared_files_stay_private(): void
    {
        Storage::fake('local');

        $workspace = $this->createWorkspace();
        [$client, $project] = $this->clientWithProject($workspace);
        $contact = $this->addMember($workspace, WorkspaceRole::Client, $client);

        [$draft, $internal, $shared] = $this->within($workspace, function () use ($client, $project, $workspace) {
            Storage::disk('local')->put('workspaces/x/a.pdf', 'pdf');

            return [
                Invoice::factory()->create(['client_id' => $client->id, 'status' => InvoiceStatus::Draft]),
                Attachment::create(['project_id' => $project->id, 'client_id' => $client->id, 'name' => 'notes.pdf', 'disk' => 'local', 'path' => 'workspaces/x/a.pdf', 'size' => 3, 'visible_to_client' => false, 'uploaded_by' => $workspace->owner_id]),
                Attachment::create(['project_id' => $project->id, 'client_id' => $client->id, 'name' => 'final.pdf', 'disk' => 'local', 'path' => 'workspaces/x/a.pdf', 'size' => 3, 'visible_to_client' => true, 'uploaded_by' => $workspace->owner_id]),
            ];
        });

        $this->as($contact)->get(route('portal.invoices.show', $draft))->assertNotFound();
        $this->as($contact)->get(route('portal.files.download', $internal))->assertNotFound();
        $this->as($contact)->get(route('portal.files.download', $shared))->assertOk();
    }

    public function test_contacts_can_approve_or_request_changes_on_delivered_milestones(): void
    {
        Event::fake([MilestoneReviewed::class]);

        $workspace = $this->createWorkspace();
        [$client, $project] = $this->clientWithProject($workspace);
        $contact = $this->addMember($workspace, WorkspaceRole::Client, $client);

        $milestone = $this->within($workspace, fn () => Milestone::create(['project_id' => $project->id, 'name' => 'Visual design', 'status' => 'completed', 'completed_at' => now(), 'requires_approval' => true, 'approval_status' => 'pending']));

        $this->as($contact)->post(route('portal.approvals.review', $milestone), ['decision' => 'changes_requested'])->assertSessionHasErrors('note');

        $this->as($contact)->post(route('portal.approvals.review', $milestone), ['decision' => 'changes_requested', 'note' => 'Darker blue please'])->assertRedirect();

        $milestone->refresh();
        $this->assertSame('changes_requested', $milestone->approval_status);
        $this->assertSame('in_progress', $milestone->status);
        $this->assertDatabaseHas('comments', ['commentable_id' => $project->id, 'user_id' => $contact->id]);
        Event::assertDispatched(MilestoneReviewed::class);

        // Not pending any more: a second review is ignored.
        $this->as($contact)->post(route('portal.approvals.review', $milestone), ['decision' => 'approved'])->assertRedirect();
        $this->assertSame('changes_requested', $milestone->fresh()->approval_status);
    }
}
