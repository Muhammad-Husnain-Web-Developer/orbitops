<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\AcceptInvitation;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $invitation = $this->find($token);

        if ($invitation && ! $request->user()) {
            // Remember the invitation through sign-up or log-in.
            $request->session()->put('invitation', ['token' => $token, 'email' => $invitation->email]);
            $request->session()->put('url.intended', route('invitations.show', $token));
        }

        return inertia('Invitations/Show', [
            'token' => $token,
            'invitation' => $invitation ? [
                'email' => $invitation->email,
                'role' => $invitation->role,
                'workspace' => $invitation->workspace->only(['name', 'industry', 'initials', 'accent']),
                'inviter' => $invitation->inviter?->only(['name', 'initials', 'avatar_url']),
                'expires_at' => $invitation->expires_at?->toIso8601String(),
            ] : null,
            'valid' => $invitation && ! $invitation->accepted_at && ! $invitation->isExpired(),
            'emailMatches' => $invitation && $request->user() && strcasecmp($invitation->email, $request->user()->email) === 0,
        ]);
    }

    public function accept(Request $request, string $token, AcceptInvitation $accept): RedirectResponse
    {
        $invitation = $this->find($token);
        abort_if($invitation === null, 404);

        $accept->handle($invitation, $request->user());
        $request->session()->forget(['invitation', 'url.intended']);

        $this->toast("Welcome to {$invitation->workspace->name}", description: 'You can switch workspaces from the sidebar at any time.');

        return redirect()->route($invitation->client_id ? 'portal.dashboard' : 'dashboard');
    }

    protected function find(string $token): ?Invitation
    {
        return Invitation::withoutGlobalScopes()->with(['workspace', 'inviter'])->where('token', $token)->first();
    }
}
