<?php

namespace App\Actions\Fortify;

use App\Actions\Workspaces\CreateWorkspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(protected CreateWorkspace $createWorkspace) {}

    /**
     * Validate and create a newly registered user, along with their first workspace.
     * People joining through an invitation skip the workspace and join the inviter's.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $invited = session()->has('invitation.token');

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'workspace' => [$invited ? 'nullable' : 'required', 'string', 'min:2', 'max:80'],
            'plan' => ['nullable', Rule::in(array_keys(config('orbitops.plans')))],
            'password' => $this->passwordRules(),
            'terms' => ['accepted'],
        ], [
            'workspace.required' => 'Name your workspace — usually your company or team name.',
            'terms.accepted' => 'Please accept the terms to continue.',
        ])->validate();

        return DB::transaction(function () use ($input, $invited) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]);

            if (! $invited) {
                $this->createWorkspace->handle($user, [
                    'name' => $input['workspace'],
                    'plan' => $input['plan'] ?? 'starter',
                ]);
            }

            return $user;
        });
    }
}
