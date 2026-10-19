<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with(['user', 'project', 'issue'])
            ->when(!$request->user()->isAdmin(), function ($q) use ($request) {
                $q->where(function ($innerQuery) use ($request) {
                    $innerQuery->whereHas('project', function ($projectQuery) use ($request) {
                        $projectQuery->visibleTo($request->user());
                    })
                    ->orWhereHas('issue.project', function ($projectQuery) use ($request) {
                        $projectQuery->visibleTo($request->user());
                    });
                });
            })
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

    /**
     * Distinct users who appear in activity logs the current user can see.
     * Used by the activity filter dropdown. Scoped by visibility (not the
     * admin-only "view users" permission), so lower roles (e.g. Viewer) can
     * filter their own logs without gaining directory access.
     */
    public function users(Request $request)
    {
        $this->authorize('viewAny', ActivityLog::class);

        $users = User::query()
            ->whereHas('activityLogs', function ($q) use ($request) {
                $this->scopeByVisibility($q, $request);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'avatar'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
            ]);

        return response()->json(['data' => $users]);
    }

    protected function scopeByVisibility($query, Request $request)
    {
        $user = $request->user();

        if ($user === null) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($inner) use ($user) {
            $inner->whereHas('project', function ($q) use ($user) {
                $q->visibleTo($user);
            })
            ->orWhereHas('issue.project', function ($q) use ($user) {
                $q->visibleTo($user);
            });
        });
    }

    public function show(ActivityLog $activityLog)
    {
        abort_unless(
            $requestUser = request()->user(),
            403,
            'Unauthenticated.'
        );

        if (!$requestUser->isAdmin()) {
            $project = $activityLog->project ?? $activityLog->issue?->project;
            abort_unless($project && $project->isVisibleTo($requestUser), 403, 'You are not allowed to view this activity log.');
        }

        $activityLog->load(['user', 'project', 'issue']);
        return new ActivityLogResource($activityLog);
    }
}