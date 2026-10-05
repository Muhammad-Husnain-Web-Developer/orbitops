<?php

namespace App\Events;

use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Pushes new timeline entries to everyone watching the workspace in realtime.
 */
class ActivityRecorded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Activity $activity) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('workspace.'.$this->activity->workspace_id)];
    }

    public function broadcastAs(): string
    {
        return 'activity.recorded';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->activity->loadMissing('causer');

        return ['activity' => (new ActivityResource($this->activity))->resolve()];
    }
}
