<?php

namespace App\Services\Automation\Actions;

use App\Enums\AutomationActionType;
use App\Models\Issue;
use App\Models\Label;
use App\Services\Issues\LabelService;

class ManageLabelAction
{
    public function __construct(private readonly LabelService $labelService) {}

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $issue = Issue::findOrFail($context['issue']['id']);
        $labelId = (int) $config['label_id'];
        $isAdd = ($config['_action_type'] ?? AutomationActionType::ADD_LABEL->value) === AutomationActionType::ADD_LABEL->value;

        $label = Label::findOrFail($labelId);

        $current = $issue->labels()->pluck('labels.id')->map(fn ($id) => (int) $id)->all();

        if ($isAdd) {
            if (in_array($labelId, $current, true)) {
                return ['skipped' => 'label_already_present'];
            }

            $next = array_values(array_unique([...$current, $labelId]));
        } else {
            if (! in_array($labelId, $current, true)) {
                return ['skipped' => 'label_not_present'];
            }

            $next = array_values(array_diff($current, [$labelId]));
        }

        $actor = auth()->user();

        $this->labelService->syncIssueLabels($issue, $actor, $next);

        return $isAdd
            ? ['added_label_id' => $label->id]
            : ['removed_label_id' => $label->id];
    }
}
