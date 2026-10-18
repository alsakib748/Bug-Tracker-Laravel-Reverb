<?php

namespace App\Analytics\Support;

use App\Enums\IssueStatus;
use App\Enums\Priority;

class MetricDefinitions
{
    /**
     * Status groupings used across all analytics.
     *
     * @var list<string>
     */
    public const STATUS_OPEN = ['open', 'assigned', 'in_progress', 'code_review', 'testing', 'reopened'];

    /**
     * @var list<string>
     */
    public const STATUS_RESOLVED = ['resolved', 'closed'];

    /**
     * @var list<string>
     */
    public const STATUS_TERMINAL = ['closed'];

    public static function isOpen(string $status): bool
    {
        return in_array($status, self::STATUS_OPEN, true);
    }

    public static function isResolved(string $status): bool
    {
        return in_array($status, self::STATUS_RESOLVED, true);
    }

    /**
     * @return list<string>
     */
    public static function allOpenStatuses(): array
    {
        return self::STATUS_OPEN;
    }

    /**
     * @return list<string>
     */
    public static function allResolvedStatuses(): array
    {
        return self::STATUS_RESOLVED;
    }

    /**
     * The canonical priority ordering.
     *
     * @return list<string>
     */
    public static function priorityOrder(): array
    {
        return array_map(fn (Priority $priority) => $priority->value, Priority::cases());
    }

    /**
     * @return list<string>
     */
    public static function allStatuses(): array
    {
        return array_map(fn (IssueStatus $status) => $status->value, IssueStatus::cases());
    }

    /**
     * Bump this when metric calculation logic changes, to invalidate caches.
     */
    public static function version(): string
    {
        return 'v1';
    }
}
