<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['workspace_id', 'client_id', 'project_id', 'number', 'status', 'issue_date', 'due_date', 'currency', 'subtotal', 'tax_rate', 'tax_amount', 'discount_type', 'discount_value', 'discount_amount', 'total', 'amount_paid', 'notes', 'terms', 'sent_at', 'paid_at', 'created_by'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<Invoice>  $query
     */
    public function scopeOutstanding(Builder $query): void
    {
        $query->whereIn('status', InvoiceStatus::outstanding());
    }

    /**
     * Recompute subtotal, discount, tax and total from line items.
     */
    public function recalculate(): void
    {
        $subtotal = round((float) $this->items()->sum('amount'), 2);

        $discount = $this->discount_type === 'percent'
            ? round($subtotal * (float) $this->discount_value / 100, 2)
            : min((float) $this->discount_value, $subtotal);

        $taxable = $subtotal - $discount;
        $tax = round($taxable * (float) $this->tax_rate / 100, 2);

        $this->forceFill([
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total' => round($taxable + $tax, 2),
        ])->save();
    }

    public function balance(): float
    {
        return max(0, round((float) $this->total - (float) $this->amount_paid, 2));
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [InvoiceStatus::Draft, InvoiceStatus::Sent, InvoiceStatus::Overdue], true);
    }
}
