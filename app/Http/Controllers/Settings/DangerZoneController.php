<?php

namespace App\Http\Controllers\Settings;

use App\Enums\TaskStatus;
use App\Enums\WorkspaceRole;
use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Task;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CurrentWorkspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class DangerZoneController extends Controller
{
    public function index(Request $request): Response
    {
        $workspace = $this->workspace();
        $isOwner = $workspace->owner_id === $request->user()->id;

        return inertia('Settings/Danger', [
            'workspace' => ['name' => $workspace->name],
            'isOwner' => $isOwner,
            'canDelete' => $request->user()->can('delete', $workspace),
            'otherWorkspaces' => $request->user()->memberships()->where('workspace_id', '!=', $workspace->id)->count(),
            'candidates' => $isOwner
                ? $workspace->teamMembers()->where('users.id', '!=', $request->user()->id)->orderBy('name')->get()->map->only(['id', 'name', 'email'])
                : [],
        ]);
    }

    public function leave(Request $request, CurrentWorkspace $current): RedirectResponse
    {
        $workspace = $this->workspace();
        $user = $request->user();

        if ($workspace->owner_id === $user->id) {
            $this->toast('Owners can’t leave their workspace', 'error', 'Transfer ownership first, or delete the workspace.');

            return back();
        }

        $request->validate(['password' => ['required', 'current_password']]);

        DB::transaction(function () use ($workspace, $user, $current) {
            Task::where('assignee_id', $user->id)->where('status', '!=', TaskStatus::Done)->update(['assignee_id' => null]);
            TimeEntry::running()->where('user_id', $user->id)->get()->each->stop();
            $current->runAs($workspace, fn () => $user->unsetRelation('roles')->syncRoles([]));
            $user->projects()->detach($workspace->projects()->pluck('id'));
            Membership::where('workspace_id', $workspace->id)->where('user_id', $user->id)->delete();
        });

        return $this->moveOn($user, "You left {$workspace->name}");
    }

    public function transfer(Request $request, CurrentWorkspace $current): RedirectResponse
    {
        $workspace = $this->workspace();
        abort_unless($workspace->owner_id === $request->user()->id, 403);

        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'password' => ['required', 'current_password'],
        ]);

        $newOwner = $workspace->teamMembers()->where('users.id', $data['user_id'])->first();
        abort_if($newOwner === null || $newOwner->id === $request->user()->id, 422, 'Choose another active team member.');

        DB::transaction(function () use ($workspace, $newOwner, $request, $current) {
            $workspace->update(['owner_id' => $newOwner->id]);

            $current->runAs($workspace, function () use ($newOwner, $request) {
                $newOwner->unsetRelation('roles')->syncRoles([WorkspaceRole::Owner->value]);
                $request->user()->unsetRelation('roles')->syncRoles([WorkspaceRole::Admin->value]);
            });
        });

        $this->toast('Ownership transferred', description: "{$newOwner->name} now owns {$workspace->name}. You're an admin.");

        return to_route('settings.general');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $workspace = $this->workspace();
        $this->authorize('delete', $workspace);

        $request->validate([
            'name' => ['required', 'string', function ($attribute, $value, $fail) use ($workspace) {
                if (trim($value) !== $workspace->name) {
                    $fail('Type the workspace name exactly to confirm.');
                }
            }],
            'password' => ['required', 'current_password'],
        ]);

        $name = $workspace->name;
        $user = $request->user();

        DB::transaction(function () use ($workspace) {
            // Point anyone whose current workspace this was somewhere else (or nowhere).
            User::where('current_workspace_id', $workspace->id)->update(['current_workspace_id' => null]);
            DB::table('model_has_roles')->where('workspace_id', $workspace->id)->delete();
            Role::where('workspace_id', $workspace->id)->delete();
            $workspace->delete();
        });

        Storage::disk('local')->deleteDirectory("workspaces/{$workspace->id}");
        Storage::disk('public')->deleteDirectory("logos/{$workspace->id}");

        return $this->moveOn($user->fresh(), "{$name} was deleted");
    }

    /**
     * Send the user to another workspace they belong to, or to create a new one.
     */
    protected function moveOn(User $user, string $message): RedirectResponse
    {
        $next = $user->memberships()->with('workspace')->first()?->workspace;

        if ($next instanceof Workspace) {
            $user->switchWorkspace($next);
            $this->toast($message, 'info', "You're now in {$next->name}.");

            return redirect()->route($user->membershipFor($next->id)?->isClient() ? 'portal.dashboard' : 'dashboard');
        }

        $user->forceFill(['current_workspace_id' => null])->save();
        $this->toast($message, 'info', 'Create a workspace to keep going.');

        return to_route('onboarding.workspace');
    }
}
