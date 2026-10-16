<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IssueWatcherResource;
use App\Models\Issue;
use App\Services\Issues\WatcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IssueWatcherController extends Controller
{
    public function __construct(
        private readonly WatcherService $watchers,
    ) {
    }

    public function index(Issue $issue): AnonymousResourceCollection
    {
        $this->authorize('view', $issue);

        return IssueWatcherResource::collection($this->watchers->listForIssue($issue));
    }

    /**
     * Current user's watch status for the issue.
     */
    public function status(Request $request, Issue $issue): JsonResponse
    {
        $this->authorize('view', $issue);

        return response()->json([
            'watching' => $this->watchers->isWatching($issue, $request->user()),
            'count' => $issue->watchers()->count(),
        ]);
    }

    public function watch(Request $request, Issue $issue): JsonResponse
    {
        $this->authorize('view', $issue);

        $this->watchers->watch($issue, $request->user());

        return response()->json([
            'watching' => true,
            'count' => $issue->watchers()->count(),
        ]);
    }

    public function unwatchMe(Request $request, Issue $issue): JsonResponse
    {
        $this->authorize('view', $issue);

        $this->watchers->unwatch($issue, $request->user());

        return response()->json([
            'watching' => false,
            'count' => $issue->watchers()->count(),
        ]);
    }
}
