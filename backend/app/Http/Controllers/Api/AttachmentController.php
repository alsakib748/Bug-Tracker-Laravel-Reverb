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
        $this->authorize('viewAny', [Attachment::class, $issue]);

        try {
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
        $this->authorize('create', [Attachment::class, $issue]);

        try {
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
        $this->authorize('view', $attachment);

        try {
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
        $this->authorize('delete', $attachment);

        try {
            $user = request()->user();

            $this->attachmentService->deleteAttachment($attachment, $user);

            return response()->json(['message' => 'Attachment deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Attachment delete error: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

}