<?php

namespace Tests\Unit;

use App\Services\Insights;
use App\Support\ReportPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReportPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-10-05 12:00:00'));
    }

    public function test_presets_resolve_to_the_expected_windows(): void
    {
        $period = ReportPeriod::fromRequest(Request::create('/reports', 'GET', ['range' => '30d']));
        $this->assertSame('2026-09-06', $period->from->toDateString());
        $this->assertSame(30, $period->days());

        $previous = $period->previous();
        $this->assertSame('2026-08-07', $previous->from->toDateString());
        $this->assertSame('2026-09-05', $previous->to->toDateString());

        $this->assertSame('2026-10-01', ReportPeriod::fromRequest(Request::create('/', 'GET', ['range' => 'quarter']))->from->toDateString());
        $this->assertSame('2026-01-01', ReportPeriod::fromRequest(Request::create('/', 'GET', ['range' => 'ytd']))->from->toDateString());
    }

    public function test_invalid_or_reversed_custom_ranges_fall_back_to_ninety_days(): void
    {
        $reversed = ReportPeriod::fromRequest(Request::create('/', 'GET', ['range' => 'custom', 'from' => '2026-09-30', 'to' => '2026-09-01']));
        $garbage = ReportPeriod::fromRequest(Request::create('/', 'GET', ['range' => 'custom', 'from' => 'nope']));
        $unknown = ReportPeriod::fromRequest(Request::create('/', 'GET', ['range' => 'forever']));

        foreach ([$reversed, $garbage, $unknown] as $period) {
            $this->assertSame('90d', $period->preset);
        }

        $custom = ReportPeriod::fromRequest(Request::create('/', 'GET', ['range' => 'custom', 'from' => '2026-09-01', 'to' => '2026-12-31']));
        $this->assertSame('custom', $custom->preset);
        $this->assertSame('2026-10-05', $custom->to->toDateString(), 'Custom ranges never extend past today.');
    }

    public function test_percent_change_has_no_meaning_without_a_baseline(): void
    {
        $insights = app(Insights::class);

        $this->assertNull($insights->percentChange(500, 0));
        $this->assertSame(50.0, $insights->percentChange(150, 100));
        $this->assertSame(-25.0, $insights->percentChange(75, 100));
    }

    public function test_capacity_only_counts_time_after_someone_joined(): void
    {
        $insights = app(Insights::class);
        $from = CarbonImmutable::parse('2026-09-05');
        $to = CarbonImmutable::parse('2026-10-05');

        $this->assertEqualsWithDelta(40 * 30 / 7, $insights->capacity(40, null, $from, $to), 0.01);
        $this->assertEqualsWithDelta(40 * 10 / 7, $insights->capacity(40, '2026-09-25', $from, $to), 0.01);
        $this->assertSame(0.0, $insights->capacity(40, '2026-11-01', $from, $to));
    }
}
