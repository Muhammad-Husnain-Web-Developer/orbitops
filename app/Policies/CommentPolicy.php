<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use App\Policies\Concerns\ChecksWorkspace;

class CommentPolicy
{
    use ChecksWorkspace;

    /**
     * Authors can remove their own comments; managers can moderate.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $this->inWorkspace($user, $comment)
            && ($comment->user_id === $user->id || $user->can('projects.manage'));
    }
}
