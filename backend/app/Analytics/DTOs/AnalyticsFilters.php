<?php

namespace App\Analytics\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsFilters
{
    /**
     * @param  list<string>  $priorities
     * @param  list<string>  $statuses
     */
    public function __construct(
        public readonly int $projectId,
        public readonly Carbon $periodStart,
        public readonly Carbon $periodEnd,
        public readonly ?string $timezone = 'UTC',
        public readonly array $priorities = [],
        public readonly array $statuses = [],
        public readonly ?int $assigneeId = null,
        public readonly ?int $sprintId = null,
    ) {}

    public static function fromRequest(Request $request, int $projectId): self
    {
        $timezone = $request->input('timezone', config('app.timezone', 'UTC'));

        $start = $request->filled('start')
            ? Carbon::parse($request->input('start'), $timezone)
            : now()->subDays(30);

        $end = $request->filled('end')
            ? Carbon::parse($request->input('end'), $timezone)
            : now();

        return new self(
            projectId: $projectId,
            periodStart: $start->copy()->startOfDay(),
            periodEnd: $end->copy()->endOfDay(),
            timezone: $timezone,
            priorities: array_values((array) $request->input('priority', [])),
            statuses: array_values((array) $request->input('status', [])),
            assigneeId: $request->filled('assignee_id') ? (int) $request->input('assignee_id') : null,
            sprintId: $request->filled('sprint_id') ? (int) $request->input('sprint_id') : null,
        );
    }

    /**
     * Build a stable hash for caching.
     */
    public function cacheKey(): string
    {
        return 'analytics:'.md5(json_encode([
            'project' => $this->projectId,
            'start' => $this->periodStart->toIso8601String(),
            'end' => $this->periodEnd->toIso8601String(),
            'tz' => $this->timezone,
            'priorities' => $this->priorities,
            'statuses' => $this->statuses,
            'assignee' => $this->assigneeId,
            'sprint' => $this->sprintId,
        ]));
    }
}
