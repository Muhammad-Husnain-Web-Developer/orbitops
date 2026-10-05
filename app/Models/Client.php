<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\InvoiceStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'name', 'industry', 'status', 'contact_name', 'email', 'phone', 'website', 'address', 'city', 'country', 'currency', 'created_by'])]
#[Appends(['initials'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClientStatus::class,
        ];
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
     * @return HasMany<Attachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Internal notes about the client.
     *
     * @return MorphMany<Comment, $this>
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->latest();
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function portalMemberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Sum of invoices still owed by this client.
     */
    public function outstandingBalance(): float
    {
        return (float) $this->invoices()
            ->whereIn('status', InvoiceStatus::outstanding())
            ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as balance')
            ->value('balance');
    }

    protected function initials(): Attribute
    {
        return Attribute::get(function () {
            $words = preg_split('/\s+/', trim($this->name)) ?: [];

            return mb_strtoupper(collect($words)->take(2)->map(fn ($word) => mb_substr($word, 0, 1))->implode(''));
        });
    }
}
