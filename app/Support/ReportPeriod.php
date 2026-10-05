<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * A reporting window plus the equal-length window before it, for comparisons.
 */
final class ReportPeriod
{
    public const PRESETS = [
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        'quarter' => 'This quarter',
        'ytd' => 'Year to date',
        '12m' => 'Last 12 months',
        'custom' => 'Custom range',
    ];

    public function __construct(
        public readonly string $preset,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $now = now()->toImmutable();
        $preset = array_key_exists((string) $request->query('range'), self::PRESETS) ? (string) $request->query('range') : '90d';

        if ($preset === 'custom') {
            $from = rescue(fn () => CarbonImmutable::parse((string) $request->query('from'))->startOfDay(), null, false);
            $to = rescue(fn () => CarbonImmutable::parse((string) $request->query('to'))->endOfDay(), null, false);

            // Fall back to the default window for missing, reversed or absurd ranges.
            if ($from && $to && $from->lte($to) && $from->diffInDays($to) <= 366 * 3) {
                return new self('custom', $from, $to->min($now->endOfDay()));
            }

            $preset = '90d';
        }

        return match ($preset) {
            '30d' => new self($preset, $now->subDays(29)->startOfDay(), $now),
            'quarter' => new self($preset, $now->firstOfQuarter()->startOfDay(), $now),
            'ytd' => new self($preset, $now->startOfYear(), $now),
            '12m' => new self($preset, $now->subMonths(11)->startOfMonth(), $now),
            default => new self('90d', $now->subDays(89)->startOfDay(), $now),
        };
    }

    public function previous(): self
    {
        $days = (int) $this->from->diffInDays($this->to) + 1;

        return new self($this->preset, $this->from->subDays($days), $this->from->subSecond());
    }

    public function days(): int
    {
        return (int) $this->from->diffInDays($this->to) + 1;
    }

    public function label(): string
    {
        return $this->preset === 'custom'
            ? $this->from->format('M j, Y').' – '.$this->to->format('M j, Y')
            : self::PRESETS[$this->preset];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'range' => $this->preset,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'label' => $this->label(),
            'days' => $this->days(),
            'previous_from' => $this->previous()->from->toDateString(),
            'previous_to' => $this->previous()->to->toDateString(),
        ];
    }
}
