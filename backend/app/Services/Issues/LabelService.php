<?php

namespace App\Services\Issues;

use App\Events\IssueLabelsUpdated;
use App\Models\Issue;
use App\Models\Label;
use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Project labels and their attachment to issues.
 */
final class LabelService
{
    public function __construct(
        private readonly ActivityLogService $activityLogs,
    ) {}

    /**
     * @return Collection<int, Label>
     */
    public function listForProject(Project $project): Collection
    {
        return $project->labels()->orderBy('name')->get();
    }

    public function create(Project $project, User $creator, array $data): Label
    {
        $normalized = Label::normalizeName($data['name']);

        if ($project->labels()->where('normalized_name', $normalized)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A label with this name already exists in the project.',
            ]);
        }

        return $project->labels()->create([
            'name' => trim($data['name']),
            'normalized_name' => $normalized,
            'color' => $data['color'] ?? '#64748b',
            'description' => $data['description'] ?? null,
            'created_by' => $creator->id,
        ]);
    }

    public function update(Label $label, array $data): Label
    {
        if (array_key_exists('name', $data) && $data['name'] !== null) {
            $normalized = Label::normalizeName($data['name']);

            $exists = Label::query()
                ->where('project_id', $label->project_id)
                ->where('normalized_name', $normalized)
                ->where('id', '!=', $label->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'name' => 'A label with this name already exists in the project.',
                ]);
            }

            $label->name = trim($data['name']);
            $label->normalized_name = $normalized;
        }

        if (array_key_exists('color', $data)) {
            $label->color = $data['color'] ?? '#64748b';
        }

        if (array_key_exists('description', $data)) {
            $label->description = $data['description'];
        }

        $label->save();

        return $label;
    }

    public function delete(Label $label): void
    {
        // issue_label rows are removed by the foreign key cascade.
        $label->delete();
    }

    /**
     * @return Collection<int, Label>
     */
    public function listForIssue(Issue $issue): Collection
    {
        return $issue->labels()->orderBy('name')->get();
    }

    /**
     * Replace the issue's labels with the given set.
     *
     * @param  array<int, int|string>  $labelIds
     * @return Collection<int, Label>
     */
    public function syncIssueLabels(Issue $issue, User $actor, array $labelIds): Collection
    {
        $labelIds = array_values(array_unique(array_map('intval', $labelIds)));

        // Only labels that belong to the issue's project may be attached.
        $validIds = Label::query()
            ->where('project_id', $issue->project_id)
            ->whereIn('id', $labelIds)
            ->pluck('id')
            ->all();

        if (count($validIds) !== count($labelIds)) {
            throw ValidationException::withMessages([
                'label_ids' => 'One or more labels do not belong to this project.',
            ]);
        }

        $before = $issue->labels()->pluck('labels.id')->all();

        DB::transaction(function () use ($issue, $actor, $validIds) {
            $sync = collect($validIds)
                ->mapWithKeys(fn ($id) => [$id => [
                    'created_by' => $actor->id,
                    'created_at' => now(),
                ]])
                ->all();

            $issue->labels()->sync($sync);
        });

        $labels = $this->listForIssue($issue);

        $added = array_values(array_diff($labels->pluck('id')->all(), $before));
        $removed = array_values(array_diff($before, $labels->pluck('id')->all()));

        if ($added !== [] || $removed !== []) {
            $this->activityLogs->logIssueLabelsChanged(
                $issue,
                $actor,
                Label::whereIn('id', $added)->pluck('name')->all(),
                Label::whereIn('id', $removed)->pluck('name')->all(),
            );
        }

        DB::afterCommit(function () use ($issue, $labels, $added, $removed, $actor) {
            event(new IssueLabelsUpdated(
                $issue,
                $labels->pluck('id')->all(),
                $added,
                $removed,
                (string) Str::uuid(),
                $actor->id,
            ));
        });

        return $labels;
    }
}
