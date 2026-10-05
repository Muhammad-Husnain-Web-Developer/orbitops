<?php

namespace App\Models;

use App\Events\ActivityRecorded;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['workspace_id', 'causer_id', 'subject_type', 'subject_id', 'project_id', 'client_id', 'event', 'description', 'properties', 'created_at'])]
class Activity extends Model
{
    use BelongsToWorkspace;

    protected static function booted(): void
    {
        static::created(fn (Activity $activity) => ActivityRecorded::dispatch($activity));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    /**
     * Hide finance events from people who can't see invoices or expenses.
     *
     * @param  Builder<Activity>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query
            ->unless($user->can('invoices.view'), fn ($query) => $query->where('event', 'not like', 'invoice.%'))
            ->unless($user->can('expenses.view'), fn ($query) => $query->where('event', 'not like', 'expense.%'));
    }

    /**
     * Record an action in the workspace timeline.
     *
     * The description is a verb phrase ("completed task"); the subject label is
     * stored alongside so the timeline still reads correctly if the subject is deleted.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function record(string $event, string $description, ?Model $subject = null, array $properties = [], ?User $causer = null): self
    {
        $causer ??= auth()->user();

        [$projectId, $clientId] = static::contextFor($subject);

        return static::create([
            'causer_id' => $causer?->id,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'project_id' => $projectId,
            'client_id' => $clientId,
            'event' => $event,
            'description' => $description,
            'properties' => array_filter([
                'label' => $subject ? static::labelFor($subject) : null,
                ...$properties,
            ], fn ($value) => $value !== null),
        ]);
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    protected static function contextFor(?Model $subject): array
    {
        return match (true) {
            $subject instanceof Project => [$subject->id, $subject->client_id],
            $subject instanceof Client => [null, $subject->id],
            $subject instanceof Invoice => [$subject->project_id, $subject->client_id],
            $subject instanceof Task, $subject instanceof Milestone => [$subject->project_id, $subject->project?->client_id],
            $subject instanceof Attachment => [$subject->project_id, $subject->client_id],
            $subject instanceof Expense, $subject instanceof TimeEntry => [$subject->project_id, $subject->project?->client_id],
            default => [null, null],
        };
    }

    protected static function labelFor(Model $subject): ?string
    {
        return match (true) {
            $subject instanceof Task => $subject->title,
            $subject instanceof Invoice => $subject->number,
            $subject instanceof Expense => $subject->description,
            $subject instanceof TimeEntry => $subject->project?->name,
            default => $subject->getAttribute('name'),
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }
}
