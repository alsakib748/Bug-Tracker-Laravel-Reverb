<?php

namespace App\Enums;

enum GitHubEntityType: string
{
    case PULL_REQUEST = 'pull_request';
    case COMMIT = 'commit';
    case BRANCH = 'branch';

    public function label(): string
    {
        return match ($this) {
            self::PULL_REQUEST => 'Pull Request',
            self::COMMIT => 'Commit',
            self::BRANCH => 'Branch',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PULL_REQUEST => 'pi pi-github',
            self::COMMIT => 'pi pi-code',
            self::BRANCH => 'pi pi-directions',
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
