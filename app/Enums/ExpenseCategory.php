<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ExpenseCategory: string
{
    use HasOptions;

    case Software = 'software';
    case Contractors = 'contractors';
    case Hardware = 'hardware';
    case Travel = 'travel';
    case Marketing = 'marketing';
    case Office = 'office';
    case Other = 'other';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
