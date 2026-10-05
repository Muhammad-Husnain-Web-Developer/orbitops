<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ExpenseStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Reimbursed = 'reimbursed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Reimbursed => 'info',
        };
    }
}
