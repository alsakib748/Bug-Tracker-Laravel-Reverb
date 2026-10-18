<?php

namespace App\Services\Automation\Actions;

use App\Models\Issue;
use App\Services\CommentService;

class AddCommentAction
{
    public function __construct(private readonly CommentService $commentService) {}

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function execute(array $config, array $context): array
    {
        $issue = Issue::findOrFail($context['issue']['id']);
        $body = (string) $config['body'];
        $actor = auth()->user();

        $this->commentService->createComment($issue, $actor, '[Automation] '.$body);

        return ['comment_body' => $body];
    }
}
