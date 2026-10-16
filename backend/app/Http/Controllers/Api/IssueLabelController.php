<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncIssueLabelsRequest;
use App\Http\Resources\LabelResource;
use App\Models\Issue;
use App\Models\Label;
use App\Services\Issues\LabelService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IssueLabelController extends Controller
{
    public function __construct(
        private readonly LabelService $labels,
    ) {
    }

    public function index(Issue $issue): AnonymousResourceCollection
    {
        $this->authorize('view', $issue);

        return LabelResource::collection($this->labels->listForIssue($issue));
    }

    /**
     * Replace all labels on the issue.
     */
    public function sync(SyncIssueLabelsRequest $request, Issue $issue): AnonymousResourceCollection
    {
        $this->authorize('update', $issue);

        $labels = $this->labels->syncIssueLabels(
            $issue,
            $request->user(),
            $request->validated('label_ids'),
        );

        return LabelResource::collection($labels);
    }

    /**
     * Attach the given labels, keeping existing ones.
     */
    public function attach(SyncIssueLabelsRequest $request, Issue $issue): AnonymousResourceCollection
    {
        $this->authorize('update', $issue);

        $current = $issue->labels()->pluck('labels.id')->all();
        $labelIds = array_values(array_unique(array_merge(
            array_map('intval', $current),
            $request->validated('label_ids'),
        )));

        $labels = $this->labels->syncIssueLabels($issue, $request->user(), $labelIds);

        return LabelResource::collection($labels);
    }

    /**
     * Detach a single label from the issue.
     */
    public function detach(Issue $issue, Label $label): AnonymousResourceCollection
    {
        $this->authorize('update', $issue);

        $current = $issue->labels()->pluck('labels.id')->all();
        $labelIds = array_values(array_diff(array_map('intval', $current), [$label->id]));

        $labels = $this->labels->syncIssueLabels($issue, request()->user(), $labelIds);

        return LabelResource::collection($labels);
    }
}
