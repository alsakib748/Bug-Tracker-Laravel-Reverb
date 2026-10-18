<?php

namespace App\Services\Automation;

use App\Enums\AutomationActionType;
use App\Enums\AutomationConditionOperator;
use App\Enums\AutomationTrigger;

/**
 * Single source of truth for the allowlisted triggers, operators, action
 * types and condition fields. Both the rule validator and the frontend rule
 * builder read from here so the two can never drift apart.
 */
class AutomationRegistry
{
    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return [
            'triggers' => collect(AutomationTrigger::cases())->map(fn (AutomationTrigger $a) => [
                'value' => $a->value,
                'label' => $a->label(),
                'fields' => $a->allowedConditionFields(),
            ])->all(),

            'operators' => collect(AutomationConditionOperator::cases())->map(fn (AutomationConditionOperator $o) => [
                'value' => $o->value,
                'label' => $o->label(),
            ])->all(),

            'actions' => collect(AutomationActionType::cases())->map(fn (AutomationActionType $a) => [
                'value' => $a->value,
                'label' => $a->label(),
                'config' => $a->requiredConfig(),
            ])->all(),

            'fields' => $this->fieldDefinitions(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function fieldDefinitions(): array
    {
        return [
            'priority' => ['type' => 'enum', 'options' => ['low', 'medium', 'high', 'critical']],
            'status' => ['type' => 'enum', 'options' => [
                'open', 'assigned', 'in_progress', 'code_review',
                'testing', 'resolved', 'closed', 'reopened',
            ]],
            'assignee' => ['type' => 'user'],
            'reporter' => ['type' => 'user'],
            'author' => ['type' => 'user'],
            'type' => ['type' => 'enum', 'options' => ['bug', 'feature', 'improvement', 'task']],
            'labels' => ['type' => 'label-multi'],
            'label_added' => ['type' => 'label-multi'],
            'label_removed' => ['type' => 'label-multi'],
            'from_status' => ['type' => 'enum', 'options' => [
                'open', 'assigned', 'in_progress', 'code_review',
                'testing', 'resolved', 'closed', 'reopened',
            ]],
            'to_status' => ['type' => 'enum', 'options' => [
                'open', 'assigned', 'in_progress', 'code_review',
                'testing', 'resolved', 'closed', 'reopened',
            ]],
            'from_assignee' => ['type' => 'user'],
            'due_date' => ['type' => 'date'],
            'sprint_id' => ['type' => 'number'],
            'pr_state' => ['type' => 'enum', 'options' => ['open', 'closed', 'merged']],
            'pr_action' => ['type' => 'enum', 'options' => [
                'opened', 'closed', 'reopened', 'synchronize', 'edited', 'backfill',
            ]],
            'pr_author' => ['type' => 'text'],
            'pr_target_branch' => ['type' => 'text'],
            'commit_author' => ['type' => 'text'],
            'repository' => ['type' => 'text'],
        ];
    }

    public function isTriggerAllowed(string $trigger): bool
    {
        return AutomationTrigger::tryFrom($trigger) !== null;
    }

    public function isOperatorAllowed(string $operator): bool
    {
        return AutomationConditionOperator::tryFrom($operator) !== null;
    }

    public function isActionAllowed(string $action): bool
    {
        return AutomationActionType::tryFrom($action) !== null;
    }
}
