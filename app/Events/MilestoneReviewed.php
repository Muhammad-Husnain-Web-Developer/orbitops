<?php

namespace App\Events;

use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MilestoneReviewed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Milestone $milestone, public User $reviewer) {}
}
