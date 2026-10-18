<?php

namespace App\Enums;

enum AutomationConditionOperator: string
{
    case EQUALS = 'equals';
    case NOT_EQUALS = 'not_equals';
    case IN = 'in';
    case NOT_IN = 'not_in';
    case CONTAINS = 'contains';
    case IS_EMPTY = 'is_empty';
    case IS_NOT_EMPTY = 'is_not_empty';
    case CHANGED = 'changed';
    case CHANGED_TO = 'changed_to';
    case GREATER_THAN = 'greater_than';
    case LESS_THAN = 'less_than';

    public function label(): string
    {
        return match ($this) {
            self::EQUALS => 'equals',
            self::NOT_EQUALS => 'does not equal',
            self::IN => 'is one of',
            self::NOT_IN => 'is not one of',
            self::CONTAINS => 'contains',
            self::IS_EMPTY => 'is empty',
            self::IS_NOT_EMPTY => 'is not empty',
            self::CHANGED => 'changed',
            self::CHANGED_TO => 'changed to',
            self::GREATER_THAN => 'greater than',
            self::LESS_THAN => 'less than',
        };
    }

    /**
     * Operators that require a comparison value.
     */
    public function requiresValue(): bool
    {
        return ! in_array($this, [
            self::IS_EMPTY,
            self::IS_NOT_EMPTY,
            self::CHANGED,
        ], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
