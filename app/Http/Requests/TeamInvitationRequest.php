<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceRole;
use App\Http\Requests\Concerns\ValidatesTenantReferences;
use App\Models\Invitation;
use App\Support\CurrentWorkspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TeamInvitationRequest extends FormRequest
{
    use ValidatesTenantReferences;

    public function authorize(): bool
    {
        return $this->user()->can('manageMembers', app(CurrentWorkspace::class)->get());
    }

    protected function prepareForValidation(): void
    {
        // Accept "a@x.com, b@y.com" or one per line, de-duplicated and normalised.
        $emails = preg_split('/[\s,;]+/', (string) $this->input('emails'), -1, PREG_SPLIT_NO_EMPTY);

        $this->merge(['emails' => collect($emails)->map(fn ($email) => strtolower(trim($email)))->unique()->values()->all()]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'emails' => ['required', 'array', 'min:1', 'max:10'],
            'emails.*' => ['required', 'email:rfc', 'max:190'],
            'role' => ['required', Rule::in([WorkspaceRole::Admin->value, WorkspaceRole::Manager->value, WorkspaceRole::Member->value, WorkspaceRole::Client->value])],
            'client_id' => ['nullable', 'required_if:role,client', $this->inWorkspace('clients')],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $workspace = app(CurrentWorkspace::class)->get();
                $emails = $this->input('emails');

                $members = $workspace->users()->whereIn('email', $emails)->pluck('email')->map(fn ($email) => strtolower($email));
                if ($members->isNotEmpty()) {
                    $validator->errors()->add('emails', $members->join(', ', ' and ').' already '.($members->count() === 1 ? 'has' : 'have').' access to this workspace.');

                    return;
                }

                $pending = Invitation::pending()->whereIn('email', $emails)->pluck('email');
                if ($pending->isNotEmpty()) {
                    $validator->errors()->add('emails', $pending->join(', ', ' and ').' already '.($pending->count() === 1 ? 'has' : 'have').' a pending invitation. Resend it from the list instead.');

                    return;
                }

                // Client portal users don't take a team seat.
                $limit = config("orbitops.plans.{$workspace->plan}.limits.members");
                if ($limit !== null && $this->input('role') !== WorkspaceRole::Client->value) {
                    $used = $workspace->teamMembers()->count() + Invitation::pending()->where('role', '!=', WorkspaceRole::Client->value)->count();

                    if ($used + count($emails) > $limit) {
                        $validator->errors()->add('emails', "Your plan includes {$limit} team seats and {$used} are in use or invited. Upgrade the plan or remove someone first.");
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'emails.required' => 'Enter at least one email address.',
            'emails.max' => 'Invite up to 10 people at a time.',
            'emails.*.email' => ':input is not a valid email address.',
            'client_id.required_if' => 'Choose which client this person represents.',
        ];
    }
}
