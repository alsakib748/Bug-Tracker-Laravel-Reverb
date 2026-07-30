<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case DEVELOPER = 'developer';
    case TESTER = 'tester';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::DEVELOPER => 'Developer',
            self::TESTER => 'Tester',
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