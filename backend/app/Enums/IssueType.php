<?php

namespace App\Enums;

enum IssueType: string
{
    case BUG = 'bug';
    case FEATURE = 'feature';
    case IMPROVEMENT = 'improvement';
    case TASK = 'task';

    public function label(): string
    {
        return match ($this) {
            self::BUG => 'Bug',
            self::FEATURE => 'Feature',
            self::IMPROVEMENT => 'Improvement',
            self::TASK => 'Task',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::BUG => 'bug',
            self::FEATURE => 'star',
            self::IMPROVEMENT => 'arrow-up',
            self::TASK => 'check',
        };
    }

    public static function options(): array
    {
        return array_reduce(self::cases(), function ($carry, $case) {
            $carry[$case->value] = $case->label();
            return $carry;
        }, []);
    }
}
