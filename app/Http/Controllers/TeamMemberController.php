<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Enums\WorkspaceRole;
use App\Models\Membership;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeamMemberController extends Controller
{
    public function update(Request $request, User $user, CurrentWorkspace $current): RedirectResponse
    {
        $membership = $this->editableMembership($request, $user);

        $data = $request->validate([
            'role' => ['sometimes', Rule::in($membership->isClient() ? [WorkspaceRole::Client->value] : [WorkspaceRole::Admin->value, WorkspaceRole::Manager->value, WorkspaceRole::Member->value])],
            'title' => ['sometimes', 'nullable', 'string', 'max:80'],
            'weekly_capacity' => ['sometimes', 'integer', 'min:0', 'max:80'],
        ]);

        if (isset($data['role']) && $user->id === $request->user()->id) {
            $this->toast("You can't change your own role", 'error', 'Ask another admin to do it.');

            return back();
        }

        $membership->update(collect($data)->only(['title', 'weekly_capacity'])->all());

        if (isset($data['role'])) {
            $current->runAs($this->workspace(), function () use ($user, $data) {
                $user->unsetRelation('roles');
                $user->syncRoles([$data['role']]);
            });

            $this->toast("{$user->name} is now ".WorkspaceRole::from($data['role'])->label());
        } else {
            $this->toast('Member updated', description: $user->name);
        }

        return back();
    }

    public function destroy(Request $request, User $user, CurrentWorkspace $current): RedirectResponse
    {
        $membership = $this->editableMembership($request, $user);

        if ($user->id === $request->user()->id) {
            $this->toast('Use “Leave workspace” in settings to remove yourself', 'error');

            return back();
        }

        DB::transaction(function () use ($membership, $user, $current) {
            $workspace = $this->workspace();

            // Hand their open work back to the team and stop any running timer.
            Task::where('assignee_id', $user->id)->where('status', '!=', TaskStatus::Done)->update(['assignee_id' => null]);
            TimeEntry::running()->where('user_id', $user->id)->get()->each->stop();

            $current->runAs($workspace, function () use ($user) {
                $user->unsetRelation('roles');
                $user->syncRoles([]);
            });

            $membership->delete();
            $user->projects()->detach($workspace->projects()->pluck('id'));

            if ($user->current_workspace_id === $workspace->id) {
                $user->forceFill(['current_workspace_id' => $user->memberships()->value('workspace_id')])->save();
            }
        });

        $this->toast("{$user->name} was removed", 'info', $membership->isClient() ? 'Their portal access has ended.' : 'Their open tasks are now unassigned.');

        return back();
    }

    /**
     * The member's membership in this workspace, if the acting user may manage it.
     */
    protected function editableMembership(Request $request, User $user): Membership
    {
        $workspace = $this->workspace();
        $this->authorize('manageMembers', $workspace);

        $membership = $user->membershipFor($workspace->id);
        abort_if($membership === null, 404);
        abort_if($workspace->owner_id === $user->id, 403, 'The workspace owner can only be changed by transferring ownership.');

        return $membership;
    }
}
