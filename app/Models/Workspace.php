<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'industry', 'owner_id', 'accent', 'logo_path', 'currency', 'timezone', 'invoice_prefix', 'default_tax_rate', 'payment_terms', 'plan', 'billing_cycle', 'is_demo'])]
#[Appends(['initials', 'logo_url'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    /**
     * Brand accents a workspace can pick to signal tenant context.
     */
    public const ACCENTS = ['violet', 'blue', 'cyan', 'emerald', 'amber', 'rose'];

    protected static function booted(): void
    {
        static::creating(function (Workspace $workspace) {
            $workspace->slug ??= static::uniqueSlug($workspace->name);
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_tax_rate' => 'decimal:2',
            'payment_terms' => 'integer',
            'is_demo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot(['client_id', 'title', 'status', 'weekly_capacity', 'joined_at', 'last_active_at'])
            ->withTimestamps();
    }

    /**
     * Internal team members (everyone except client portal users).
     *
     * @return BelongsToMany<User, $this>
     */
    public function teamMembers(): BelongsToMany
    {
        return $this->users()->wherePivotNull('client_id')->wherePivot('status', 'active');
    }

    /**
     * @return HasMany<Invitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Active team members whose role in this workspace grants a permission.
     *
     * @return Collection<int, User>
     */
    public function membersWithPermission(string $permission): Collection
    {
        $userIds = DB::table('model_has_roles')
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('model_has_roles.workspace_id', $this->id)
            ->where('model_has_roles.model_type', (new User)->getMorphClass())
            ->where('permissions.name', $permission)
            ->pluck('model_has_roles.model_id');

        return $this->teamMembers()->whereIn('users.id', $userIds)->get();
    }

    public function nextInvoiceNumber(): string
    {
        $last = Invoice::withoutGlobalScopes()->withTrashed()
            ->where('workspace_id', $this->id)
            ->where('number', 'like', $this->invoice_prefix.'-%')
            ->pluck('number')
            ->map(fn ($number) => (int) Str::afterLast($number, '-'))
            ->max() ?? 1000;

        return sprintf('%s-%04d', $this->invoice_prefix, $last + 1);
    }

    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $words = preg_split('/\s+/', trim($this->name)) ?: [];

            return mb_strtoupper(collect($words)->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode(''));
        });
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null);
    }
}
