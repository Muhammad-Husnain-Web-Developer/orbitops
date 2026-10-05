<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspace;

class ClientPolicy
{
    use ChecksWorkspace;

    public function viewAny(User $user): bool
    {
        return $user->can('clients.view');
    }

    public function view(User $user, Client $client): bool
    {
        return $this->allowed($user, 'clients.view', $client);
    }

    public function create(User $user): bool
    {
        return $user->can('clients.manage');
    }

    public function update(User $user, Client $client): bool
    {
        return $this->allowed($user, 'clients.manage', $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->allowed($user, 'clients.delete', $client);
    }
}
