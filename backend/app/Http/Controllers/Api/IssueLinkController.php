<?php

namespace App\Http\Controllers\Api;

use App\Enums\IssueLinkType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIssueLinkRequest;
use App\Http\Resources\IssueLinkResource;
use App\Models\Issue;
use App\Models\IssueLink;
use App\Services\Issues\IssueLinkService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class IssueLinkController extends Controller
{
    public function __construct(
        private readonly IssueLinkService $links,
    ) {
    }

    public function index(Issue $issue): AnonymousResourceCollection
    {
        $this->authorize('view', $issue);

        return IssueLinkResource::collection($this->links->listForIssue($issue));
    }

    public function store(StoreIssueLinkRequest $request, Issue $issue): IssueLinkResource
    {
        $this->authorize('update', $issue);

        $link = $this->links->create(
            $issue,
            $request->user(),
            (int) $request->validated('target_issue_id'),
            IssueLinkType::from($request->validated('link_type')),
        );

        return new IssueLinkResource($link);
    }

    public function destroy(Request $request, IssueLink $issueLink): Response
    {
        $this->authorize('update', $issueLink->sourceIssue);

        $this->links->delete($issueLink, $request->user());

        return response()->noContent();
    }
}
