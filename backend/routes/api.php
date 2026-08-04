<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

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

});
