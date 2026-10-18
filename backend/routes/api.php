<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AiBugReportController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AutomationMetadataController;
use App\Http\Controllers\Api\AutomationRuleController;
use App\Http\Controllers\Api\BacklogController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GitHubIntegrationController;
use App\Http\Controllers\Api\GitHubLinkController;
use App\Http\Controllers\Api\GitHubWebhookController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\IssueLabelController;
use App\Http\Controllers\Api\IssueLinkController;
use App\Http\Controllers\Api\IssueWatcherController;
use App\Http\Controllers\Api\IssueWorkflowController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectAnalyticsController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectInsightsController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\SprintController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// GitHub webhook — signature-authenticated (HMAC), outside auth:sanctum
Route::post('/webhooks/github', [GitHubWebhookController::class, 'handle'])
    ->middleware('throttle:120,1');

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [AuthController::class, 'user']);

    // Admin user management
    Route::apiResource('users', UserController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

    // Authenticated user profile
    Route::prefix('user')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
    });

    /**
     * *Projects Route
     */
    Route::apiResource('projects', ProjectController::class);

    /**
     * * Project Members Route
     */
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/members', [ProjectMemberController::class, 'index'])->name('project.members.index');
        Route::post('/members', [ProjectMemberController::class, 'store'])->name('project.members.store');
        Route::delete('/members/{user}', [ProjectMemberController::class, 'destroy'])->name('project.members.destroy');
        Route::get('/members/available', [ProjectMemberController::class, 'available'])->name('project.members.available');
    });

    // Issues routes
    Route::apiResource('issues', IssueController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

    // Issue workflow (status transitions + history)
    Route::prefix('issues/{issue}')->group(function () {
        Route::post('/transitions', [IssueWorkflowController::class, 'transition']);
        Route::get('/status-history', [IssueWorkflowController::class, 'history']);
        Route::get('/allowed-transitions', [IssueWorkflowController::class, 'allowedTransitions']);
    });

    // Issues Business action routes
    Route::prefix('issues/{issue}')->group(function () {
        Route::patch('/assign', [IssueController::class, 'assign']);
        Route::patch('/status', [IssueController::class, 'changeStatus']);
        Route::patch('/reopen', [IssueController::class, 'reopen']);
        Route::patch('/close', [IssueController::class, 'close']);

        Route::get('/attachments', [AttachmentController::class, 'index']);
        Route::post('/attachments', [AttachmentController::class, 'store']);
    });

    // Attachment Routes
    Route::prefix('attachments')->group(function () {
        Route::get('/{attachment}/download', [AttachmentController::class, 'download'])->name('attachments.download');
        Route::delete('/{attachment}', [AttachmentController::class, 'destroy']);
    });

    // Comments
    Route::prefix('issues/{issue}')->group(function () {
        Route::get('/comments', [CommentController::class, 'index']);
        Route::post('/comments', [CommentController::class, 'store']);
    });

    Route::prefix('comments')->group(function () {
        Route::put('/{comment}', [CommentController::class, 'update']);
        Route::delete('/{comment}', [CommentController::class, 'destroy']);
    });

    Route::prefix('comments')->group(function () {
        Route::put('/{comment}', [CommentController::class, 'update']);
        Route::delete('/{comment}', [CommentController::class, 'destroy']);
    });

    // Labels (project-scoped)
    Route::prefix('projects/{project}/labels')->group(function () {
        Route::get('/', [LabelController::class, 'index']);
        Route::post('/', [LabelController::class, 'store']);
    });

    Route::prefix('labels')->group(function () {
        Route::put('/{label}', [LabelController::class, 'update']);
        Route::delete('/{label}', [LabelController::class, 'destroy']);
    });

    // Issue labels
    Route::prefix('issues/{issue}/labels')->group(function () {
        Route::get('/', [IssueLabelController::class, 'index']);
        Route::put('/', [IssueLabelController::class, 'sync']);
        Route::post('/', [IssueLabelController::class, 'attach']);
        Route::delete('/{label}', [IssueLabelController::class, 'detach']);
    });

    // Issue watchers
    Route::prefix('issues/{issue}/watchers')->group(function () {
        Route::get('/', [IssueWatcherController::class, 'index']);
        Route::get('/me', [IssueWatcherController::class, 'status']);
        Route::post('/', [IssueWatcherController::class, 'watch']);
        Route::delete('/me', [IssueWatcherController::class, 'unwatchMe']);
    });

    // Issue links
    Route::prefix('issues/{issue}/links')->group(function () {
        Route::get('/', [IssueLinkController::class, 'index']);
        Route::post('/', [IssueLinkController::class, 'store']);
    });

    Route::delete('/issue-links/{issueLink}', [IssueLinkController::class, 'destroy']);

    // Kanban board (project-scoped)
    Route::get('/projects/{project}/board', [BoardController::class, 'index']);
    Route::patch('/issues/{issue}/board-position', [BoardController::class, 'move']);

    // Product backlog (project-scoped)
    Route::get('/projects/{project}/backlog', [BacklogController::class, 'index']);
    Route::patch('/issues/{issue}/backlog-position', [BacklogController::class, 'reorder']);

    // Sprints (project-scoped listing + sprint lifecycle)
    Route::prefix('projects/{project}/sprints')->group(function () {
        Route::get('/', [SprintController::class, 'index']);
        Route::post('/', [SprintController::class, 'store']);
    });

    Route::prefix('sprints/{sprint}')->group(function () {
        Route::get('/', [SprintController::class, 'show']);
        Route::put('/', [SprintController::class, 'update']);
        Route::post('/issues', [SprintController::class, 'addIssues']);
        Route::delete('/issues/{issue}', [SprintController::class, 'removeIssue']);
        Route::post('/start', [SprintController::class, 'start']);
        Route::post('/complete', [SprintController::class, 'complete']);
        Route::post('/cancel', [SprintController::class, 'cancel']);
        Route::get('/report', [SprintController::class, 'report']);
    });

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // AI bug report assistant (project-scoped analysis + review workflow)
    Route::prefix('projects/{project}/ai')->group(function () {
        Route::post('/bug-report-analyses', [AiBugReportController::class, 'analyze'])
            ->middleware('throttle:20,1');
    });

    Route::prefix('ai/bug-report-analyses')->group(function () {
        Route::get('/{analysis}', [AiBugReportController::class, 'show']);
        Route::post('/{analysis}/apply', [AiBugReportController::class, 'apply']);
        Route::post('/{analysis}/dismiss', [AiBugReportController::class, 'dismiss']);
    });

    //  Automation rules (project-scoped listing + creation)
    Route::get('/automation-options', AutomationMetadataController::class);

    Route::prefix('projects/{project}')->group(function () {
        Route::get('/automation-rules', [AutomationRuleController::class, 'index']);
        Route::post('/automation-rules', [AutomationRuleController::class, 'store']);
        Route::get('/automation-executions', [AutomationRuleController::class, 'executions']);
    });

    Route::prefix('automation-rules/{rule}')->group(function () {
        Route::get('/', [AutomationRuleController::class, 'show']);
        Route::put('/', [AutomationRuleController::class, 'update']);
        Route::post('/enable', [AutomationRuleController::class, 'enable']);
        Route::post('/disable', [AutomationRuleController::class, 'disable']);
        Route::delete('/', [AutomationRuleController::class, 'destroy']);
    });

    // Analytics (deterministic metrics) + AI-assisted insights (project-scoped)
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/analytics/overview', [ProjectAnalyticsController::class, 'overview']);
        Route::get('/analytics/trends', [ProjectAnalyticsController::class, 'trends']);
        Route::get('/analytics/workflow', [ProjectAnalyticsController::class, 'workflow']);
        Route::get('/analytics/github', [ProjectAnalyticsController::class, 'github']);

        Route::get('/insights', [ProjectInsightsController::class, 'index']);
        Route::post('/insights', [ProjectInsightsController::class, 'store']);
    });

    Route::get('/insights/{insight}', [ProjectInsightsController::class, 'show']);

    //  Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    //  Activity-Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show']);

    //  GitHub integration (project-scoped repository connection)
    Route::prefix('projects/{project}/integrations/github')->group(function () {
        Route::get('/status', [GitHubIntegrationController::class, 'status']);
        Route::post('/connect', [GitHubIntegrationController::class, 'connect']);
        Route::get('/repositories', [GitHubIntegrationController::class, 'repositories']);
        Route::post('/repositories', [GitHubIntegrationController::class, 'linkRepository']);
        Route::post('/repositories/{repository}/sync', [GitHubIntegrationController::class, 'syncRepository']);
        Route::delete('/repositories/{repository}', [GitHubIntegrationController::class, 'unlinkRepository']);
    });

    // GitHub issue links (manual PR/commit linking on an issue)
    Route::prefix('issues/{issue}/github-links')->group(function () {
        Route::get('/', [GitHubLinkController::class, 'index']);
        Route::post('/', [GitHubLinkController::class, 'store']);
        Route::get('/candidates', [GitHubLinkController::class, 'candidates']);
        Route::delete('/{link}', [GitHubLinkController::class, 'destroy']);
    });

});
