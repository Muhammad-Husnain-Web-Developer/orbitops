<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable(['workspace_id', 'project_id', 'user_id', 'category', 'vendor', 'description', 'amount', 'currency', 'spent_on', 'receipt_path', 'receipt_name', 'notes', 'status', 'billable'])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use BelongsToWorkspace, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::forceDeleted(function (Expense $expense) {
            if ($expense->receipt_path) {
                Storage::disk('local')->delete($expense->receipt_path);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'status' => ExpenseStatus::class,
            'amount' => 'decimal:2',
            'spent_on' => 'date',
            'billable' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
