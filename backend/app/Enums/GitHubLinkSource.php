<?php

namespace App\Enums;

enum GitHubLinkSource: string
{
    case MANUAL = 'manual';
    case BRANCH = 'branch';
    case COMMIT_MESSAGE = 'commit_message';
    case PR_DESCRIPTION = 'pr_description';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::BRANCH => 'Branch name',
            self::COMMIT_MESSAGE => 'Commit message',
            self::PR_DESCRIPTION => 'PR description',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
