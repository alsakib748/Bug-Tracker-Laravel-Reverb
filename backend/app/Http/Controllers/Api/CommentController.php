<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Issue;
use App\Services\CommentService;
use Illuminate\Http\Request;

class CommentController extends Controller
{

    protected CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    public function index(Issue $issue)
    {
        // $this->authorize('view', $issue);

        $comments = $this->commentService->getCommentByIssue($issue);

        return CommentResource::collection($comments);

    }

    public function store(StoreCommentRequest $request, Issue $issue)
    {
        // $this->authorize('view', $issue);

        try {
            $comment = $this->commentService->createComment(
                $issue,
                $request->user(),
                $request->comment
            );
            return new CommentResource($comment);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

    }

    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        // $this->authorize('update', $comment);

        $updated = $this->commentService->updateComment($comment, $request->comment);

        return new CommentResource($updated);

    }

    public function destroy(Comment $comment)
    {
        // $this->authorize('delete', $comment);
        $this->commentService->deleteComment($comment);

        return response()->json(['message' => 'Comment deleted successfully.']);
    }

}