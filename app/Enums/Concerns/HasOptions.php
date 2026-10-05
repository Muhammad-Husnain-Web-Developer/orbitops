<?php

namespace App\Enums\Concerns;

trait HasOptions
{
    /**
     * Serialize every case for select inputs, filters and badges on the frontend.
     *
     * @return list<array{value: string, label: string, tone: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => $case->toArray(), self::cases());
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array{value: string, label: string, tone: string}
     */
    public function toArray(): array
    {
        return ['value' => $this->value, 'label' => $this->label(), 'tone' => $this->tone()];
    }
}
