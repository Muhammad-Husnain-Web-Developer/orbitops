<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspace;

class AttachmentPolicy
{
    use ChecksWorkspace;

    public function viewAny(User $user): bool
    {
        return $user->can('files.view');
    }

    public function view(User $user, Attachment $attachment): bool
    {
        return $this->allowed($user, 'files.view', $attachment);
    }

    public function create(User $user): bool
    {
        return $user->can('files.manage');
    }

    public function update(User $user, Attachment $attachment): bool
    {
        return $this->allowed($user, 'files.manage', $attachment);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $this->allowed($user, 'files.manage', $attachment);
    }
}
