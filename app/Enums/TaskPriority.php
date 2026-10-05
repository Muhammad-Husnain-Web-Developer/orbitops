<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum TaskPriority: string
{
    use HasOptions;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Low => 'neutral',
            self::Medium => 'info',
            self::High => 'warning',
            self::Urgent => 'danger',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Urgent => 4,
        };
    }
}
