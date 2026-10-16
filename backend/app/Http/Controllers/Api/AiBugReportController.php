<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AnalyzeBugReportRequest;
use App\Http\Requests\Ai\ApplyBugReportSuggestionsRequest;
use App\Http\Resources\AiBugReportSuggestionResource;
use App\Http\Resources\IssueResource;
use App\Jobs\AnalyzeBugReportJob;
use App\Models\AiBugReportSuggestion;
use App\Models\Issue;
use App\Models\Project;
use App\Services\Ai\ApplyBugReportSuggestionsService;
use App\Services\Ai\BugReportContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiBugReportController extends Controller
{
    public function __construct(
        private readonly BugReportContextBuilder $contextBuilder,
        private readonly ApplyBugReportSuggestionsService $applyService,
    ) {}

    /**
     * Queue an analysis of a new draft or an existing issue.
     */
    public function analyze(AnalyzeBugReportRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $input = $request->validated();

        if (! empty($input['issue_id'])) {
            $issueExists = Issue::where('id', $input['issue_id'])
                ->where('project_id', $project->id)
                ->exists();

            if (! $issueExists) {
                return response()->json(['message' => 'Issue not found in this project.'], 404);
            }
        }

        $analysis = AiBugReportSuggestion::create([
            'project_id' => $project->id,
            'issue_id' => $input['issue_id'] ?? null,
            'requested_by' => $request->user()->id,
            'status' => 'queued',
            'input_snapshot' => $input,
            'input_hash' => $this->contextBuilder->hashContext($input),
            'expires_at' => now()->addHours(24),
        ]);

        AnalyzeBugReportJob::dispatch($analysis->id);

        return response()->json([
            'data' => [
                'id' => $analysis->id,
                'status' => $analysis->status,
            ],
        ], 202);
    }

    /**
     * Poll the status / suggestions of an analysis.
     */
    public function show(Request $request, AiBugReportSuggestion $analysis): AiBugReportSuggestionResource
    {
        $this->authorize('view', $analysis->project);

        return new AiBugReportSuggestionResource($analysis);
    }

    /**
     * Apply the accepted suggestions to the analyzed issue.
     */
    public function apply(ApplyBugReportSuggestionsRequest $request, AiBugReportSuggestion $analysis): JsonResponse
    {
        $this->authorize('view', $analysis->project);

        $issue = $analysis->issue_id ? Issue::find($analysis->issue_id) : null;

        if ($issue) {
            $this->authorize('update', $issue);
        }

        $accepted = $request->validated('accepted');

        // Let the user override a suggestion with their own edited text.
        $overrides = [];

        if ($request->filled('reviewed_title')) {
            $accepted['title'] = true;
            $overrides['improved_title'] = $request->input('reviewed_title');
        }

        if ($request->filled('reviewed_description')) {
            $accepted['description'] = true;
            $overrides['improved_description'] = $request->input('reviewed_description');
        }

        if ($request->filled('reviewed_priority')) {
            $accepted['priority'] = true;
            $overrides['suggested_priority'] = $request->input('reviewed_priority');
        }

        if ($overrides !== []) {
            $analysis->suggestions = array_merge($analysis->suggestions ?? [], $overrides);
            $analysis->save();
        }

        $updatedIssue = $this->applyService->apply(
            $analysis,
            $accepted,
            $request->user(),
            $issue,
        );

        return response()->json([
            'message' => 'Suggestions applied successfully.',
            'issue' => $updatedIssue ? new IssueResource($updatedIssue) : null,
        ]);
    }

    /**
     * Dismiss an analysis without applying anything.
     */
    public function dismiss(Request $request, AiBugReportSuggestion $analysis): JsonResponse
    {
        $this->authorize('view', $analysis->project);

        if ($analysis->status !== 'dismissed') {
            $analysis->update(['status' => 'dismissed']);
        }

        return response()->json(['message' => 'Analysis dismissed.']);
    }
}
