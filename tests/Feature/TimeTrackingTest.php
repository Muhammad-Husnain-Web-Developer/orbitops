<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsWorkspaces;
use Tests\TestCase;

class TimeTrackingTest extends TestCase
{
    use BuildsWorkspaces, RefreshDatabase;

    public function test_only_one_timer_runs_at_a_time(): void
    {
        $workspace = $this->createWorkspace();
        [, $project] = $this->clientWithProject($workspace);
        $member = $this->addMember($workspace, WorkspaceRole::Member);

        $this->as($member)->post(route('timer.start'), ['project_id' => $project->id, 'description' => 'Design'])->assertRedirect();
        $this->travel(25)->minutes();
        $this->as($member)->post(route('timer.start'), ['project_id' => $project->id, 'description' => 'Build'])->assertRedirect();

        $entries = TimeEntry::withoutGlobalScopes()->where('user_id', $member->id)->orderBy('id')->get();
        $this->assertCount(2, $entries);
        $this->assertNotNull($entries[0]->ended_at);
        $this->assertSame(25 * 60, $entries[0]->duration_seconds);
        $this->assertNull($entries[1]->ended_at);

        $this->travel(10)->minutes();
        $this->as($member)->post(route('timer.stop'));
        $this->assertSame(10 * 60, $entries[1]->fresh()->duration_seconds);
    }

    public function test_a_task_must_belong_to_the_chosen_project(): void
    {
        $workspace = $this->createWorkspace();
        [, $website] = $this->clientWithProject($workspace);
        [, $app] = $this->clientWithProject($workspace);
        $appTask = $this->within($workspace, fn () => Task::factory()->create(['project_id' => $app->id]));

        $this->as($workspace->owner)->post(route('timer.start'), ['project_id' => $website->id, 'task_id' => $appTask->id])->assertSessionHasErrors('task_id');
        $this->as($workspace->owner)->post(route('time.store'), ['project_id' => $website->id, 'task_id' => $appTask->id, 'date' => now()->toDateString(), 'start' => '09:00', 'end' => '10:00'])->assertSessionHasErrors('task_id');
    }

    public function test_manual_entries_validate_times_and_people_only_edit_their_own(): void
    {
        $workspace = $this->createWorkspace();
        [, $project] = $this->clientWithProject($workspace);
        $priya = $this->addMember($workspace, WorkspaceRole::Member);
        $lucas = $this->addMember($workspace, WorkspaceRole::Member);

        $this->as($priya)->post(route('time.store'), ['project_id' => $project->id, 'date' => now()->toDateString(), 'start' => '11:00', 'end' => '10:00'])->assertSessionHasErrors('end');
        $this->as($priya)->post(route('time.store'), ['project_id' => $project->id, 'date' => now()->addDay()->toDateString(), 'start' => '09:00', 'end' => '10:00'])->assertSessionHasErrors('date');
        $this->as($priya)->post(route('time.store'), ['project_id' => $project->id, 'date' => now()->toDateString(), 'start' => '09:00', 'end' => '10:30', 'billable' => true])->assertRedirect();

        $entry = TimeEntry::withoutGlobalScopes()->where('user_id', $priya->id)->firstOrFail();
        $this->assertSame(90 * 60, $entry->duration_seconds);

        $this->as($lucas)->delete(route('time.destroy', $entry))->assertForbidden();
        $this->as($workspace->owner)->delete(route('time.destroy', $entry))->assertRedirect();
    }
}
