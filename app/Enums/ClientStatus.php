<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ClientStatus: string
{
    use HasOptions;

    case Lead = 'lead';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return match ($this) {
            self::Lead => 'info',
            self::Active => 'success',
            self::Inactive => 'neutral',
        };
    }
}
