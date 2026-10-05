<?php

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\Activity;
use App\Models\Invitation;
use App\Models\User;
use App\Support\CurrentWorkspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptInvitation
{
    public function __construct(
        protected AddWorkspaceMember $addMember,
        protected CurrentWorkspace $current,
    ) {}

    public function handle(Invitation $invitation, User $user): void
    {
        if ($invitation->accepted_at || $invitation->isExpired()) {
            throw ValidationException::withMessages(['invitation' => 'This invitation is no longer valid.']);
        }

        if (strcasecmp($invitation->email, $user->email) !== 0) {
            throw ValidationException::withMessages(['invitation' => "This invitation was sent to {$invitation->email}."]);
        }

        DB::transaction(function () use ($invitation, $user) {
            $workspace = $invitation->workspace;
            $role = WorkspaceRole::tryFrom($invitation->role) ?? WorkspaceRole::Member;

            $this->addMember->handle($workspace, $user, $role, $invitation->client_id);
            $invitation->forceFill(['accepted_at' => now()])->save();
            $user->switchWorkspace($workspace);

            $this->current->runAs($workspace, fn () => Activity::record('member.joined', 'joined the workspace', causer: $user));
        });
    }
}
