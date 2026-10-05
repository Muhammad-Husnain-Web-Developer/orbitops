<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'project_id', 'milestone_id', 'number', 'title', 'description', 'status', 'priority', 'assignee_id', 'creator_id', 'due_date', 'position', 'estimate_minutes', 'visible_to_client', 'completed_at'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Task $task) {
            if (! $task->number && $task->project_id) {
                $task->number = Project::withoutGlobalScopes()->find($task->project_id)?->nextTaskNumber() ?? 1;
            }
        });

        static::saving(function (Task $task) {
            if ($task->isDirty('status')) {
                $task->completed_at = $task->status === TaskStatus::Done ? ($task->completed_at ?? now()) : null;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
            'visible_to_client' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Milestone, $this>
     */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * @param  Builder<Task>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', '!=', TaskStatus::Done);
    }

    /**
     * @param  Builder<Task>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->open()->whereDate('due_date', '<', today());
    }

    public function key(): string
    {
        return ($this->project?->code ?: 'TASK').'-'.$this->number;
    }
}
