<?php

namespace App\Enums;

enum ActivityAction: string
{
    case PROJECT_CREATED = 'project_created';
    case PROJECT_UPDATED = 'project_updated';
    case PROJECT_DELETED = 'project_deleted';

    case MEMBER_ADDED = 'member_added';
    case MEMBER_REMOVED = 'member_removed';

    case ISSUE_CREATED = 'issue_created';
    case ISSUE_UPDATED = 'issue_updated';
    case ISSUE_ASSIGNED = 'issue_assigned';
    case ISSUE_REASSIGNED = 'issue_reassigned';
    case ISSUE_STATUS_CHANGED = 'issue_status_changed';
    case ISSUE_CLOSED = 'issue_closed';
    case ISSUE_REOPENED = 'issue_reopened';

    case COMMENT_ADDED = 'comment_added';
    case COMMENT_UPDATED = 'comment_updated';
    case COMMENT_DELETED = 'comment_deleted';

    case ATTACHMENT_UPLOADED = 'attachment_uploaded';
    case ATTACHMENT_DELETED = 'attachment_deleted';

    case USER_LOGIN = 'user_login';
    case USER_LOGOUT = 'user_logout';

    public function label(): string
    {
        return match ($this) {
            self::PROJECT_CREATED => 'Project Created',
            self::PROJECT_UPDATED => 'Project Updated',
            self::PROJECT_DELETED => 'Project Deleted',
            self::MEMBER_ADDED => 'Member Added',
            self::MEMBER_REMOVED => 'Member Removed',
            self::ISSUE_CREATED => 'Issue Created',
            self::ISSUE_UPDATED => 'Issue Updated',
            self::ISSUE_ASSIGNED => 'Issue Assigned',
            self::ISSUE_REASSIGNED => 'Issue Reassigned',
            self::ISSUE_STATUS_CHANGED => 'Issue Status Changed',
            self::ISSUE_CLOSED => 'Issue Closed',
            self::ISSUE_REOPENED => 'Issue Reopened',
            self::COMMENT_ADDED => 'Comment Added',
            self::COMMENT_UPDATED => 'Comment Updated',
            self::COMMENT_DELETED => 'Comment Deleted',
            self::ATTACHMENT_UPLOADED => 'Attachment Uploaded',
            self::ATTACHMENT_DELETED => 'Attachment Deleted',
            self::USER_LOGIN => 'User Login',
            self::USER_LOGOUT => 'User Logout',
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}