<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List all notifications for the authenticated user.
     */
    public function index(Request $request)
    {
        try {
            $notifications = $request->user()
                ->notifications()
                ->latest()
                ->paginate(15);

            $data = $notifications->getCollection()->map(function ($notification) {
                return (new NotificationResource($notification))->toArray(request());
            })->values();

            return response()->json([
                'current_page' => $notifications->currentPage(),
                'data' => $data,
                'first_page_url' => $notifications->url(1),
                'from' => $notifications->firstItem(),
                'last_page' => $notifications->lastPage(),
                'last_page_url' => $notifications->url($notifications->lastPage()),
                'links' => $notifications->linkCollection(),
                'next_page_url' => $notifications->nextPageUrl(),
                'path' => $notifications->path(),
                'per_page' => $notifications->perPage(),
                'prev_page_url' => $notifications->previousPageUrl(),
                'to' => $notifications->lastItem(),
                'total' => $notifications->total(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Notification error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        $request->user()
            ->unreadNotifications
            ->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * Delete a notification (optional).
     */
    public function destroy(Request $request, $id)
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }

}
