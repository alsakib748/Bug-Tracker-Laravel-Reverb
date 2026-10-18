<?php

namespace App\Enums;

enum AutomationActionType: string
{
    case ASSIGN_ISSUE = 'assign_issue';
    case ADD_LABEL = 'add_label';
    case REMOVE_LABEL = 'remove_label';
    case CHANGE_PRIORITY = 'change_priority';
    case ADD_COMMENT = 'add_comment';
    case NOTIFY_USERS = 'notify_users';
    case SET_DUE_DATE = 'set_due_date';

    public function label(): string
    {
        return match ($this) {
            self::ASSIGN_ISSUE => 'Assign issue',
            self::ADD_LABEL => 'Add label',
            self::REMOVE_LABEL => 'Remove label',
            self::CHANGE_PRIORITY => 'Change priority',
            self::ADD_COMMENT => 'Add comment',
            self::NOTIFY_USERS => 'Notify users',
            self::SET_DUE_DATE => 'Set due date',
        };
    }

    /**
     * Required config keys per action type (allowlist).
     *
     * @return list<string>
     */
    public function requiredConfig(): array
    {
        return match ($this) {
            self::ASSIGN_ISSUE => ['user_id'],
            self::ADD_LABEL => ['label_id'],
            self::REMOVE_LABEL => ['label_id'],
            self::CHANGE_PRIORITY => ['priority'],
            self::ADD_COMMENT => ['body'],
            self::NOTIFY_USERS => ['recipients', 'message'],
            self::SET_DUE_DATE => ['offset_days'],
        };
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
