<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with(['user', 'project', 'issue'])
            ->when($request->action, fn($q, $v) => $q->where('action', $v))
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->project_id, fn($q, $v) => $q->where('project_id', $v))
            ->when($request->issue_id, fn($q, $v) => $q->where('issue_id', $v))
            ->when($request->search, function ($q, $search) {
                $q->where('description', 'like', "%{$search}%");
            })
            ->when($request->from, fn($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->to, fn($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest();

        $logs = $query->paginate($request->get('per_page', 20));

        return ActivityLogResource::collection($logs);
    }

    public function show(ActivityLog $activityLog)
    {
        $activityLog->load(['user', 'project', 'issue']);
        return new ActivityLogResource($activityLog);
    }
}