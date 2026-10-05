<?php

namespace App\Models;

use App\Enums\WorkspaceRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'title', 'timezone', 'theme', 'notification_preferences', 'current_workspace_id', 'avatar_path'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
#[Appends(['avatar_url', 'initials'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Notification types a user can tune, with their default channels.
     *
     * @var array<string, array{label: string, description: string, channels: list<string>}>
     */
    public const NOTIFICATION_TYPES = [
        'task_assigned' => ['label' => 'Task assigned', 'description' => 'Someone assigns a task to you.', 'channels' => ['database', 'mail']],
        'task_completed' => ['label' => 'Task completed', 'description' => 'A task you created or own is completed.', 'channels' => ['database']],
        'project_update' => ['label' => 'Project updates', 'description' => 'Milestones are approved or need changes.', 'channels' => ['database', 'mail']],
        'invoice_paid' => ['label' => 'Invoice paid', 'description' => 'A client pays an invoice.', 'channels' => ['database', 'mail']],
        'invoice_overdue' => ['label' => 'Invoice overdue', 'description' => 'An invoice passes its due date.', 'channels' => ['database', 'mail']],
        'client_comment' => ['label' => 'Client messages', 'description' => 'A client posts a message on a project.', 'channels' => ['database', 'mail']],
        'mention' => ['label' => 'Mentions', 'description' => 'Someone @mentions you in a comment.', 'channels' => ['database', 'mail']],
        'team_invitation' => ['label' => 'Team invitations', 'description' => 'You are invited to another workspace.', 'channels' => ['database', 'mail']],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'notification_preferences' => 'array',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return BelongsToMany<Workspace, $this>
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'memberships')
            ->withPivot(['client_id', 'title', 'status', 'weekly_capacity', 'joined_at', 'last_active_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    /**
     * @return HasMany<TimeEntry, $this>
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    public function membershipFor(Workspace|int $workspace): ?Membership
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $this->memberships->firstWhere('workspace_id', $workspaceId);
    }

    public function belongsToWorkspace(Workspace|int $workspace): bool
    {
        return $this->membershipFor($workspace)?->status === 'active';
    }

    /**
     * The user's role within a given workspace, read directly so it is independent
     * of whichever Spatie team happens to be active.
     */
    public function roleIn(Workspace|int $workspace): ?WorkspaceRole
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        $name = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $this->getMorphClass())
            ->where('model_has_roles.model_id', $this->getKey())
            ->where('model_has_roles.workspace_id', $workspaceId)
            ->value('roles.name');

        return $name ? WorkspaceRole::tryFrom($name) : null;
    }

    public function switchWorkspace(Workspace $workspace): void
    {
        $this->forceFill(['current_workspace_id' => $workspace->id])->save();
        $this->unsetRelation('roles')->unsetRelation('permissions');
    }

    /**
     * A shared demo persona whose credentials must keep working for every visitor.
     */
    public function isDemo(): bool
    {
        return config('orbitops.demo_login') && in_array(strtolower($this->email), config('orbitops.demo_accounts', []), true);
    }

    /**
     * Whether this user wants a given notification type on a given channel.
     */
    public function wantsNotification(string $type, string $channel): bool
    {
        $preference = data_get($this->notification_preferences, "{$type}.{$channel}");

        if ($preference !== null) {
            return (bool) $preference;
        }

        return in_array($channel, self::NOTIFICATION_TYPES[$type]['channels'] ?? ['database'], true);
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function resolvedNotificationPreferences(): array
    {
        return collect(self::NOTIFICATION_TYPES)->mapWithKeys(fn ($config, $type) => [
            $type => [
                'database' => $this->wantsNotification($type, 'database'),
                'mail' => $this->wantsNotification($type, 'mail'),
            ],
        ])->all();
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn () => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null);
    }

    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];

            return mb_strtoupper(collect($parts)->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode(''));
        });
    }

    public function firstName(): string
    {
        return (string) str($this->name)->before(' ');
    }
}
