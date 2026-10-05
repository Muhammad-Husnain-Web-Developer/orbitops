<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Personal account data shared by workspace settings and the client portal account page.
 */
class AccountSettings
{
    /**
     * @return array<string, mixed>
     */
    public static function profile(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'title' => $user->title,
            'timezone' => $user->timezone ?? 'UTC',
            'avatar_url' => $user->avatar_url,
            'initials' => $user->initials,
            'email_verified' => $user->hasVerifiedEmail(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function timezones(): array
    {
        return collect(DateTimeZone::listIdentifiers())
            ->map(fn ($zone) => ['value' => $zone, 'label' => str_replace(['/', '_'], [' / ', ' '], $zone)])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function security(Request $request): array
    {
        $user = $request->user();

        return [
            'two_factor' => [
                'enabled' => $user->two_factor_secret !== null,
                'confirmed' => $user->two_factor_confirmed_at !== null,
            ],
            'sessions' => self::sessions($request),
        ];
    }

    /**
     * Browser sessions for this user (database session driver only).
     *
     * @return list<array<string, mixed>>
     */
    public static function sessions(Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => [
                'id' => hash('sha256', $session->id),
                'ip' => $session->ip_address,
                'current' => $session->id === $request->session()->getId(),
                'last_active' => CarbonImmutable::createFromTimestamp($session->last_activity)->toIso8601String(),
                ...self::agent((string) $session->user_agent),
            ])
            ->all();
    }

    /**
     * A readable browser/OS label from a user-agent string.
     *
     * @return array{browser: string, platform: string, mobile: bool}
     */
    public static function agent(string $agent): array
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') || str_contains($agent, 'Chromium/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Browser',
        };

        $platform = match (true) {
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS X') || str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Unknown OS',
        };

        return ['browser' => $browser, 'platform' => $platform, 'mobile' => (bool) preg_match('/Mobile|Android|iPhone/', $agent)];
    }
}
