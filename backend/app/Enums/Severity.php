<?php

namespace App\Enums;

enum Severity: string
{
    case MINOR = 'minor';
    case MAJOR = 'major';
    case CRITICAL = 'critical';
    case BLOCKER = 'blocker';

    public function label(): string
    {
        return match ($this) {
            self::MINOR => 'Minor',
            self::MAJOR => 'Major',
            self::CRITICAL => 'Critical',
            self::BLOCKER => 'Blocker',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MINOR => 'gray',
            self::MAJOR => 'yellow',
            self::CRITICAL => 'orange',
            self::BLOCKER => 'red',
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
