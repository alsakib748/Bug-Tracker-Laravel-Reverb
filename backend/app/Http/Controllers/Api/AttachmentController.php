<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Models\Attachment;
use App\Models\Issue;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{

    protected AttachmentService $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * List attachments for an issue.
     */
    public function index(Issue $issue)
    {
        // $this->authorize('view', $issue);

        try {
            // $this->authorize('view', $issue);
            $attachments = $this->attachmentService->getIssueAttachments($issue);
            return AttachmentResource::collection($attachments);
        } catch (\Exception $e) {
            Log::error('Attachment list error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Upload a new attachment.
     */
    public function store(UploadAttachmentRequest $request, Issue $issue)
    {
        // $this->authorize('view', $issue);

        try {
            // $this->authorize('view', $issue);

            // Check project membership
            if (!$issue->project->members()->where('user_id', $request->user()->id)->exists()) {
                return response()->json(['message' => 'You are not a member of this project.'], 403);
            }

            $attachment = $this->attachmentService->uploadAttachment(
                $issue,
                $request->user(),
                $request->file('file')
            );

            return new AttachmentResource($attachment);
        } catch (\Exception $e) {
            Log::error('Attachment upload error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Download an attachment.
     */
    public function download(Attachment $attachment)
    {
        try {
            $issue = $attachment->issue;
            // $this->authorize('view', $issue);
            return $this->attachmentService->downloadAttachment($attachment);
        } catch (\Exception $e) {
            Log::error('Attachment download error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete an attachment.
     */
    public function destroy(Attachment $attachment)
    {
        try {
            $user = request()->user();

            // Permissions: uploader or admin
            if (!$user->isAdmin() && $user->id !== $attachment->user_id) {
                return response()->json(['message' => 'You are not allowed to delete this attachment.'], 403);
            }

            $this->attachmentService->deleteAttachment($attachment, $user);

            return response()->json(['message' => 'Attachment deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Attachment delete error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

}