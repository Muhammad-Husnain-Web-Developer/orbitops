<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\MilestoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['workspace_id', 'project_id', 'name', 'description', 'due_date', 'status', 'position', 'requires_approval', 'approval_status', 'approval_note', 'approved_by', 'approved_at', 'completed_at'])]
class Milestone extends Model
{
    /** @use HasFactory<MilestoneFactory> */
    use BelongsToWorkspace, HasFactory;

    public const STATUSES = ['pending', 'in_progress', 'completed'];

    public const APPROVAL_STATUSES = ['pending', 'approved', 'changes_requested'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'requires_approval' => 'boolean',
            'approved_at' => 'datetime',
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
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
