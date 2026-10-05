<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum InvoiceStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Sent = 'sent';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Sent => 'info',
            self::Paid => 'success',
            self::Overdue => 'danger',
            self::Cancelled => 'neutral',
        };
    }

    /**
     * Statuses where money is still owed.
     *
     * @return list<string>
     */
    public static function outstanding(): array
    {
        return [self::Sent->value, self::Overdue->value];
    }
}
