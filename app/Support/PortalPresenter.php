<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;

/**
 * Client-facing shapes for portal pages. Deliberately explicit: internal fields
 * (budgets, rates, internal notes, assignee workloads) are never included.
 */
class PortalPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function project(Project $project): array
    {
        $next = $project->relationLoaded('milestones')
            ? $project->milestones->where('status', '!=', 'completed')->sortBy('due_date')->first()
            : null;

        return [
            'id' => $project->id,
            'name' => $project->name,
            'code' => $project->code,
            'color' => $project->color,
            'description' => $project->description,
            'status' => $project->status->value,
            'progress' => $project->progress(),
            'start_date' => $project->start_date?->toDateString(),
            'due_date' => $project->due_date?->toDateString(),
            'completed_at' => $project->completed_at?->toIso8601String(),
            'lead' => $project->relationLoaded('owner') && $project->owner ? ['name' => $project->owner->name, 'initials' => $project->owner->initials, 'avatar_url' => $project->owner->avatar_url, 'title' => $project->owner->title] : null,
            'next_milestone' => $next ? ['name' => $next->name, 'due_date' => $next->due_date?->toDateString()] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function milestone(Milestone $milestone): array
    {
        return [
            'id' => $milestone->id,
            'name' => $milestone->name,
            'description' => $milestone->description,
            'status' => $milestone->status,
            'due_date' => $milestone->due_date?->toDateString(),
            'completed_at' => $milestone->completed_at?->toIso8601String(),
            'requires_approval' => $milestone->requires_approval,
            'approval_status' => $milestone->approval_status,
            'approval_note' => $milestone->approval_note,
            'approved_at' => $milestone->approved_at?->toIso8601String(),
            'project' => $milestone->relationLoaded('project') ? ['id' => $milestone->project->id, 'name' => $milestone->project->name, 'color' => $milestone->project->color] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function task(Task $task): array
    {
        return [
            'id' => $task->id,
            'key' => $task->key(),
            'title' => $task->title,
            'status' => $task->status->value,
            'due_date' => $task->due_date?->toDateString(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'project' => $task->relationLoaded('project') ? ['id' => $task->project->id, 'name' => $task->project->name, 'color' => $task->project->color] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function file(Attachment $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->name,
            'kind' => $file->kind(),
            'size' => $file->size,
            'created_at' => $file->created_at?->toIso8601String(),
            'download_url' => route('portal.files.download', $file),
            'project' => $file->relationLoaded('project') && $file->project ? ['id' => $file->project->id, 'name' => $file->project->name, 'color' => $file->project->color] : null,
            'uploader' => $file->relationLoaded('uploader') && $file->uploader ? ['name' => $file->uploader->name] : null,
        ];
    }

    /**
     * @param  list<int>  $clientUserIds
     * @return array<string, mixed>
     */
    public static function message(Comment $comment, array $clientUserIds, int $viewerId): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'created_at' => $comment->created_at?->toIso8601String(),
            'is_mine' => $comment->user_id === $viewerId,
            'from_client' => in_array($comment->user_id, $clientUserIds, true),
            'author' => $comment->author ? ['name' => $comment->author->name, 'initials' => $comment->author->initials, 'avatar_url' => $comment->author->avatar_url, 'title' => $comment->author->title] : null,
            'project_id' => $comment->commentable_id,
        ];
    }
}
