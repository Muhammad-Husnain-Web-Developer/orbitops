<?php

namespace App\Support;

use Illuminate\Support\Number;

class Money
{
    /**
     * Format an amount (decimal casts arrive as strings) in the given currency.
     */
    public static function format(string|int|float|null $amount, string $currency = 'USD'): string
    {
        return Number::currency((float) $amount, in: $currency);
    }
}
