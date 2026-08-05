<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\Attachment;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Upload an attachment.
     */
    public function uploadAttachment(Issue $issue, User $user, UploadedFile $file): Attachment
    {
        // 1. Generate a unique stored filename
        $storedName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "issues/{$issue->id}/" . $storedName;

        // 2. Store the file
        Storage::disk('public')->put($path, file_get_contents($file));

        // 3. Save metadata using your column names
        $attachment = Attachment::create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'file_name' => $file->getClientOriginalName(),    // your column name
            'file_path' => $path,                            // your column name
            'file_size' => $file->getSize(),                 // your column name
            'mime_type' => $file->getMimeType(),
        ]);

        // 4. Log activity (using ActivityAction enum)
        $this->activityLogService->log(
            ActivityAction::ATTACHMENT_UPLOADED,
            $user,
            $issue->project,
            $issue,
            "{$user->name} uploaded attachment '{$file->getClientOriginalName()}' to issue #{$issue->id}",
            ['size' => $file->getSize(), 'mime' => $file->getMimeType()]
        );

        return $attachment;
    }

    /**
     * Get all attachments for an issue.
     */
    public function getIssueAttachments(Issue $issue)
    {
        return $issue->attachments()->with('user')->latest()->get();
    }

    /**
     * Download an attachment.
     */
    public function downloadAttachment(Attachment $attachment)
    {
        // Use your column name: file_path
        $path = storage_path('app/public/' . $attachment->file_path);

        if (!file_exists($path)) {
            throw new \Exception('File not found.');
        }

        // Use your column name: file_name for original name
        return response()->download($path, $attachment->file_name);
    }

    /**
     * Delete an attachment.
     */
    public function deleteAttachment(Attachment $attachment, User $user): void
    {
        // 1. Delete the file using your column name: file_path
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        // 2. Delete the record
        $attachment->delete();

        // 3. Log activity
        $this->activityLogService->log(
            ActivityAction::ATTACHMENT_DELETED,
            $user,
            $attachment->issue->project,
            $attachment->issue,
            "{$user->name} deleted attachment '{$attachment->file_name}' from issue #{$attachment->issue->id}",
            ['deleted_file' => $attachment->file_name]
        );
    }
}