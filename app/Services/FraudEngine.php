<?php

namespace App\Services;

use App\Models\ListenSession;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class FraudEngine
{
    public const FLAG_TOO_FAST = 'too_fast';
    public const FLAG_BACKGROUNDED = 'backgrounded';

    public function listenSessionRisk(ListenSession $session): array
    {
        $flags = [];

        if (! $session->foreground) {
            $flags[] = self::FLAG_BACKGROUNDED;
        }

        if ($session->completed_at && $session->started_at
            && abs($session->completed_at->diffInSeconds($session->started_at)) < $session->min_duration_seconds - 5) {
            $flags[] = self::FLAG_TOO_FAST;
        }

        return $flags;
    }

    public function hitDailyLimit(User $listener, int $maxPerDay): bool
    {
        $cacheKey = 'session_count:' . $listener->id . ':' . now()->toDateString();

        return (int) Cache::get($cacheKey, 0) > $maxPerDay;
    }

    public function incrementDailyCount(User $listener): int
    {
        $cacheKey = 'session_count:' . $listener->id . ':' . now()->toDateString();
        $count = Cache::increment($cacheKey);

        return $count ?? 0;
    }
}