<?php

namespace App\Analytics\DTOs;

use App\Analytics\Support\MetricDefinitions;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsFilters
{
    /**
     * @param  list<int>  $projectIds  the scope: one project, several, or all visible to the user
     * @param  list<string>  $priorities
     * @param  list<string>  $statuses
     */
    public function __construct(
        public readonly array $projectIds,
        public readonly Carbon $periodStart,
        public readonly Carbon $periodEnd,
        public readonly ?string $timezone = 'UTC',
        public readonly array $priorities = [],
        public readonly array $statuses = [],
        public readonly ?int $assigneeId = null,
        public readonly ?int $sprintId = null,
    ) {}

    /**
     * Build filters scoped to a single project (the analytics endpoints).
     */
    public static function forProject(Request $request, int $projectId): self
    {
        return self::fromRequest($request, [$projectId]);
    }

    /**
     * Build filters for a set of projects. An empty scope is treated as "all".
     */
    public static function fromRequest(Request $request, array $projectIds = []): self
    {
        $timezone = $request->input('timezone', config('app.timezone', 'UTC'));

        // Period is half-open: [periodStart, periodEnd) — periodEnd is exclusive so
        // adjacent periods never double-count a row on a shared boundary.
        $start = $request->filled('start')
            ? Carbon::parse($request->input('start'), $timezone)
            : now()->subDays(30);

        $end = $request->filled('end')
            ? Carbon::parse($request->input('end'), $timezone)
            : now();

        $requestedIds = array_values(array_filter(array_map(
            'intval',
            (array) $request->input('project_ids', $request->input('project_id', []))
        )));

        $scope = $projectIds !== []
            ? array_values(array_intersect($projectIds, $requestedIds ?: $projectIds))
            : ($requestedIds ?: []);

        return new self(
            projectIds: $scope,
            periodStart: $start->copy()->startOfDay(),
            periodEnd: $end->copy()->startOfDay()->addDay(),
            timezone: $timezone,
            priorities: array_values((array) $request->input('priority', [])),
            statuses: array_values((array) $request->input('status', [])),
            assigneeId: $request->filled('assignee_id') ? (int) $request->input('assignee_id') : null,
            sprintId: $request->filled('sprint_id') ? (int) $request->input('sprint_id') : null,
        );
    }

    /**
     * The project scope as a stable cache-key fragment.
     */
    public function scopeKey(): string
    {
        return $this->projectIds === []
            ? 'all'
            : implode('-', $this->projectIds);
    }

    /**
     * Build a stable hash for caching. Scoped by the project set, the filters,
     * the period and the canonical metric definition version.
     */
    public function cacheKey(string $segment): string
    {
        return 'analytics:'.$segment.':'.MetricDefinitions::version().':'.md5(json_encode([
            'scope' => $this->scopeKey(),
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
