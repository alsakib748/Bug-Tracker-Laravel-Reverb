<?php

namespace App\Enums;

enum SprintStatus: string
{
    case PLANNED = 'planned';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED => 'Planned',
            self::ACTIVE => 'Active',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function canBeEdited(): bool
    {
        return $this === self::PLANNED;
    }

    public function canBeStarted(): bool
    {
        return $this === self::PLANNED;
    }

    public function canBeCompleted(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Options for select inputs: [ ['value' => ..., 'label' => ...], ... ]
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
