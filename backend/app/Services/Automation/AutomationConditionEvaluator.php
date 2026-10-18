<?php

namespace App\Services\Automation;

use App\Enums\AutomationConditionOperator;

/**
 * Pure, deterministic evaluation of a rule's conditions against an event
 * context. No side effects, no I/O.
 */
class AutomationConditionEvaluator
{
    /**
     * @param  array<string, mixed>  $conditions
     * @param  array<string, mixed>  $context
     * @return array{matched: bool, results: array<int, array<string, mixed>>, failed_conditions: array}
     */
    public function evaluate(array $conditions, array $context): array
    {
        $match = $conditions['match'] ?? 'all';
        $rules = $conditions['rules'] ?? [];

        if ($rules === []) {
            return ['matched' => true, 'results' => [], 'failed_conditions' => []];
        }

        $results = [];

        foreach ($rules as $rule) {
            $passed = $this->evaluateOne($rule, $context);

            $results[] = [
                'field' => $rule['field'],
                'operator' => $rule['operator'],
                'expected' => $rule['value'] ?? null,
                'passed' => $passed,
            ];
        }

        $matched = $match === 'all'
            ? collect($results)->every(fn (array $r) => $r['passed'])
            : collect($results)->contains(fn (array $r) => $r['passed']);

        return [
            'matched' => $matched,
            'failed_conditions' => array_values(array_filter($results, fn (array $r) => ! $r['passed'])),
            'evaluated' => $results,
        ];
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $context
     */
    protected function evaluateOne(array $rule, array $context): bool
    {
        $field = $rule['field'];
        $operator = AutomationConditionOperator::tryFrom($rule['operator']);
        $value = $rule['value'] ?? null;

        $actual = data_get($context, $field);
        $before = data_get($context, "before.$field");

        return match ($operator) {
            AutomationConditionOperator::EQUALS => $actual == $value,
            AutomationConditionOperator::NOT_EQUALS => $actual != $value,
            AutomationConditionOperator::IN => is_array($value) && in_array($actual, $value, true),
            AutomationConditionOperator::NOT_IN => is_array($value) && ! in_array($actual, $value, true),
            AutomationConditionOperator::CONTAINS => is_array($actual) && in_array($value, $actual, true),
            AutomationConditionOperator::IS_EMPTY => blank($actual),
            AutomationConditionOperator::IS_NOT_EMPTY => filled($actual),
            AutomationConditionOperator::CHANGED => $actual != $before,
            AutomationConditionOperator::CHANGED_TO => $actual == $value && $actual != $before,
            AutomationConditionOperator::GREATER_THAN => is_numeric($actual) && $actual > $value,
            AutomationConditionOperator::LESS_THAN => is_numeric($actual) && $actual < $value,
            default => false,
        };
    }
}
