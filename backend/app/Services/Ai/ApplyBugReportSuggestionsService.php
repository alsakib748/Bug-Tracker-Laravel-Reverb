<?php

namespace App\Services\Ai;

use App\Enums\ActivityAction;
use App\Models\AiBugReportSuggestion;
use App\Models\Issue;
use App\Models\Label;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyBugReportSuggestionsService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly BugReportContextBuilder $contextBuilder,
    ) {}

    /**
     * Apply the accepted suggestions to an existing issue.
     *
     * When no issue is supplied (a new issue draft), nothing is persisted and
     * null is returned; the caller applies the suggestions to its local form.
     *
     * @param  array{title?: bool, description?: bool, priority?: bool, label_ids?: array<int>}  $accepted
     *
     * @throws ValidationException
     */
    public function apply(
        AiBugReportSuggestion $analysis,
        array $accepted,
        User $actor,
        ?Issue $issue = null,
    ): ?Issue {
        if ($analysis->hasExpired()) {
            $analysis->markAsExpired();

            throw ValidationException::withMessages([
                'analysis' => 'This analysis has expired. Please run the analysis again.',
            ]);
        }

        if (! $analysis->isReviewable()) {
            throw ValidationException::withMessages([
                'analysis' => 'This analysis is not in a reviewable state.',
            ]);
        }

        $suggestions = $analysis->suggestions ?? [];

        return DB::transaction(function () use ($analysis, $suggestions, $accepted, $actor, $issue) {
            // The analyzed issue may have changed since the analysis was run;
            // applying stale suggestions would overwrite newer content.
            if ($issue && $analysis->input_hash && $analysis->input_hash !== $this->contextBuilder->hashIssue($issue)) {
                throw ValidationException::withMessages([
                    'analysis' => 'This issue has changed since the analysis was run. Please run the analysis again.',
                ]);
            }

            $acceptedLabelIds = array_values(array_unique(array_map('intval', $accepted['label_ids'] ?? [])));

            if ($acceptedLabelIds !== [] && ! $issue) {
                throw ValidationException::withMessages([
                    'label_ids' => 'Labels can only be applied to an existing issue.',
                ]);
            }

            // Never trust the client: labels must belong to the analysis project.
            if ($acceptedLabelIds !== []) {
                $validLabelIds = Label::where('project_id', $analysis->project_id)
                    ->whereIn('id', $acceptedLabelIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (count($validLabelIds) !== count($acceptedLabelIds)) {
                    throw ValidationException::withMessages([
                        'label_ids' => 'One or more labels do not belong to this project.',
                    ]);
                }
            }

            $applied = [];

            if (($accepted['title'] ?? false) && ! empty($suggestions['improved_title'])) {
                $applied['title'] = $suggestions['improved_title'];
            }

            if (($accepted['description'] ?? false) && ! empty($suggestions['improved_description'])) {
                $applied['description'] = $suggestions['improved_description'];
            }

            if (($accepted['priority'] ?? false) && ! empty($suggestions['suggested_priority'])) {
                $applied['priority'] = $suggestions['suggested_priority'];
            }

            $updatedIssue = null;

            if ($issue) {
                $old = $issue->getOriginal();

                if ($applied !== []) {
                    $issue->update($applied);
                }

                if ($acceptedLabelIds !== []) {
                    $issue->labels()->syncWithoutDetaching($acceptedLabelIds);
                }

                $updatedIssue = $issue->fresh(['project', 'reporter', 'assignee', 'labels']);

                $this->activityLogService->log(
                    action: ActivityAction::ISSUE_UPDATED,
                    user: $actor,
                    project: $updatedIssue->project,
                    issue: $updatedIssue,
                    entityType: Issue::class,
                    entityId: $updatedIssue->id,
                    description: "{$actor->name} applied AI suggestions to issue #{$updatedIssue->id}",
                    properties: [
                        'applied_fields' => array_keys($applied),
                        'label_ids' => $acceptedLabelIds,
                        'old' => array_intersect_key($old, $applied),
                    ],
                );
            }

            $status = $analysis->status;

            if ($issue) {
                $suggested = [
                    'title' => ! empty($suggestions['improved_title']),
                    'description' => ! empty($suggestions['improved_description']),
                    'priority' => ! empty($suggestions['suggested_priority']),
                    'label_ids' => ! empty($suggestions['suggested_labels']),
                ];

                $suggestedCount = count(array_filter($suggested));

                $acceptedCount = count(array_filter([
                    'title' => $suggested['title'] && ($accepted['title'] ?? false),
                    'description' => $suggested['description'] && ($accepted['description'] ?? false),
                    'priority' => $suggested['priority'] && ($accepted['priority'] ?? false),
                    'label_ids' => $suggested['label_ids'] && $acceptedLabelIds !== [],
                ]));

                // Leaving a suggested field unapplied keeps the analysis open so
                // the remaining suggestions can be reviewed later.
                $status = ($suggestedCount > 0 && $acceptedCount === $suggestedCount)
                    ? 'applied'
                    : 'partially_applied';
            }

            $analysis->update([
                'review_decisions' => $accepted,
                'applied_fields' => [
                    'fields' => array_keys($applied),
                    'label_ids' => $acceptedLabelIds,
                ],
                'status' => $status,
                'completed_at' => now(),
            ]);

            return $issue ? $updatedIssue : null;
        });
    }
}
