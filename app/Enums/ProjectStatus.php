<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ProjectStatus: string
{
    use HasOptions;

    case Planning = 'planning';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::Active => 'Active',
            self::OnHold => 'On Hold',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Planning => 'info',
            self::Active => 'accent',
            self::OnHold => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'neutral',
        };
    }

    /**
     * Statuses that count as "open" work.
     *
     * @return list<string>
     */
    public static function open(): array
    {
        return [self::Planning->value, self::Active->value, self::OnHold->value];
    }
}
