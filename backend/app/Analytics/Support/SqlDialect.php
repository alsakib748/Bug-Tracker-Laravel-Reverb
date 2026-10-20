<?php

namespace App\Analytics\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Portable SQL fragments for analytics aggregations.
 *
 * The application runs on MySQL while the test suite runs on SQLite, so the
 * expressions exposed here are limited to those with identical semantics on
 * both drivers. Anything that cannot be expressed portably (such as a median)
 * stays in PHP, but operates on already-aggregated rows.
 */
final class SqlDialect
{
    /**
     * Day bucket label ("Y-m-d") for a datetime column.
     */
    public static function date(string $column): string
    {
        return "DATE({$column})";
    }

    /**
     * Absolute difference in minutes between two datetime columns.
     */
    public static function minutesBetween(string $start, string $end): string
    {
        if (self::driver() === 'sqlite') {
            return "ABS(ROUND((julianday({$end}) - julianday({$start})) * 1440))";
        }

        return "ABS(TIMESTAMPDIFF(MINUTE, {$start}, {$end}))";
    }

    /**
     * Signed difference in seconds between two datetime columns (to - from).
     */
    public static function secondsBetween(string $from, string $to): string
    {
        if (self::driver() === 'sqlite') {
            return "((julianday({$to}) - julianday({$from})) * 86400)";
        }

        return "TIMESTAMPDIFF(SECOND, {$from}, {$to})";
    }

    /**
     * Fold a day-level count map ("Y-m-d" => count) into the requested
     * granularity, preserving the labels used by the API contract.
     *
     * @param  array<string, int>  $dayCounts
     * @return array<string, int>
     */
    public static function bucketDays(array $dayCounts, string $granularity): array
    {
        $buckets = [];

        foreach ($dayCounts as $day => $count) {
            $label = match ($granularity) {
                'week' => Carbon::parse($day)->isoFormat('GGGG-[W]WW'),
                'month' => substr($day, 0, 7),
                default => $day,
            };

            $buckets[$label] = ($buckets[$label] ?? 0) + $count;
        }

        ksort($buckets);

        return $buckets;
    }

    private static function driver(): string
    {
        return DB::connection()->getDriverName();
    }
}
