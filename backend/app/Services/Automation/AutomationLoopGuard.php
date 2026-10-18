<?php

namespace App\Services\Automation;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks how deep an issue is inside an automation chain so a rule that keeps
 * triggering itself (or another rule) cannot run away.
 */
class AutomationLoopGuard
{
    private const TTL_MINUTES = 5;

    public function enter(int $issueId): int
    {
        $key = $this->key($issueId);

        $depth = Cache::increment($key);

        if ($depth === false) {
            Cache::put($key, 1, now()->addMinutes(self::TTL_MINUTES));
            $depth = 1;
        } else {
            Cache::put($key, $depth, now()->addMinutes(self::TTL_MINUTES));
        }

        return (int) $depth;
    }

    public function exit(int $issueId): void
    {
        $key = $this->key($issueId);
        $current = (int) Cache::get($key, 0);

        if ($current > 1) {
            Cache::put($key, $current - 1, now()->addMinutes(self::TTL_MINUTES));
        } else {
            Cache::forget($key);
        }
    }

    public function depth(int $issueId): int
    {
        return (int) Cache::get($this->key($issueId), 0);
    }

    public function exceeds(int $issueId, int $max = 3): bool
    {
        return $this->depth($issueId) >= $max;
    }

    private function key(int $issueId): string
    {
        return "automation_loop:{$issueId}";
    }
}
