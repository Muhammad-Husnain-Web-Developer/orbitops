<?php

namespace App\Listeners;

use App\Events\MilestoneReviewed;
use App\Notifications\ProjectUpdateNotification;
use Illuminate\Support\Facades\Notification;

class SendMilestoneReviewNotification
{
    public function handle(MilestoneReviewed $event): void
    {
        $project = $event->milestone->project;
        $recipients = $project->members()->get()->push($project->owner)->filter()->unique('id');

        Notification::send($recipients, new ProjectUpdateNotification($event->milestone, $event->reviewer));
    }
}
