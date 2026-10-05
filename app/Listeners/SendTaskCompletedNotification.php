<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Notifications\TaskCompletedNotification;

class SendTaskCompletedNotification
{
    /**
     * Tell the task's creator and the project owner, but never the person who completed it.
     */
    public function handle(TaskCompleted $event): void
    {
        $event->task->loadMissing('creator', 'project.owner');

        collect([$event->task->creator, $event->task->project->owner])
            ->filter()
            ->unique('id')
            ->reject(fn ($user) => $user->is($event->completedBy))
            ->each(fn ($user) => $user->notify(new TaskCompletedNotification($event->task, $event->completedBy)));
    }
}
