<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\TimeEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['workspace_id', 'user_id', 'project_id', 'task_id', 'description', 'started_at', 'ended_at', 'duration_seconds', 'billable'])]
class TimeEntry extends Model
{
    /** @use HasFactory<TimeEntryFactory> */
    use BelongsToWorkspace, HasFactory;

    protected static function booted(): void
    {
        static::saving(function (TimeEntry $entry) {
            if ($entry->ended_at && $entry->started_at) {
                $entry->duration_seconds = max(0, (int) $entry->started_at->diffInSeconds($entry->ended_at));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
            'billable' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @param  Builder<TimeEntry>  $query
     */
    public function scopeRunning(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    /**
     * @param  Builder<TimeEntry>  $query
     */
    public function scopeCompleted(Builder $query): void
    {
        $query->whereNotNull('ended_at');
    }

    public function isRunning(): bool
    {
        return $this->ended_at === null;
    }

    public function stop(): void
    {
        $this->ended_at = now();
        $this->save();
    }
}
