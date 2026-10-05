<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'client_id', 'owner_id', 'name', 'code', 'description', 'status', 'priority', 'color', 'billing_type', 'budget', 'hourly_rate', 'start_date', 'due_date', 'completed_at'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'budget' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<Milestone, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('position');
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * The client-facing conversation thread.
     *
     * @return MorphMany<Comment, $this>
     */
    public function messages(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Eager-load the counts needed to render progress everywhere a project appears.
     *
     * @param  Builder<Project>  $query
     */
    public function scopeWithProgress(Builder $query): void
    {
        $query->withCount([
            'tasks',
            'tasks as completed_tasks_count' => fn ($q) => $q->where('status', TaskStatus::Done),
        ]);
    }

    /**
     * @param  Builder<Project>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', ProjectStatus::open());
    }

    public function progress(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $done = $this->completed_tasks_count ?? $this->tasks()->where('status', TaskStatus::Done)->count();

        return $total > 0 ? (int) round($done / $total * 100) : 0;
    }

    public function nextTaskNumber(): int
    {
        return (int) Task::withoutGlobalScopes()->withTrashed()->where('project_id', $this->id)->max('number') + 1;
    }
}
