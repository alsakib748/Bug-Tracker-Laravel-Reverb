<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectMemberController;
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
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

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

    Route::get('/dashboard', [DashboardController::class, 'index']);

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

});