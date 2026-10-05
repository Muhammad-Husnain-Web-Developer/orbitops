<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeamInvitationRequest;
use App\Models\Activity;
use App\Models\Invitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;

class TeamInvitationController extends Controller
{
    public function store(TeamInvitationRequest $request): RedirectResponse
    {
        $emails = $request->validated('emails');

        foreach ($emails as $email) {
            $invitation = Invitation::create([
                'email' => $email,
                'role' => $request->validated('role'),
                'client_id' => $request->validated('role') === 'client' ? $request->validated('client_id') : null,
                'invited_by' => $request->user()->id,
            ]);

            $this->deliver($invitation);
            Activity::record('member.invited', "invited {$email} to the workspace");
        }

        $this->toast(count($emails) === 1 ? 'Invitation sent' : count($emails).' invitations sent', description: implode(', ', $emails));

        return back();
    }

    public function resend(Invitation $invitation): RedirectResponse
    {
        $this->authorize('manageMembers', $this->workspace());
        abort_if($invitation->accepted_at !== null, 404);

        // A fresh link and expiry; the old link stops working.
        $invitation->forceFill(['token' => str()->random(48), 'expires_at' => now()->addDays(7)])->save();
        $this->deliver($invitation);

        $this->toast('Invitation resent', description: $invitation->email);

        return back();
    }

    public function destroy(Invitation $invitation): RedirectResponse
    {
        $this->authorize('manageMembers', $this->workspace());
        abort_if($invitation->accepted_at !== null, 404);

        $invitation->delete();
        $this->toast('Invitation revoked', 'info', $invitation->email);

        return back();
    }

    /**
     * Email the invite, and show it in-app too when the person already has an account.
     */
    protected function deliver(Invitation $invitation): void
    {
        $notification = new TeamInvitationNotification($invitation);
        $existing = User::where('email', $invitation->email)->first();

        $existing
            ? $existing->notify($notification)
            : Notification::route('mail', $invitation->email)->notify($notification);
    }
}
