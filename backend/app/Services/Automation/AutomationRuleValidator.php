<?php

namespace App\Services\Automation;

use App\Enums\AutomationActionType;
use App\Enums\AutomationConditionOperator;
use App\Enums\AutomationTrigger;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

/**
 * Validates a rule definition against the allowlist and normalizes it before
 * it is persisted. Nothing that is not explicitly allowed may be stored.
 */
class AutomationRuleValidator
{
    public function __construct(private readonly AutomationRegistry $registry) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{trigger_type: string, conditions: array, actions: array}
     *
     * @throws ValidationException
     */
    public function validate(Project $project, array $data): array
    {
        $trigger = AutomationTrigger::tryFrom($data['trigger_type'] ?? '');

        if (! $trigger) {
            throw ValidationException::withMessages([
                'trigger_type' => 'Unknown trigger.',
            ]);
        }

        return [
            'trigger_type' => $trigger->value,
            'conditions' => $this->validateConditions($data['conditions'] ?? [], $trigger),
            'actions' => $this->validateActions($data['actions'] ?? [], $project),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{match: string, rules: array<int, array<string, mixed>>}
     */
    protected function validateConditions(array $config, AutomationTrigger $trigger): array
    {
        $match = $config['match'] ?? 'all';

        if (! in_array($match, ['all', 'any'], true)) {
            throw ValidationException::withMessages([
                'conditions.match' => 'Match must be "all" or "any".',
            ]);
        }

        $rules = $config['rules'] ?? [];

        if (! is_array($rules) || count($rules) > 20) {
            throw ValidationException::withMessages([
                'conditions.rules' => 'At most 20 conditions are allowed.',
            ]);
        }

        $allowedFields = $trigger->allowedConditionFields();
        $normalized = [];

        foreach ($rules as $i => $rule) {
            $field = $rule['field'] ?? null;
            $operator = $rule['operator'] ?? null;

            if (! $field || ! in_array($field, $allowedFields, true)) {
                throw ValidationException::withMessages([
                    "conditions.rules.$i.field" => "Field '{$field}' is not allowed for this trigger.",
                ]);
            }

            if (! $operator || ! AutomationConditionOperator::tryFrom($operator)) {
                throw ValidationException::withMessages([
                    "conditions.rules.$i.operator" => "Unknown operator '{$operator}'.",
                ]);
            }

            $normalized[] = [
                'field' => $field,
                'operator' => $operator,
                'value' => $rule['value'] ?? null,
            ];
        }

        return ['match' => $config['match'] ?? 'all', 'rules' => $normalized];
    }

    /**
     * @param  array<int, array<string, mixed>>  $actions
     * @return array<int, array<string, mixed>>
     *
     * @throws ValidationException
     */
    protected function validateActions(array $actions, Project $project): array
    {
        if (! is_array($actions) || count($actions) === 0) {
            throw ValidationException::withMessages([
                'actions' => 'At least one action is required.',
            ]);
        }

        if (count($actions) > 10) {
            throw ValidationException::withMessages([
                'actions' => 'At most 10 actions are allowed.',
            ]);
        }

        $validLabelIds = $project->labels()->pluck('id')->all();
        $validUserIds = $project->members()->pluck('users.id')->all();

        $normalized = [];

        foreach ($actions as $i => $action) {
            $type = AutomationActionType::tryFrom($action['type'] ?? '');

            if (! $type) {
                throw ValidationException::withMessages([
                    "actions.$i.type" => "Unknown action '{$action['type']}'.",
                ]);
            }

            $config = $action['config'] ?? [];

            foreach ($type->requiredConfig() as $key) {
                if (! array_key_exists($key, $config) || $config[$key] === null || $config[$key] === '') {
                    throw ValidationException::withMessages([
                        "actions.$i.config.$key" => "The {$key} field is required for this action.",
                    ]);
                }
            }

            if (in_array($type, [AutomationActionType::ADD_LABEL, AutomationActionType::REMOVE_LABEL], true)) {
                if (! in_array((int) $config['label_id'], $validLabelIds, true)) {
                    throw ValidationException::withMessages([
                        "actions.$i.config.label_id" => 'The selected label does not belong to this project.',
                    ]);
                }
            }

            if ($type === AutomationActionType::ASSIGN_ISSUE) {
                if (! in_array((int) $config['user_id'], $validUserIds, true)) {
                    throw ValidationException::withMessages([
                        "actions.$i.config.user_id" => 'The selected user is not a member of this project.',
                    ]);
                }
            }

            if ($type === AutomationActionType::NOTIFY_USERS) {
                $recipients = array_map('intval', (array) $config['recipients']);

                if ($recipients === []) {
                    throw ValidationException::withMessages([
                        "actions.$i.config.recipients" => 'At least one recipient is required.',
                    ]);
                }

                foreach ($recipients as $recipient) {
                    if (! in_array($recipient, $validUserIds, true)) {
                        throw ValidationException::withMessages([
                            "actions.$i.config.recipients" => 'One or more recipients are not members of this project.',
                        ]);
                    }
                }
            }

            if ($type === AutomationActionType::CHANGE_PRIORITY) {
                if (! in_array($config['priority'], ['low', 'medium', 'high', 'critical'], true)) {
                    throw ValidationException::withMessages([
                        "actions.$i.config.priority" => 'Unknown priority value.',
                    ]);
                }
            }

            if ($type === AutomationActionType::SET_DUE_DATE) {
                if (! is_numeric($config['offset_days']) || (int) $config['offset_days'] < 0 || (int) $config['offset_days'] > 3650) {
                    throw ValidationException::withMessages([
                        "actions.$i.config.offset_days" => 'Offset days must be a number between 0 and 3650.',
                    ]);
                }
            }

            $normalized[] = [
                'type' => $type->value,
                'config' => $config,
            ];
        }

        return $normalized;
    }
}
