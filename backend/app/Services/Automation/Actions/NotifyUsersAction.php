<?php

namespace App\Services\Automation\Actions;

use App\Events\NotificationCreated;
use App\Models\Issue;
use App\Models\User;
use App\Notifications\SystemNotification;

class NotifyUsersAction
{
    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $issue = Issue::findOrFail($context['issue']['id']);
        $recipients = User::whereIn('id', array_map('intval', (array) $config['recipients']))->get();

        foreach ($recipients as $user) {
            $user->notify(new SystemNotification('automation_triggered', [
                'issue_id' => $issue->id,
                'project_id' => $issue->project_id,
                'title' => $issue->title,
                'message' => $config['message'],
                'url' => '/issues/'.$issue->id,
            ]));

            $notification = $user->notifications()->latest('created_at')->first();

            if ($notification) {
                event(new NotificationCreated($notification));
            }
        }

        return ['notified' => $recipients->count()];
    }
}
