<?php

namespace App\Enums;

enum IssueStatus: string
{
    case OPEN = 'open';
    case ASSIGNED = 'assigned';
    case IN_PROGRESS = 'in_progress';
    case CODE_REVIEW = 'code_review';
    case TESTING = 'testing';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
    case REOPENED = 'reopened';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::ASSIGNED => 'Assigned',
            self::IN_PROGRESS => 'In Progress',
            self::CODE_REVIEW => 'Code Review',
            self::TESTING => 'Testing',
            self::RESOLVED => 'Resolved',
            self::CLOSED => 'Closed',
            self::REOPENED => 'Reopened',
        };
    }

    /**
     * * Get the CSS class for a badge / status indicator.
     */

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'gray',
            self::ASSIGNED => 'blue',
            self::IN_PROGRESS => 'yellow',
            self::CODE_REVIEW => 'purple',
            self::TESTING => 'orange',
            self::RESOLVED => 'green',
            self::CLOSED => 'gray-600',
            self::REOPENED => 'red',
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
