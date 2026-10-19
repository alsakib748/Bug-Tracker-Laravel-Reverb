<?php

namespace App\Http\Controllers\Api;

use App\Analytics\DTOs\AnalyticsFilters;
use App\Analytics\Services\ProjectAnalyticsService;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ProjectAnalyticsService $analytics,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $projectIds = $this->visibleProjectIds($request);

        // An empty scope means "no projects visible" — return a zeroed payload
        // instead of accidentally reporting all-project metrics.
        if ($projectIds === []) {
            return response()->json([
                'period' => null,
                'scope' => ['project_ids' => [], 'key' => 'none'],
                'summary' => $this->emptySummary(),
                'resolution' => ['median_hours' => null, 'sample_size' => 0, 'legacy_sample_size' => 0, 'coverage' => 'unknown'],
                'status_distribution' => [],
                'priority_distribution' => [],
                'reopened_count' => 0,
                'trends' => ['granularity' => 'day', 'series' => ['buckets' => [], 'created' => [], 'resolved' => []]],
                'per_project' => [],
                'metric_definition_version' => 'v1',
            ]);
        }

        $filters = AnalyticsFilters::fromRequest($request, $projectIds);

        return response()->json($this->analytics->dashboard($filters));
    }

    /**
     * Project ids the current user can view. Restricted users only ever see
     * their own membership; unrestricted users see every project.
     *
     * @return list<int>
     */
    protected function visibleProjectIds(Request $request): array
    {
        $user = $request->user();

        if ($user === null) {
            return [];
        }

        return Project::query()
            ->visibleTo($user)
            ->orderBy('name')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    protected function emptySummary(): array
    {
        return [
            'total_issues' => 0,
            'open_issues' => 0,
            'created_in_period' => 0,
            'resolved_in_period' => 0,
            'overdue_issues' => 0,
            'critical_open_issues' => 0,
        ];
    }
}
