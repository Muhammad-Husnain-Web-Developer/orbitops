<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Notifications\TaskAssignedNotification;

class SendTaskAssignedNotification
{
    public function handle(TaskAssigned $event): void
    {
        if ($event->assignee->is($event->assignedBy)) {
            return;
        }

        $event->assignee->notify(new TaskAssignedNotification($event->task, $event->assignedBy));
    }
}
