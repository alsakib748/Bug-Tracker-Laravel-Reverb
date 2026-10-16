<?php

namespace App\Enums;

enum IssueLinkType: string
{
    case BLOCKS = 'blocks';
    case DUPLICATES = 'duplicates';
    case RELATES_TO = 'relates_to';

    /**
     * Human-readable label (from the source issue's perspective).
     */
    public function label(): string
    {
        return match ($this) {
            self::BLOCKS => 'Blocks',
            self::DUPLICATES => 'Duplicates',
            self::RELATES_TO => 'Relates to',
        };
    }

    /**
     * Label shown when reading the link from the target issue's perspective.
     */
    public function inverseLabel(): string
    {
        return match ($this) {
            self::BLOCKS => 'Blocked by',
            self::DUPLICATES => 'Duplicated by',
            self::RELATES_TO => 'Relates to',
        };
    }

    /**
     * Whether the relationship is direction-agnostic.
     */
    public function isSymmetric(): bool
    {
        return $this === self::RELATES_TO;
    }

    /**
     * Whether the relationship has a meaningful direction.
     */
    public function isDirected(): bool
    {
        return ! $this->isSymmetric();
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
