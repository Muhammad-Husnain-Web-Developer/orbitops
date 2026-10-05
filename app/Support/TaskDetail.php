<?php

namespace App\Support;

use App\Http\Resources\ActivityResource;
use App\Http\Resources\TaskResource;
use App\Models\Activity;
use App\Models\Task;
use App\Models\TimeEntry;

/**
 * Full payload for the task drawer, shared by the Tasks and Project pages so a
 * task opened from either place (or a deep link) looks identical.
 */
class TaskDetail
{
    /**
     * @return array<string, mixed>|null
     */
    public static function for(?int $taskId, ?int $projectId = null): ?array
    {
        if (! $taskId) {
            return null;
        }

        $task = Task::query()
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->with(['project:id,name,code,color,client_id', 'milestone:id,name', 'assignee', 'creator', 'comments.author', 'attachments.uploader'])
            ->find($taskId);

        if (! $task) {
            return null;
        }

        return [
            ...(new TaskResource($task))->resolve(),
            'tracked_seconds' => (int) TimeEntry::where('task_id', $task->id)->sum('duration_seconds'),
            'milestones' => $task->project->milestones()->get(['id', 'name'])->toArray(),
            'activity' => ActivityResource::collection(
                Activity::where('subject_type', 'task')->where('subject_id', $task->id)->with('causer')->latest()->limit(15)->get()
            )->resolve(),
        ];
    }
}
