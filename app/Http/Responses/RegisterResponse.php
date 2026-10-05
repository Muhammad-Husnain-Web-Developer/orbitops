<?php

namespace App\Http\Responses;

use App\Actions\Workspaces\AcceptInvitation;
use App\Models\Invitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * After sign-up, people who arrived through an invitation join that workspace straight
 * away. The invitation link proves they control the address, so it also verifies it.
 */
class RegisterResponse implements RegisterResponseContract
{
    public function __construct(protected AcceptInvitation $accept) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $user = $request->user();
        $token = $request->session()->pull('invitation.token');
        $request->session()->forget('invitation.email');

        $invitation = $token ? Invitation::withoutGlobalScopes()->where('token', $token)->first() : null;

        if ($invitation && strcasecmp($invitation->email, $user->email) === 0 && ! $invitation->accepted_at && ! $invitation->isExpired()) {
            $user->markEmailAsVerified();
            $this->accept->handle($invitation, $user);
        }

        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect()->intended(config('fortify.home'));
    }
}
