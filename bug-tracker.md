Backend:::

1.Route List::

 
sakib@DESKTOP-K61E1LT MINGW64 /e/Issue Bug Tracker/backend (main)
$ php artisan r:l

  GET|HEAD        / ......................................... routes/web.php:5
  GET|HEAD        api/activity-logs .......... Api\ActivityLogController@index
  GET|HEAD        api/activity-logs/{activityLog} Api\ActivityLogController@s…
  DELETE          api/attachments/{attachment} Api\AttachmentController@destr…
  GET|HEAD        api/attachments/{attachment}/download attachments.download …
  GET|POST|HEAD   api/broadcasting/auth Illuminate\Broadcasting › BroadcastCo…
  PUT             api/comments/{comment} ........ Api\CommentController@update
  DELETE          api/comments/{comment} ....... Api\CommentController@destroy
  GET|HEAD        api/dashboard ................ Api\DashboardController@index
  GET|HEAD        api/issues ........ issues.index › Api\IssueController@index
  POST            api/issues ........ issues.store › Api\IssueController@store
  GET|HEAD        api/issues/{issue} .. issues.show › Api\IssueController@show
  PUT|PATCH       api/issues/{issue} issues.update › Api\IssueController@upda…
  DELETE          api/issues/{issue} issues.destroy › Api\IssueController@des…
  PATCH           api/issues/{issue}/assign ....... Api\IssueController@assign
  GET|HEAD        api/issues/{issue}/attachments Api\AttachmentController@ind…
  POST            api/issues/{issue}/attachments Api\AttachmentController@sto…
  PATCH           api/issues/{issue}/close ......... Api\IssueController@close
  GET|HEAD        api/issues/{issue}/comments .... Api\CommentController@index
  POST            api/issues/{issue}/comments .... Api\CommentController@store
  PATCH           api/issues/{issue}/reopen ....... Api\IssueController@reopen
  PATCH           api/issues/{issue}/status . Api\IssueController@changeStatus
  GET|HEAD        api/login ........................ login › routes/api.php:21
  POST            api/login ......................... Api\AuthController@login
  POST            api/logout ....................... Api\AuthController@logout
  GET|HEAD        api/notifications ......... Api\NotificationController@index
  PATCH           api/notifications/read-all Api\NotificationController@markA…
  DELETE          api/notifications/{id} .. Api\NotificationController@destroy
  PATCH           api/notifications/{id}/read Api\NotificationController@mark…
  GET|HEAD        api/projects .. projects.index › Api\ProjectController@index
  POST            api/projects .. projects.store › Api\ProjectController@store
  GET|HEAD        api/projects/{project} projects.show › Api\ProjectControlle…
  PUT|PATCH       api/projects/{project} projects.update › Api\ProjectControl…
  DELETE          api/projects/{project} projects.destroy › Api\ProjectContro…
  GET|HEAD        api/projects/{project}/members project.members.index › Api\…
  POST            api/projects/{project}/members project.members.store › Api\…
  GET|HEAD        api/projects/{project}/members/available project.members.av…
  DELETE          api/projects/{project}/members/{user} project.members.destr…
  GET|HEAD        api/user ........................... Api\AuthController@user
  GET|HEAD        api/user/profile ................ Api\ProfileController@show
  PUT             api/user/profile .............. Api\ProfileController@update
  GET|HEAD        api/users ........... users.index › Api\UserController@index
  POST            api/users ........... users.store › Api\UserController@store
  GET|HEAD        api/users/{user} ...... users.show › Api\UserController@show
  PUT|PATCH       api/users/{user} .. users.update › Api\UserController@update
  DELETE          api/users/{user} users.destroy › Api\UserController@destroy
  GET|POST|HEAD   broadcasting/auth Illuminate\Broadcasting › BroadcastContro…
  GET|HEAD        sanctum/csrf-cookie sanctum.csrf-cookie › Laravel\Sanctum  …
  GET|HEAD        storage/{path} storage.local › vendor/laravel/framework/src…
  PUT             storage/{path} storage.local.upload › vendor/laravel/framew…
  GET|HEAD        test-broadcast ............................ routes/web.php:9
  GET|HEAD        up vendor/laravel/framework/src/Illuminate/Foundation/Confi…

                                                           Showing [52] routes


2. Backend .env::
   APP_NAME="Bug Tracker"
APP_ENV=local
APP_KEY=base64:ZBf6zQXiyXnaQKh4uMb/NTCAkYGBAP439/98KrHS42o=
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
# APP_MAINTENANCE_STORE=database

# PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bug_tracker
DB_USERNAME=root
DB_PASSWORD=

FRONTEND_URL=http://localhost:5173


SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

SANCTUM_STATEFUL_DOMAINS=localhost:5173

BROADCAST_CONNECTION=reverb

FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
# CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

REVERB_APP_ID=309103
REVERB_APP_KEY=pdhydnsdcbmqawzt5zse
REVERB_APP_SECRET=gk8mdtukpamkuuo3oyuo
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

                                                           

3. cors.php::
   <?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'broadcasting/*', 'api/broadcasting/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:8000',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];

3. bootstrap/app.php::
   <?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__ . '/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        // 🔥 Exclude broadcasting auth from CSRF validation
        $middleware->validateCsrfTokens(except: [
            'api/broadcasting/auth',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );
    })->create();

4. bootstrap/providers.php
  <?php

use App\Providers\AppServiceProvider;
use Illuminate\Broadcasting\BroadcastServiceProvider;


return [
    AppServiceProvider::class,
    BroadcastServiceProvider::class,
];

5. channels.php::

<?php

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;


// Private user channel: only the user themselves can listen.
// Laravel's broadcaster passes wildcards as strings, so cast to int.
Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return (int) $user->id === (int) $userId;
});

// Private project channel: only project members
Broadcast::channel('project.{projectId}', function (User $user, string $projectId) {
    $project = Project::find((int) $projectId);
    if (!$project) {
        return false;
    }
    return $project->members()->where('users.id', $user->id)->exists();
});

// Private issue channel: any authenticated user can subscribe (mirrors
// the controller-level access in IssueController::show, which doesn't
// restrict by project membership). Tighten this when policies are added.
Broadcast::channel('issue.{issueId}', function (User $user, string $issueId) {
    return Issue::where('id', (int) $issueId)->exists();
});


6. api.php::
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

8. IssueService::
<?php

namespace App\Services;

use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Enums\Priority;
use App\Enums\Severity;
use App\Events\IssueAssigned;
use App\Events\IssueCreated;
use App\Events\IssueStatusChanged;
use App\Events\IssueUpdated;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Pagination\LengthAwarePaginator;

class IssueService
{

    protected NotificationService $notificationService;
    protected ActivityLogService $activityLogService;

    public function __construct(NotificationService $notificationService, ActivityLogService $activityLogService)
    {
        $this->notificationService = $notificationService;
        $this->activityLogService = $activityLogService;
    }

    public function getAllIssues(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Issue::query()
            ->with(['project', 'reporter', 'assignee', 'comments', 'attachments'])
            ->when($filters['project_id'] ?? null, fn($q, $v) => $q->where('project_id', $v))
            ->when($filters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['priority'] ?? null, fn($q, $v) => $q->where('priority', $v))
            ->when($filters['severity'] ?? null, fn($q, $v) => $q->where('severity', $v))
            ->when($filters['type'] ?? null, fn($q, $v) => $q->where('type', $v))
            ->when($filters['assigned_to'] ?? null, fn($q, $v) => $q->where('assigned_to', $v))
            ->when($filters['reporter_id'] ?? null, fn($q, $v) => $q->where('reporter_id', $v))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($filters['sort'] ?? null, function ($q, $sort) {
                $direction = 'asc';
                if (str_starts_with($sort, '-')) {
                    $direction = 'desc';
                    $sort = substr($sort, 1);
                }
                $q->orderBy($sort, $direction);
            }, fn($q) => $q->orderBy('id', 'desc'));

        return $query->paginate($perPage);

    }

    public function getIssueById(int $id): Issue
    {
        return Issue::with(['project', 'reporter', 'assignee', 'comments.user', 'attachments'])
            ->findOrFail($id);
    }

    public function createIssue(array $data): Issue
    {

        $project = Project::findOrFail($data['project_id']);
        $reporter = User::findOrFail($data['reporter_id']);

        $issue = Issue::create([
            'project_id' => $data['project_id'],
            'reporter_id' => $data['reporter_id'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? Priority::MEDIUM->value,
            'severity' => $data['severity'] ?? Severity::MAJOR->value,
            'status' => IssueStatus::OPEN->value,
            'type' => $data['type'] ?? IssueType::BUG->value,
            'due_date' => $data['due_data'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
        ]);

        event(new IssueCreated($issue));

        $this->notificationService->sendIssueCreated($issue, $reporter);
        $this->activityLogService->logIssueCreated($issue, auth()->user());
        // Refresh with relations

        return $issue->fresh(['project', 'reporter', 'assignee']);

    }

    public function updateIssue(Issue $issue, array $data): Issue
    {

        $old = $issue->getOriginal();

        $issue->update([
            'title' => $data['title'] ?? $issue->title,
            'description' => $data['description'] ?? $issue->description,
            'priority' => $data['priority'] ?? $issue->priority,
            'severity' => $data['severity'] ?? $issue->severity,
            'type' => $data['type'] ?? $issue->type,
            'due_date' => $data['due_date'] ?? $issue->due_date,
            'estimated_hours' => $data['estimated_hours'] ?? $issue->estimated_hours,
        ]);

        event(new IssueUpdated($issue->fresh()));

        $this->activityLogService->logIssueUpdated($issue, auth()->user(), $old, $issue->getAttributes());

        return $issue->fresh(['project', 'reporter', 'assignee']);

    }

    public function deleteIssue(Issue $issue): void
    {
        $issue->delete();
    }

    // -------------------- Business Actions -------------------
    /**
     * Assign (or reassign) a developer to an issue.
     * If issue is open, transition to assigned.
     */
    public function assignIssue(Issue $issue, int $userId): Issue
    {
        $user = User::findOrFail($userId);

        if (!$issue->project->members()->where('user_id', $userId)->exists()) {
            throw new \Exception('User is not a member of this project.');
        }

        $issue->assigned_to = $userId;

        // If status if 'open', change to 'assigned'
        if ($issue->status === IssueStatus::OPEN->value) {
            $issue->status = IssueStatus::ASSIGNED;
        }

        // Send notification
        $assignee = User::find($userId);
        $notificationService = app(NotificationService::class);
        $notificationService->sendIssueAssigned($issue, $assignee, auth()->user());

        $issue->save();

        $issue->refresh();

        event(new IssueAssigned(
            $issue->load(['project', 'reporter', 'assignee']),
            $user->id
        ));

        event(new IssueUpdated(
            $issue->load(['project', 'reporter', 'assignee'])
        ));

        $this->activityLogService->logIssueAssigned($issue, auth()->user(), $assignee);

        $this->notificationService->sendIssueAssigned(
            $issue,
            $assignee,
            auth()->user()
        );

        return $issue->fresh(['project', 'reporter', 'assignee']);

    }

    /**
     * Change status of an issue following the workflow.
     * Allowed transitions:
     *   - open → assigned (only if assigned_to is set)
     *   - assigned → in_progress
     *   - in_progress → code_review
     *   - code_review → testing
     *   - testing → resolved
     *   - resolved → closed
     *   - testing → reopened
     *   - reopened → in_progress
     *
     * Also allow any status from any? For now, we enforce the workflow.
     */
    public function changeStatus(Issue $issue, string $newStatus): Issue
    {
        $oldStatus = $issue->status;
        $current = IssueStatus::tryFrom($issue->status);
        $new = IssueStatus::tryFrom($newStatus);

        if (!$current || !$new) {
            throw new \Exception('Invalid status value.');
        }

        // Define allowed transitions
        $transitions = [
            IssueStatus::OPEN->value => [IssueStatus::ASSIGNED->value],
            IssueStatus::ASSIGNED->value => [IssueStatus::IN_PROGRESS->value],
            IssueStatus::IN_PROGRESS->value => [IssueStatus::CODE_REVIEW->value],
            IssueStatus::CODE_REVIEW->value => [IssueStatus::TESTING->value],
            IssueStatus::TESTING->value => [IssueStatus::RESOLVED->value, IssueStatus::REOPENED->value],
            IssueStatus::RESOLVED->value => [IssueStatus::CLOSED->value, IssueStatus::REOPENED->value],
            IssueStatus::REOPENED->value => [IssueStatus::IN_PROGRESS->value],
            IssueStatus::CLOSED->value => [], // closed is final
        ];

        if (!in_array($newStatus, $transitions[$current->value] ?? [])) {
            throw new \Exception("Cannot transition from {$current->label()} to {$new->label()}.");
        }

        // Additional validation:
        // - Cannot assign to 'assigned' if no assignee? We'll allow, but assignIssue handles that.
        if ($newStatus === IssueStatus::ASSIGNED->value && !$issue->assigned_to) {
            throw new \Exception('Cannot set status to "Assigned" without an assignee.');
        }

        $issue->status = $new;

        // If closing, set completed_at
        if ($new === IssueStatus::CLOSED) {
            $issue->completed_at = \Illuminate\Support\Carbon::now();
        }

        $issue->save();


        event(new IssueStatusChanged($issue->fresh(), $oldStatus, $newStatus));

        $this->notificationService->sendIssueStatusChanged($issue, $current->value, $new->value, auth()->user());

        $this->activityLogService->logIssueStatusChanged($issue, auth()->user(), $oldStatus, $newStatus);

        return $issue->fresh(['project', 'reporter', 'assignee']);

    }

    /**
     * Reopen a resolved issue.
     * This is a specific action: sets status to 'reopened' and keeps assignee.
     */

    public function reopenIssue(Issue $issue): Issue
    {
        if ($issue->status !== IssueStatus::RESOLVED->value) {
            throw new \Exception('Only resolved issues can be reopened.');
        }

        $issue->status = IssueStatus::REOPENED;
        $issue->save();

        $this->activityLogService->logIssueReopened($issue, auth()->user());

        // TODO: Broadcast event, log activity

        return $issue->fresh(['project', 'reporter', 'assignee']);
    }

    /**
     * Close a resolved issue.
     */
    public function closeIssue(Issue $issue): Issue
    {
        if ($issue->status !== IssueStatus::RESOLVED->value) {
            throw new \Exception('Only resolved issues can be closed.');
        }

        $issue->status = IssueStatus::CLOSED;
        $issue->completed_at = \Illuminate\Support\Carbon::now();
        $issue->save();

        // TODO: Broadcast event, log activity

        return $issue->fresh(['project', 'reporter', 'assignee']);
    }

}


9. IssueController::
    <?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignIssueRequest;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Http\Resources\IssueResource;
use App\Models\Issue;
use App\Services\IssueService;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    protected IssueService $issueService;

    public function __construct(IssueService $issueService)
    {
        $this->issueService = $issueService;
    }

    /**
     * List issues with filters.
     */
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Issue::class);

        $filters = $request->only([
            'project_id',
            'status',
            'priority',
            'severity',
            'type',
            'assigned_to',
            'reporter_id',
            'search',
            'sort',
        ]);

        $perPage = $request->get('per_page', 15);

        $issues = $this->issueService->getAllIssues($filters, $perPage);

        return IssueResource::collection($issues);

    }

    /**
     * Create a new issue.
     */
    public function store(StoreIssueRequest $request)
    {
        // $this->authorize('viewAny', Issue::class);

        $data = $request->validated();

        $data['reporter_id'] = $request->user()->id;

        $issue = $this->issueService->createIssue($data);

        return new IssueResource($issue);

    }

    /**
     * Show a single issue (full detail).
     */
    public function show(Issue $issue)
    {
        // $this->authorize('view', $issue);

        $issue->load(['project', 'reporter', 'assignee', 'comments.user', 'attachments']);
        return new IssueResource($issue);
    }

    /**
     * Update issue details (excluding status and assignment).
     */
    public function update(UpdateIssueRequest $request, Issue $issue)
    {
        // $this->authorize('update', $issue);

        $updated = $this->issueService->updateIssue($issue, $request->validated());

        return new IssueResource($updated);
    }

    /**
     * Delete an issue.
     */
    public function destroy(Issue $issue)
    {
        // $this->authorize('delete', $issue);

        $this->issueService->deleteIssue($issue);

        return response()->json(['message' => 'Issue deleted successfully.']);
    }

    // ---------- Business Actions ----------

    /**
     * Assign a developer.
     */
    public function assign(AssignIssueRequest $request, Issue $issue)
    {
        // $this->authorize('assign', $issue);

        $issue = $this->issueService->assignIssue($issue, $request->user_id);

        return new IssueResource($issue);
    }

    /**
     * Change status (workflow).
     */
    public function changeStatus(ChangeStatusRequest $request, Issue $issue)
    {
        // $this->authorize('changeStatus', $issue);

        $issue = $this->issueService->changeStatus($issue, $request->status);

        return new IssueResource($issue);
    }

    /**
     * Reopen a resolved issue.
     */
    public function reopen(Issue $issue)
    {
        // $this->authorize('reopen', $issue);

        $issue = $this->issueService->reopenIssue($issue);

        return new IssueResource($issue);
    }

    /**
     * Close a resolved issue.
     */
    public function close(Issue $issue)
    {
        // $this->authorize('close', $issue);

        $issue = $this->issueService->closeIssue($issue);

        return new IssueResource($issue);
    }

}


10. CommentService::
    <?php

namespace App\Services;

use App\Events\CommentCreated;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class CommentService
{

    protected NotificationService $notificationService;
    protected ActivityLogService $activityLogService;

    public function __construct(NotificationService $notificationService, ActivityLogService $activityLogService)
    {
        $this->notificationService = $notificationService;
        $this->activityLogService = $activityLogService;
    }

    public function getCommentByIssue(Issue $issue): Collection
    {
        return $issue->comments()
            ->with('user')
            ->oldest()
            ->get();
    }

    public function createComment(Issue $issue, User $user, string $content): Comment
    {

        // Allow admins to comment on any issue, project members to comment on
        // issues in their project, and the issue reporter/assignee regardless
        // of membership. Mirrors the access policy of IssueController::show,
        // which doesn't restrict by membership.
        $isMember = $issue->project->members()->where('user_id', $user->id)->exists();
        $isCreatorOrAssignee = $issue->reporter_id === $user->id || $issue->assigned_to === $user->id;
        $isAdmin = ($user->role?->value ?? null) === 'admin';

        if (!$isMember && !$isCreatorOrAssignee && !$isAdmin) {
            throw new \Exception('You are not a member of this project.');
        }

        $comment = new Comment();
        $comment->issue_id = $issue->id;
        $comment->user_id = $user->id;
        $comment->comment = $content;
        $comment->save();

        event(new CommentCreated($comment->fresh()));

        $this->activityLogService->logCommentAdded($issue, $user, $content);
        $this->notificationService->sendCommentAdded($comment, $user);

        // $recipients = collect();
        // if ($issue->reporter_id && $issue->reporter_id !== $author->id) {
        //     $recipients->push($issue->reporter);
        // }
        // if ($issue->assigned_to && $issue->assigned_to !== $author->id) {
        //     $recipients->push($issue->assignee);
        // }
        // Notification::send($recipients->unique(), new SystemNotification('comment_added', $data));

        return $comment->fresh('user');

    }

    public function updateComment(Comment $comment, string $content): Comment
    {
        $oldContent = $comment->comment;
        $comment->comment = $content;
        $comment->save();
        $this->activityLogService->logCommentUpdated($comment->issue, auth()->user(), $oldContent, $content);
        return $comment->fresh('user');
    }

    public function deleteComment(Comment $comment): void
    {
        $content = $comment->comment;
        $issue = $comment->issue;
        $comment->delete();
        $this->activityLogService->logCommentDeleted($issue, auth()->user(), $content);
    }

}

11. NotificationService::
    <?php

namespace App\Services;

use App\Events\NotificationCreated;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Notifications\SystemNotification;

class NotificationService
{
    private function notifyProjectMembers(Project $project, string $type, array $data, ?int $excludeUserId = null): void
    {
        $project->members()
            ->get()
            ->unique('id')
            ->each(function (User $recipient) use ($type, $data, $excludeUserId) {
                if (
                    $excludeUserId !== null &&
                    $recipient->id === $excludeUserId
                ) {
                    return;
                }

                $recipient->notify(
                    new SystemNotification($type, $data)
                );

                $notification = $recipient->notifications()
                    ->latest('created_at')
                    ->first();

                if ($notification) {
                    event(
                        new NotificationCreated($notification)
                    );
                }
            });

    }

    /**
     * Send notification when a new issue is created.
     * Notify the project members except the creator.
     */
    public function sendIssueCreated(Issue $issue, User $createdBy): void
    {
        if (!$issue->project) {
            return;
        }

        $data = [
            'issue_id' => $issue->id,
            'project_id' => $issue->project_id,
            'title' => $issue->title,
            'created_by' => $createdBy->name,
            'url' => '/issues/' . $issue->id,
        ];

        $this->notifyProjectMembers($issue->project, 'issue_created', $data);
    }

    /**
     * Send notification when an issue is assigned.
     */

    public function sendIssueAssigned(Issue $issue, User $assignee, User $assignedBy): void
    {
        $data = [
            'issue_id' => $issue->id,
            'project_id' => $issue->project_id,
            'title' => $issue->title,
            'assigned_by' => $assignedBy->name,
            'url' => '/issues/' . $issue->id,
        ];

        $this->notifyProjectMembers($issue->project, 'issue_assigned', $data);

    }

    /**
     * Send notification when a comment is added.
     * Notify the issue reporter and the assigned developer, but not the comment author.
     */
    public function sendCommentAdded(Comment $comment, User $author): void
    {
        $issue = $comment->issue;
        if (!$issue->project) {
            return;
        }

        $data = [
            'issue_id' => $issue->id,
            'project_id' => $issue->project_id,
            'title' => $issue->title,
            'comment_author' => $author->name,
            'comment_snippet' => substr($comment->comment, 0, 100) . (strlen($comment->comment) > 100 ? '...' : ''),
            'url' => '/issues/' . $issue->id,
        ];

        $this->notifyProjectMembers($issue->project, 'comment_added', $data);

    }

    /**
     * Send notification when issue status changes.
     * Notify reporter and assignee (if different).
     */

    public function sendIssueStatusChanged(Issue $issue, string $oldStatus, string $newStatus, User $changedBy): void
    {
        if (!$issue->project) {
            return;
        }

        $data = [
            'issue_id' => $issue->id,
            'project_id' => $issue->project_id,
            'title' => $issue->title,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy->name,
            'url' => '/issues/' . $issue->id,
        ];

        $this->notifyProjectMembers($issue->project, 'status_changed', $data);

    }

    /**
     * Send notification when a user is added to a project.
     */

    public function sendProjectMemberAdded(Project $project, User $member, User $addedBy): void
    {
        $data = [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'added_by' => $addedBy->name,
            'url' => '/projects/' . $project->id,
        ];

        $member->notify(new SystemNotification('project_member_added', $data));

        $notification = $member->notifications()
            ->latest('created_at')
            ->first();

        if ($notification) {
            event(new NotificationCreated($notification));
        }

    }

}


12. Events/IssueCreated::
    <?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public Issue $issue;

    /**
     * Create a new event instance.
     */
    public function __construct(public Issue $issue)
    {
        $this->issue->load(['project', 'reporter', 'assignee']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'project.' . $this->issue->project_id
            )
        ];
    }

    public function broadcastAs(): string
    {
        return 'issue.created';
    }

    public function broadcastWith(): array
    {
        // return [
        //     'id' => $this->issue->id,
        //     'title' => $this->issue->title,
        //     'status' => $this->issue->status,
        //     'priority' => $this->issue->priority,
        //     'project_id' => $this->issue->project_id,
        //     'reporter_id' => $this->issue->reporter_id,
        //     'assigned_to' => $this->issue->assigned_to,
        //     'created_at' => $this->issue->created_at->toISOString(),
        // ];
        return [
            'issue' => $this->issue->toArray(),
        ];
    }

}

13. Events/IssueUpdated::
    <?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public Issue $issue;

    /**
     * Create a new event instance.
     */
    public function __construct(public Issue $issue)
    {
        $this->issue->load([
            'project',
            'reporter',
            'assignee',
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'issue.' . $this->issue->id
            )
        ];
    }

    public function broadcastAs(): string
    {
        return 'issue.updated';
    }

    public function broadcastWith(): array
    {
        // return [
        //     'id' => $this->issue->id,
        //     'title' => $this->issue->title,
        //     'status' => $this->issue->status,
        //     'priority' => $this->issue->priority,
        //     'assignee' => $this->issue->assignee?->name,
        //     'updated_at' => $this->issue->updated_at->toISOString(),
        // ];

        return [
            'issue' => $this->issue->toArray(),
        ];

    }
}

14. Events/IssueAssigned::
    <?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public Issue $issue;

    /**
     * Create a new event instance.
     */
    public function __construct(public Issue $issue, public int $assignedTo)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'issue.' . $this->issue->id,
            ),
            new PrivateChannel(
                'user.' . $this->assignedTo
            )
        ];
    }

    public function broadcastAs(): string
    {
        return 'issue.assigned';
    }

    public function broadcastWith(): array
    {
        // return [
        //     'id' => $this->issue->id,
        //     'title' => $this->issue->title,
        //     'project_id' => $this->issue->project_id,
        //     'assigned_by' => auth()->user()?->name,
        // ];
        return [
            'issue_id' => $this->issue->id,
            'assigned_to' => $this->assignedTo,
        ];
    }

}

15. Events/CommentCreated::
    <?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public Comment $comment;

    /**
     * Create a new event instance.
     */
    public function __construct(public Comment $comment)
    {
        $this->comment->load('user');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'issue.' . $this->comment->issue_id
            )
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }

    public function broadcastWith(): array
    {
        // return [
        //     'id' => $this->comment->id,
        //     'comment' => $this->comment->comment,
        //     'user_id' => $this->comment->user_id,
        //     'user_name' => $this->comment->user->name,
        //     'created_at' => $this->comment->created_at->toISOString(),
        // ];
        return [
            'comment' => $this->comment->toArray(),
        ];
    }

}


16. Events/AttachmentUploaded::
    <?php

namespace App\Events;

use App\Models\Attachment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttachmentUploaded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public Attachment $attachment;

    /**
     * Create a new event instance.
     */
    public function __construct(public Attachment $attachment)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('issue.' . $this->attachment->issue_id)
        ];
    }

    public function broadcastAs(): string
    {
        return 'attachment.uploaded';
    }

    public function broadcastWith(): array
    {
        // return [
        //     'id' => $this->attachment->id,
        //     'file_name' => $this->attachment->file_name,
        //     'file_size' => $this->attachment->file_size,
        //     'user_id' => $this->attachment->user_id,
        //     'created_at' => $this->attachment->created_at->toISOString(),
        // ];
        return [
            'attachment' => $this->attachment->toArray(),
        ];
    }

}

17. Events/AttachmentDeleted::
    <?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttachmentDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public int $attachmentId, public int $issueId)
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'issue.' . $this->issueId
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'attachment.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'attachment_id' => $this->attachmentId,
        ];
    }

}


18. Events/IssueStatusChanged::
    <?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public Issue $issue;
    // public string $oldStatus;
    // public string $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(public Issue $issue, public string $oldStatus, public string $newStatus)
    {
        // $this->issue = $issue;
        // $this->oldStatus = $oldStatus;
        // $this->newStatus = $newStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'issue.' . $this->issue->id
            ),

            new PrivateChannel(
                'project.' . $this->issue->project_id
            ),
        ];
    }

    public function broadcastAs(): string
    {
        // return 'issue.status_changed';
        return 'issue.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'issue_id' => $this->issue->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'assignee' => $this->issue->assignee?->name,
        ];
    }

}


19. Events/NotificationCreated::
    <?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public $notification;

    /**
     * Create a new event instance.
     */
    public function __construct(public DatabaseNotification $notification)
    {
        // $this->notification = $notification;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'user.' . $this->notification->notifiable_id
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'notification' => [
                'id' => $this->notification->id,
                'type' => $this->notification->type,
                'data' => $this->notification->data,
                'read_at' => $this->notification->read_at,
                'created_at' => $this->notification->created_at,
            ],
        ];
    }

}

20. Events/ActivityLogged:: 
    <?php

namespace App\Events;

use App\Models\ActivityLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityLogged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public ActivityLog $activity)
    {
        $this->activity->load('user');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'project.' . $this->activity->project_id
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'activity.logged';
    }

    public function broadcastWith(): array
    {
        return [
            'activity' => $this->activity->toArray(),
        ];
    }
}

Frontend::::

1. frontend/.env::
   BASE_URL=http://localhost:5173

VITE_API_URL=http://localhost:8000/api
VITE_BACKEND_URL=http://localhost:8000

VITE_REVERB_APP_KEY=pdhydnsdcbmqawzt5zse
VITE_REVERB_HOST="localhost"
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http


2. api.js::
   import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

// Track CSRF token state
let csrfTokenPromise = null

export const getCsrfToken = async () => {
  if (csrfTokenPromise) return csrfTokenPromise

  // csrfTokenPromise = api
  //   .get('/sanctum/csrf-cookie')
  //   .then(() => {
  //     return true
  //   })
  //   .catch((error) => {
  //     csrfTokenPromise = null // Reset on failure
  //     throw error
  //   })

  csrfTokenPromise = axios
    .get(`${import.meta.env.VITE_BACKEND_URL}/sanctum/csrf-cookie`, {
      withCredentials: true,
      headers: {
        Accept: 'application/json',
      },
    })
    .then(() => {
      return true
    })
    .catch((error) => {
      csrfTokenPromise = null // Reset on failure
      throw error
    })

  return csrfTokenPromise
}

export const resetCsrfToken = () => {
  csrfTokenPromise = null
}

export default api


2. main.js
   import './assets/main.css'
// Import Swiper styles
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'
import 'jsvectormap/dist/jsvectormap.css'
import 'flatpickr/dist/flatpickr.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Vue3Toastify from 'vue3-toastify' // 👈 Import Toastify
import 'vue3-toastify/dist/index.css' // 👈 Import Toastify CSS
import App from './App.vue'
import router from './router'
import VueApexCharts from 'vue3-apexcharts'

// PrimeVue Import
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'

// PrimeIcons
import 'primeicons/primeicons.css'

import axios from 'axios'
import api from './services/api'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: '.dark',
    },
  },
})
app.use(VueApexCharts)
app.use(Vue3Toastify, {
  autoClose: 3000,
  position: 'top-right',
  clearOnUrlChange: false,
})

app.mount('#app')


3. services/echo.js::
   import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import api from './api'

window.Pusher = Pusher

const getCookie = (name) => {
  const match = document.cookie.match(
    new RegExp('(^|;\\s*)' + name + '=([^;]*)')
  )
  return match ? decodeURIComponent(match[2]) : null
}

// Custom authorizer that uses axios so cookies (session + XSRF) are sent.
// Pusher's built-in XHR auth does NOT send cross-origin cookies, which
// breaks Sanctum's session-based auth on /broadcasting/auth.
const authorizer = (channel, options) => {
  return {
    authorize: (socketId, callback) => {
      api
        .post(
          '/broadcasting/auth',
          {
            socket_id: socketId,
            channel_name: channel.name,
          },
          {
            headers: {
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
            },
          }
        )
        .then((response) => {
          callback(null, response.data)
        })
        .catch((error) => {
          callback(error, null)
        })
    },
  }
}

const echo = new Echo({
  broadcaster: 'reverb',

  key: import.meta.env.VITE_REVERB_APP_KEY,

  wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',

  wsPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,

  wssPort: Number(import.meta.env.VITE_REVERB_PORT) || 8080,

  forceTLS: import.meta.env.VITE_REVERB_SCHEME === 'https',

  enabledTransports: ['ws', 'wss'],

  // Custom authorizer sends cookies (session + XSRF) and proper headers.
  authorizer: authorizer,

  // Keep the legacy headers/endpoint around as a fallback so other Pusher
  // internal calls (e.g. /pusher/auth on Reverb) still work.
  authEndpoint: `${import.meta.env.VITE_BACKEND_URL}/api/broadcasting/auth`,

  auth: {
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      get 'X-XSRF-TOKEN'() {
        return getCookie('XSRF-TOKEN')
      },
    },
  },

  withCredentials: true,
})

export default echo


4. router/index.js::
   import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

// Import Users Pages
import UsersIndex from '@/views/Admin/Users/Index.vue'
import UsersCreate from '@/views/Admin/Users/Create.vue'
import UsersEdit from '@/views/Admin/Users/Edit.vue'
import Profile from '@/views/Admin/Profile.vue'

// Import Project Pages
import ProjectsIndex from '@/views/Admin/Projects/Index.vue'
import ProjectsCreate from '@/views/Admin/Projects/Create.vue'
import ProjectsEdit from '@/views/Admin/Projects/Edit.vue'
import ProjectsShow from '@/views/Admin/Projects/Show.vue'

// Import Issues Pages
import IssuesIndex from '@/views/Admin/Issues/Index.vue'
import IssuesCreate from '@/views/Admin/Issues/Create.vue'
import IssuesEdit from '@/views/Admin/Issues/Edit.vue'
import IssuesShow from '@/views/Admin/Issues/Show.vue'

// Import Notifications Pages
import NotificationsIndex from '@/views/Admin/Notifications/Index.vue'
import NotificationsShow from '@/views/Admin/Notifications/Show.vue'

// Import Activity Logs Pages
import ActivityLogsIndex from '@/views/Admin/ActivityLogs/Index.vue'

// Import Project Members Page
// import ProjectMembers from '@/views/Admin/ProjectMembers/ProjectMembers.vue'

const routes = [
  {
    path: '/',
    name: 'Login',
    component: () => import('../views/Auth/Signin.vue'),
    meta: { guest: true },
  },
  {
    path: '/dashboard',
    name: 'Ecommerce',
    component: () => import('../views/Ecommerce.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/users',
    name: 'UsersIndex',
    component: UsersIndex,
    meta: { requiresAuth: true },
  },
  {
    path: '/users/create',
    name: 'UsersCreate',
    component: UsersCreate,
    meta: { requiresAuth: true },
  },
  {
    path: '/users/:id/edit',
    name: 'UsersEdit',
    component: UsersEdit,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'Profile',
    component: Profile,
    meta: { requiresAuth: true },
  },
  {
    path: '/projects',
    name: 'ProjectsIndex',
    component: ProjectsIndex,
    meta: { requiresAuth: true },
  },
  {
    path: '/projects/create',
    name: 'ProjectsCreate',
    component: ProjectsCreate,
    meta: { requiresAuth: true }, // fixed
  },
  {
    path: '/projects/:id/edit',
    name: 'ProjectsEdit',
    component: ProjectsEdit,
    meta: { requiresAuth: true },
  },
  {
    path: '/projects/:id',
    name: 'ProjectsShow',
    component: ProjectsShow,
    meta: { requiresAuth: true },
  },
  {
    path: '/issues',
    name: 'IssuesIndex',
    component: IssuesIndex,
    meta: { requiresAuth: true },
  },
  {
    path: '/issues/create',
    name: 'IssuesCreate',
    component: IssuesCreate,
    meta: { requiresAuth: true },
  },
  {
    path: '/issues/:id/edit',
    name: 'IssuesEdit',
    component: IssuesEdit,
    meta: { requiresAuth: true },
  },
  {
    path: '/issues/:id',
    name: 'IssuesShow',
    component: IssuesShow,
    meta: { requiresAuth: true },
  },
  {
    path: '/notifications',
    name: 'NotificationsIndex',
    component: NotificationsIndex,
    meta: { requiresAuth: true },
  },
  {
    path: '/notifications/:id',
    name: 'NotificationsShow',
    component: NotificationsShow,
    meta: { requiresAuth: true },
  },
  {
    path: '/activity-logs',
    name: 'ActivityLogsIndex',
    component: ActivityLogsIndex,
    meta: { requiresAuth: true },
  },
  {
    path: '/calendar',
    name: 'Calendar',
    component: () => import('../views/Others/Calendar.vue'),
    meta: {
      title: 'Calendar',
    },
  },
  // {
  //   path: '/profile',
  //   name: 'Profile',
  //   component: () => import('../views/Others/UserProfile.vue'),
  //   meta: {
  //     title: 'Profile',
  //   },
  // },
  {
    path: '/form-elements',
    name: 'Form Elements',
    component: () => import('../views/Forms/FormElements.vue'),
    meta: {
      title: 'Form Elements',
    },
  },
  {
    path: '/basic-tables',
    name: 'Basic Tables',
    component: () => import('../views/Tables/BasicTables.vue'),
    meta: {
      title: 'Basic Tables',
    },
  },
  {
    path: '/line-chart',
    name: 'Line Chart',
    component: () => import('../views/Chart/LineChart/LineChart.vue'),
  },
  {
    path: '/bar-chart',
    name: 'Bar Chart',
    component: () => import('../views/Chart/BarChart/BarChart.vue'),
  },
  {
    path: '/alerts',
    name: 'Alerts',
    component: () => import('../views/UiElements/Alerts.vue'),
    meta: {
      title: 'Alerts',
    },
  },
  {
    path: '/avatars',
    name: 'Avatars',
    component: () => import('../views/UiElements/Avatars.vue'),
    meta: {
      title: 'Avatars',
    },
  },
  {
    path: '/badge',
    name: 'Badge',
    component: () => import('../views/UiElements/Badges.vue'),
    meta: {
      title: 'Badge',
    },
  },

  {
    path: '/buttons',
    name: 'Buttons',
    component: () => import('../views/UiElements/Buttons.vue'),
    meta: {
      title: 'Buttons',
    },
  },

  {
    path: '/images',
    name: 'Images',
    component: () => import('../views/UiElements/Images.vue'),
    meta: {
      title: 'Images',
    },
  },
  {
    path: '/videos',
    name: 'Videos',
    component: () => import('../views/UiElements/Videos.vue'),
    meta: {
      title: 'Videos',
    },
  },
  {
    path: '/blank',
    name: 'Blank',
    component: () => import('../views/Pages/BlankPage.vue'),
    meta: {
      title: 'Blank',
    },
  },

  {
    path: '/error-404',
    name: '404 Error',
    component: () => import('../views/Errors/FourZeroFour.vue'),
    meta: {
      title: '404 Error',
    },
  },

  {
    path: '/signin',
    name: 'Signin',
    component: () => import('../views/Auth/Signin.vue'),
    meta: {
      title: 'Signin',
    },
  },
  {
    path: '/signup',
    name: 'Signup',
    component: () => import('../views/Auth/Signup.vue'),
    meta: {
      title: 'Signup',
    },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, form, next) => {
  const auth = useAuthStore()

  // If we haven't checked auth yet, do it
  if (auth.user === null && !auth.authenticated) {
    await auth.checkAuth()
  }

  if (to.meta.requiresAuth && !auth.authenticated) {
    next({ name: 'Login' })
  } else if (to.meta.guest && auth.authenticated) {
    next({ name: 'Ecommerce' })
  } else {
    next()
  }
})

export default router


5. stores/auth.js::
   import { defineStore } from 'pinia'
import api, { getCsrfToken, resetCsrfToken } from '@/services/api'
import { useNotificationStore } from './notifications'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    authenticated: false,
    loading: false,
  }),
  getters: {
    isAdmin: (state) => state.user?.role === 'admin',
    isDeveloper: (state) => state.user?.role === 'developer',
    isTester: (state) => state.user?.role === 'tester',
  },
  actions: {
    async login(credentials) {
      this.loading = true

      try {
        // 1. Get CSRF cookie (cached after first call)
        await getCsrfToken()

        // 2. Login
        // const result = await api.post('/api/login', credentials)
        await api.post('/login', credentials)

        this.authenticated = true

        // 3. Fetch user
        await this.getUser()

        return true
      } catch (error) {
        console.error('Login failed: ', error)
        return false
      } finally {
        this.loading = false
      }
    },
    async getUser() {
      try {
        // const response = await api.get('/api/user')
        const response = await api.get('/user')
        // Normalize common response shapes so `user` is always the user object
        // Support: { data: user }, { user: user }, or direct user object
        this.user = response.data?.data ?? response.data?.user ?? response.data
        this.authenticated = true
        // console.log(this.user.name)
        return true
      } catch (error) {
        this.user = null
        this.authenticated = false
        throw error
        // console.error('User profile failed: ', error)
        // return false
      }
    },
    async checkAuth() {
      try {
        await this.getUser()
      } catch {}
    },
    // async checkAuth() {
    //   try {
    //     const response = await api.get('/api/user')
    //     if (response.status === 200) {
    //       this.user = response.data
    //       this.authenticated = true
    //       return true
    //     }
    //     return false
    //   } catch (error) {
    //     console.error('Auth error: ', error)
    //     this.user = null
    //     this.authenticated = false
    //     return false
    //   }
    // },
    async logout() {
      try {
        // await getCsrfToken()
        // await api.post('/api/logout')
        await api.post('/logout')

        // Reset notification listeners so the next login can re-register
        try {
          const notificationStore = useNotificationStore()
          notificationStore.listenersInitialized = false
        } catch (_) {}

        // Clear auth state
        this.user = null
        this.authenticated = false

        // Reset CSRF token so next login gets fresh token
        // resetCsrfToken()

        return true
      } catch (error) {
        console.error('Logout failed: ', error)
        return false
      }
    },
  },
})


6. stores/issues.js::
   import { defineStore } from 'pinia'
import api from '@/services/api'
import echo from '@/services/echo'
import { useAuthStore } from './auth'

export const useIssueStore = defineStore('issues', {
  state: () => ({
    issues: [],
    issue: null,
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    },
  }),
  actions: {
    async fetchIssues(params = {}) {
      this.loading = true
      try {
        // const response = await api.get('/api/issues', { params })
        const response = await api.get('/issues', { params })
        console.log('API response:', response.data)
        this.issues = response.data.data || []
        this.pagination = {
          current_page: response.data.current_page || 1,
          last_page: response.data.last_page || 1,
          per_page: response.data.per_page || 10,
          total: response.data.total || 0,
        }
        console.log('Store issues after assignment: ', this.issues)
        return response.data
      } catch (error) {
        console.error('Failed to fetch issues: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchIssue(id) {
      this.loading = true
      try {
        // const response = await api.get(`/api/issues/${id}`)
        const response = await api.get(`/issues/${id}`)
        this.issue = response.data.data
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch issue:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async createIssue(data) {
      this.loading = true
      try {
        // const response = await api.post('/api/issues', data)
        const response = await api.post('/issues', data)
        return response.data.data
      } catch (error) {
        console.error('Failed to create issue:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async updateIssue(id, data) {
      this.loading = true
      try {
        // const response = await api.put(`/api/issues/${id}`, data)
        const response = await api.put(`/issues/${id}`, data)
        return response.data.data
      } catch (error) {
        console.error('Failed to update issue: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async deleteIssue(id) {
      this.loading = true
      try {
        // await api.delete(`/api/issues/${id}`)
        await api.delete(`/issues/${id}`)
      } catch (error) {
        console.error('Failed to delete issue:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    // Business actions
    async assignIssue(id, userId) {
      // const response = await api.patch(`/api/issues/${id}/assign`, { user_id: userId })
      const response = await api.patch(`/issues/${id}/assign`, { user_id: userId })
      return response.data.data
    },
    async changeStatus(id, status) {
      // const response = await api.patch(`/api/issues/${id}/status`, { status })
      const response = await api.patch(`/issues/${id}/status`, { status })
      return response.data.data
    },
    async reopenIssue(id) {
      // const response = await api.patch(`/api/issues/${id}/reopen`)
      const response = await api.patch(`/issues/${id}/reopen`)
      return response.data.data
    },
    async closeIssue(id) {
      // const response = await api.patch(`/api/issues/${id}/close`)
      const response = await api.patch(`/issues/${id}/close`)
      return response.data.data
    },
    listenToIssue(issueId) {
      if (!issueId) return

      console.log(`Listening to issue.${issueId}`)

      echo
        .private(`issue.${issueId}`)

        .listen('.issue.status.changed', (event) => {
          console.log('REAL-TIME ISSUE STATUS:', event)

          if (this.issue && this.issue.id === event.issue_id) {
            this.issue = {
              ...this.issue,

              status: event.new_status,

              status_label: event.status_label ?? this.issue.status_label,
            }
          }
        })

        .listen('.issue.assigned', (event) => {
          console.log('REAL-TIME ISSUE ASSIGNED:', event)

          if (this.issue && this.issue.id === event.issue_id) {
            this.issue = {
              ...this.issue,

              assigned_to: event.assigned_to,

              assignee: event.assignee,
            }
          }
        })

        .listen('.issue.updated', (event) => {
          console.log('REAL-TIME ISSUE UPDATED:', event)

          if (this.issue && this.issue.id === event.issue.id) {
            this.issue = event.issue
          }
        })
    },

    stopListeningToIssue(issueId) {
      if (!issueId) return

      console.log(`Leaving issue.${issueId}`)

      echo.leave(`issue.${issueId}`)
    },

    // listenToProject(projectId) {
    //   if (!projectId) return

    //   echo.private(`project.${projectId}`).listen('.issue.created', (event) => {
    //     const newIssue = event.issue

    //     const exists = this.issues.some((issue) => issue.id === newIssue.id)

    //     if (!exists) {
    //       this.issues.unshift(newIssue)
    //     }
    //   })
    // },
    // stopListening(projectId) {
    //   echo.leave(`project.${projectId}`)
    // },

    initializeListeners(issueId) {
      if (!issueId) return

      console.log(`Subscribing to issue.${issueId} for comments`)

      echo.private(`issue.${issueId}`).listen('.comment.created', (event) => {
        console.log('Comment created:', event)

        const comment = event.comment

        if (!comment) return

        const exists = this.comments.some((item) => item.id === comment.id)

        if (!exists) {
          this.comments.push(comment)
        }
      })
    },
    stopListeners(issueId) {
      if (!issueId) return

      echo.leave(`issue.${issueId}`)
    },
  },
})


8. issues/show.vue::
   <script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useIssueStore } from '@/stores/issues';
import { useAttachmentStore } from '@/stores/attachments';
import { useProjectStore } from '@/stores/projects';
import { useCommentStore } from '@/stores/comments';
import { useAuthStore } from '@/stores/auth';
import Select from 'primevue/select';
import Button from 'primevue/button';
import Swal from 'sweetalert2';
import { storeToRefs } from 'pinia';

import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';

// ... other imports
import CommentList from '@/components/comments/CommentList.vue';
import CommentForm from '@/components/comments/CommentForm.vue';

import AttachmentList from '@/components/attachments/AttachmentList.vue';
import AttachmentUpload from '@/components/attachments/AttachmentUpload.vue';

import echo from '@/services/echo';

const issue = ref(null);
const loading = ref(false);

const route = useRoute();
const router = useRouter();

const issueStore = useIssueStore();
const projectStore = useProjectStore();
const commentStore = useCommentStore();
const authStore = useAuthStore();
const attachmentStore = useAttachmentStore();

const { attachments, loading: attachmentsLoading } = storeToRefs(attachmentStore);

const comments = ref([]);
const loadingComments = ref(false);
const editingComment = ref(null);
const showCommentForm = ref(false);

const actionLoading = ref(false);
const selectedStatus = ref(null);
const selectedAssignee = ref(null);
const projectMembers = ref([]);

const currentPageTitle = ref('Issue Details');

const canManageMembers = computed(() => {
  if (!issue.value) return false;
  return authStore.isAdmin || authStore.user?.id === issue.value.created_by;
});

const availableStatuses = computed(() => {
  if (!issue.value) return [];
  const transitions = {
    open: ['assigned'],
    assigned: ['in_progress'],
    in_progress: ['code_review'],
    code_review: ['testing'],
    testing: ['resolved', 'reopened'],
    resolved: ['closed', 'reopened'],
    reopened: ['in_progress'],
    closed: [],
  };

  const next = transitions[issue.value.status] || [];
  return next.map(s => ({
    label: s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' '),
    value: s,
  }));

});


const changeStatus = async () => {
  if (!selectedStatus.value) return;
  actionLoading.value = true;
  try {
    const updated = await issueStore.changeStatus(issue.value.id, selectedStatus.value);
    issue.value = updated;
    selectedStatus.value = null;
    Swal.fire('Status update', '', 'success');
  } catch (error) {
    Swal.fire('error', error.response?.data?.message || 'Failed to change status', 'error');
  } finally {
    actionLoading.value = false;
  }
}

const assignUser = async () => {
  if (!selectedAssignee.value) return;
  actionLoading.value = true;
  try {
    const updated = await issueStore.assignIssue(issue.value.id, selectedAssignee.value);
    issue.value = updated;
    selectedAssignee.value = null;
    Swal.fire('Assigned', 'Issue assigned successfully.', 'success');
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to assign', 'error');
  } finally {
    actionLoading.value = false;
  }
}

const reopen = async () => {
  actionLoading.value = true;
  try {
    const updated = await issueStore.reopenIssue(issue.value.id);
    issue.value = updated;
    Swal.fire('Reopened', 'Issue has been reopened.', 'success');
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to reopen.', 'error');
  } finally {
    actionLoading.value = false;
  }
}

const closeIssue = async () => {
  actionLoading.value = true;
  try {
    const updated = await issueStore.closeIssue(issue.value.id);
    issue.value = updated;
    Swal.fire('Closed', 'Issue has been closed.', 'success');
  } catch (error) {
    Swal.fire('Error', error.response?.data?.message || 'Failed to close.', 'error');
  } finally {
    actionLoading.value = false;
  }
};

// comments
const loadComments = async () => {
  if (!issue.value) return;
  loadingComments.value = true;
  try {
    comments.value = await commentStore.fetchComments(issue.value.id);
  } catch (error) {
    console.error('Failed to load comments: ', error);
  } finally {
    loadingComments.value = false;
  }
}

const handleCommentSubmit = async (content) => {
  if (editingComment.value) {
    //update
    await commentStore.updateComment(editingComment.value.id, content);
    editingComment.value = null;
    // Re-fetch or update local - store already updates
  } else {
    // Create
    await commentStore.createComment(issue.value.id, content);
  }
  // Refresh list from store
  comments.value = commentStore.comments;
}

const startEdit = (comment) => {
  editingComment.value = comment;
};

const cancelEdit = () => {
  editingComment.value = null;
};

const deleteComment = async (commentId) => {
  await commentStore.deleteComment(commentId);
  comments.value = commentStore.comments;
}

// Load comments when issue loads

const loadAttachments = async () => {
  if (!issue.value) return;
  await attachmentStore.fetchAttachments(issue.value.id);
  // attachmentsLoading.value = true;
  // try {
  //   await attachmentStore.fetchAttachments(issue.value.id);
  //   attachments.value = attachmentStore.attachments;
  // } finally {
  //   attachmentsLoading.value = false;
  // }
};

const handleUploadAttachment = async (file) => {
  await attachmentStore.uploadAttachment(issue.value.id, file);
  // attachments.value = attachmentStore.attachments;
};

const handleDeleteAttachment = async (id) => {
  await attachmentStore.deleteAttachment(id);
  attachments.value = attachmentStore.attachments;
};


const loadIssue = async () => {
  loading.value = true;
  try {
    const data = await issueStore.fetchIssue(route.params.id);
    issue.value = data;

    if (issue.value) {
      commentStore.initializeListeners(issue.value.id);
      attachmentStore.initializeListeners(issue.value.id);
    }

    if (data.project) {
      const members = await projectStore.fetchMembers(data.project.id);
      projectMembers.value = members;
    }
    await loadComments();
    await loadAttachments();
  } catch (error) {
    console.error('Error loading issue: ', error);
    router.push('/issues');
  } finally {
    loading.value = false;
  }
}

const subscribeToIssueChannel = () => {
  if (!issue.value?.id) {
    console.warn('Cannot subscribe: issue ID missing')
    return
  }

  const issueId = issue.value.id

  console.log('Subscribing to issue channel:', `issue.${issueId}`)

  echo
    .private(`issue.${issueId}`)

    // Issue status
    .listen('.issue.status.changed', (event) => {
      console.log('REALTIME: status changed', event)

      if (issue.value?.id === event.issue_id) {
        issue.value.status = event.new_status
      }
    })

    // Comment
    .listen('.comment.created', (event) => {
      console.log('REALTIME: comment created', event)

      const comment = event.comment

      if (!comment) {
        return
      }

      const exists = comments.value.some(
        item => item.id === comment.id
      )

      if (!exists) {
        comments.value.push(comment)
      }
    })

    // Attachment uploaded
    .listen('.attachment.uploaded', (event) => {
      console.log('REALTIME: attachment uploaded', event)

      if (event.attachment) {
        attachments.value.unshift(event.attachment)
      }
    })

    // Attachment deleted
    .listen('.attachment.deleted', (event) => {
      console.log('REALTIME: attachment deleted', event)

      attachments.value = attachments.value.filter(
        item => item.id !== event.attachment_id
      )
    })
}

onMounted(async () => {

  await authStore.checkAuth()

  if (!authStore.authenticated) {
    console.log('User is not authenticated')
    return
  }

  const data = await issueStore.fetchIssue(route.params.id)
  issue.value = data

  // await loadIssue()

  if (!issue.value) {
    return
  }

  const issueId = issue.value.id

  // 4. Load project members
  if (issue.value.project?.id) {

    projectMembers.value =
      await projectStore.fetchMembers(
        issue.value.project.id
      )
  }

  // 5. Load comments
  await loadComments()

  // 6. Load attachments
  await loadAttachments()

  if (issue.value?.id) {
    echo.leave(`issue.${issue.value.id}`)
  }

  subscribeToIssueChannel()

  // console.log(
  //   'Starting real-time listeners for:',
  //   issueId
  // )

  // issueStore.listenToIssue(issueId)

  // commentStore.initializeListeners(issueId)

  // attachmentStore.initializeListeners(issueId)

});

onUnmounted(() => {

  if (!issue.value) {
    return
  }

  const issueId = issue.value.id

  issueStore.stopListeningToIssue(issueId)

  commentStore.stopListeners?.(issueId)

  attachmentStore.stopListeners?.(issueId)
})


</script>

<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div v-if="issue" class="space-y-5 sm:space-y-6">
      <ComponentCard title="Issue Details">
        <div class="grid grid-cols-2 gap-4">
          <div><span class="font-medium">Project: </span> {{ issue.project?.name }}</div>
          <div><span class="font-medium">Type: </span> {{ issue.type_label }}</div>
          <div class="col-span-2"><span class="font-medium">Title: </span> {{ issue.title }}</div>
          <div class="col-span-2"><span class="font-medium">Description: </span> {{ issue.description || 'None' }}</div>
          <div><span class="font-medium">Priority: </span> {{ issue.priority_label }}</div>
          <div><span class="font-medium">Severity: </span> {{ issue.severity_label }}</div>
          <div><span class="font-medium">Status: </span> {{ issue.status_label }}</div>
          <div><span class="font-medium">Assignee: </span> {{ issue.assignee?.name || 'Unassigned' }}</div>
          <div><span class="font-medium">Reporter: </span> {{ issue.reporter?.name }}</div>
          <div><span class="font-medium">Due Date: </span> {{ issue.due_date ? new
            Date(issue.due_date).toLocaleDateString() : 'None' }}</div>
          <div><span class="font-medium">Estimated Hours: </span> {{ issue.estimated_hours || 'Not set' }}</div>
        </div>
        <hr class="my-4" />
        <h1 class="text-xl text-gray-700">Issue Assign To:</h1>
        <div class="flex flex-wrap gap-2">
          <!-- Status Change -->
          <Select v-model="selectedStatus" :options="availableStatuses" optionLabel="label" optionValue="value"
            placeholder="Change status" />
          <Button @click="changeStatus" :loading="actionLoading" label="Update Status"
            class="bg-blue-600 text-white px-4 py-2 rounded" />

          <!-- Assign -->
          <Select v-model="selectedAssignee" :options="projectMembers" optionLabel="name" optionValue="id"
            placeholder="Assign to" />
          <Button @click="assignUser" :loading="actionLoading" label="Assign"
            class="bg-green-600 text-white px-4 py-2 rounded" />

          <Button @click="reopen" v-if="issue.status === 'resolved'" label="Reopen"
            class="bg-yellow-600 text-white px-4 py-2 rounded" />
          <Button @click="closeIssue" v-if="issue.status === 'resolved'" label="Close"
            class="bg-gray-600 text-white px-4 py-2 rounded" />
        </div>


        <h1 class="text-xl text-gray-700">Issue Image/Screenshot:</h1>
        <AttachmentList :attachments="attachments" :loading="attachmentsLoading" @delete="handleDeleteAttachment" />
        <AttachmentUpload @upload="handleUploadAttachment" />

        <h1 class="text-xl text-gray-700">Comments:</h1>
        <CommentList :comments="comments" :loading="loadingComments" @edit="startEdit" @delete="deleteComment" />

        <CommentForm :editing="!!editingComment" :initialContent="editingComment ? editingComment.comment : ''"
          @submit="handleCommentSubmit" @cancelEdit="cancelEdit" />

      </ComponentCard>
    </div>
    <div v-else>
      <div class="flex justify-center" style="margin-top: 60px;">
        <i class="pi pi-spin pi-spinner" style="font-size: 4rem"></i>
      </div>
    </div>
  </AdminLayout>
</template>


9. stores/comments.js::
    import { defineStore } from 'pinia'
import api from '@/services/api'
import echo from '@/services/echo'

export const useCommentStore = defineStore('comments', {
  state: () => ({
    comments: [],
    loading: false,
  }),
  actions: {
    async fetchComments(issueId) {
      this.loading = true
      try {
        // const response = await api.get(`/api/issues/${issueId}/comments`)
        const response = await api.get(`/issues/${issueId}/comments`)
        this.comments = response.data.data
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch comments: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async createComment(issueId, content) {
      this.loading = true
      try {
        // const response = await api.post(`/api/issues/${issueId}/comments`, { comment: content })
        const response = await api.post(`/issues/${issueId}/comments`, { comment: content })
        const newComment = response.data.data
        this.comments.push(newComment)
        return newComment
      } catch (error) {
        console.error('Failed to create comment:', error.response?.status, error.response?.data)
        throw error
      } finally {
        this.loading = false
      }
    },
    async updateComment(commentId, content) {
      try {
        // const response = await api.put(`/api/comments/${commentId}`, { comment: content })
        const response = await api.put(`/comments/${commentId}`, { comment: content })

        const updated = response.data.data
        const index = this.comments.findIndex((c) => c.id === commentId)
        if (index !== -1) {
          this.comments[index] = updated
        }
        return updated
      } catch (error) {
        console.error('Failed to update comment: ', error)
        throw error
      }
    },
    async deleteComment(commentId) {
      try {
        // await api.delete(`/api/comments/${commentId}`)
        await api.delete(`/comments/${commentId}`)
        this.comments = this.comments.filter((c) => c.id !== commentId)
      } catch (error) {
        console.error('Failed to delete comment: ', error)
        throw error
      }
    },

    initializeListeners(issueId) {
      if (!issueId) return
      // The actual subscription is set up in the page (Show.vue) via
      // subscribeToIssueChannel(). Keeping this hook around so callers
      // that still reference it don't crash, but no listener is added.
    },
  },
})


10. stores/attachments.js::
    import { defineStore } from 'pinia'
import api from '@/services/api'
import echo from '@/services/echo'

export const useAttachmentStore = defineStore('attachments', {
  state: () => ({
    attachments: [],
    loading: false,
    uploading: false,
  }),
  actions: {
    async fetchAttachments(issueId) {
      this.loading = true
      try {
        // const response = await api.get(`/api/issues/${issueId}/attachments`)
        const response = await api.get(`/issues/${issueId}/attachments`)
        this.attachments = response.data.data
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch attachments:', error)
        throw error
      } finally {
        this.loading = false
      }
    },
    async uploadAttachment(issueId, file) {
      this.uploading = true
      try {
        const formData = new FormData()
        formData.append('file', file)
        // const response = await api.post(`/api/issues/${issueId}/attachments`, formData, {
        //   headers: { 'Content-Type': 'multipart/form-data' },
        // })
        const response = await api.post(`/issues/${issueId}/attachments`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        const newAtt = response.data.data
        this.attachments.push(newAtt)
        return newAtt
      } catch (error) {
        console.error('Failed to upload attachment:', error)
        throw error
      } finally {
        this.uploading = false
      }
    },
    async deleteAttachment(attachmentId) {
      try {
        // await api.delete(`/api/attachments/${attachmentId}`)
        await api.delete(`/attachments/${attachmentId}`)
        this.attachments = this.attachments.filter((a) => a.id !== attachmentId)
      } catch (error) {
        console.error('Failed to delete attachment:', error)
        throw error
      }
    },
    initializeListeners(issueId) {
      if (!issueId) return

      echo
        .private(`issue.${issueId}`)

        .listen('.attachment.uploaded', (event) => {
          console.log('Attachment uploaded:', event)

          const attachment = event.attachment

          if (!attachment) return

          const exists = this.attachments.some((item) => item.id === attachment.id)

          if (!exists) {
            this.attachments.unshift(attachment)
          }
        })

        .listen('.attachment.deleted', (event) => {
          console.log('Attachment deleted:', event)

          this.attachments = this.attachments.filter((item) => item.id !== event.attachment_id)
        })
    },
    stopListeners(issueId) {
      echo.leave(`issue.${issueId}`)
    },
  },
})

11. stores/notifications.js::
    import { defineStore } from 'pinia'
import api from '@/services/api'
import echo from '@/services/echo'
import { useAuthStore } from './auth'

export const useNotificationStore = defineStore('notifications', {
  state: () => ({
    notifications: [],
    loading: false,
    listenersInitialized: false, // prevent duplicate listeners
    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    },
  }),
  getters: {
    unreadCount: (state) => state.notifications.filter((n) => !n.read_at).length,
  },
  actions: {
    async fetchNotifications(page = 1, append = false) {
      this.loading = true
      try {
        // const response = await api.get('/api/notifications/', { params: { page } })
        const response = await api.get('/notifications/', { params: { page } })
        if (append) {
          this.notifications = [...this.notifications, ...response.data.data]
        } else {
          this.notifications = response.data.data
        }

        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total,
        }
        //  update unread count
        return response.data
      } catch (error) {
        console.error('Failed to fetch notifications: ', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    async markAsRead(id) {
      try {
        // await api.patch(`/api/notifications/${id}/read`)
        await api.patch(`/notifications/${id}/read`)
        const notify = this.notifications.find((n) => n.id === id)
        if (notify) {
          notify.read_at = new Date().toISOString()
        }
      } catch (error) {
        console.error('Failed to mark as read: ', error)
      }
    },
    async markAllAsRead() {
      try {
        // await api.patch('/api/notifications/read-all')
        await api.patch('/notifications/read-all')
        this.notifications.forEach((n) => (n.read_at = new Date().toISOString()))
      } catch (error) {
        console.error('Failed to mark all as read:', error)
      }
    },
    async deleteNotification(id) {
      try {
        // await api.delete(`/api/notifications/${id}`)
        await api.delete(`/notifications/${id}`)
        this.notifications = this.notifications.filter((n) => n.id !== id)
      } catch (error) {
        console.error('Failed to delete notification:', error)
      }
    },
    // Called when a real-time notification arrives (later)
    addNotification(notification) {
      this.notifications.unshift(notification)
    },
    async initializeListeners() {
      // Prevent duplicate listeners
      if (this.listenersInitialized) return

      const auth = useAuthStore()
      // Guard against partial auth state (e.g., user object exists but id not yet set,
      // which would subscribe to "user.undefined" and trigger 403 on broadcasting auth)
      if (!auth.authenticated || !auth.user?.id) {
        console.warn('Not authenticated, skipping notification listeners')
        return
      }

      // Ensure CSRF cookie is fresh before /api/broadcasting/auth
      try {
        const { getCsrfToken } = await import('@/services/api')
        await getCsrfToken()
      } catch (_) {}

      try {
        console.log('Subscribing to private channel for user:', auth.user.id)
        console.log(
          'Current XSRF token:',
          (function () {
            const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)
            return m ? decodeURIComponent(m[1]) : null
          })(),
        )

        echo
          .private(`user.${auth.user.id}`)
          .listen('notification.created', (data) => {
            console.log('Real-time notification received:', data)
            // Add notification to the list
            this.notifications.unshift(data)
            // If you have a toast notification system, you can show it here
            // this.showToast(data)
          })
          .listen('notification.read', (data) => {
            console.log('Notification read event:', data)
            const notify = this.notifications.find((n) => n.id === data.id)
            if (notify) {
              notify.read_at = new Date().toISOString()
            }
          })

        this.listenersInitialized = true
        console.log('Notification listeners initialized for user:', auth.user.id)
      } catch (error) {
        console.error('Failed to initialize notification listeners:', error)
      }
    },
  },
})


12. notifications/show.vue::
    <template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="pageTitle" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="Notification Details">
        <div v-if="loading" class="text-center py-8">
          <i class="pi pi-spin pi-spinner text-2xl text-blue-600"></i>
        </div>
        <div v-else-if="notification" class="space-y-4">
          <div class="flex items-center gap-2">
            <span :class="{
              'text-green-600': notification.read_at,
              'text-blue-600': !notification.read_at,
            }">
              {{ notification.read_at ? '✅ Read' : '🔵 Unread' }}
            </span>
            <span class="text-sm text-gray-500">
              {{ formatDate(notification.created_at) }}
            </span>
          </div>
          <div class="p-4 bg-gray-50 rounded-lg">
            <h3 class="text-lg font-semibold">{{ getTitle(notification) }}</h3>
            <p class="text-gray-700 mt-2">{{ getMessage(notification) }}</p>
          </div>
          <div v-if="notification.data?.url">
            <router-link :to="notification.data.url" class="text-blue-600 hover:underline">
              Go to related page
            </router-link>
          </div>
        </div>
      </ComponentCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useNotificationStore } from '@/stores/notifications';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';

const route = useRoute();
const router = useRouter();
const notificationStore = useNotificationStore();
const notification = ref(null);
const loading = ref(false);
const pageTitle = ref('Notification Details');

const loadNotification = async () => {
  loading.value = true;
  try {
    // Since we don't have a single notification endpoint,
    // we fetch all and find by id (or implement a dedicated endpoint)
    await notificationStore.fetchNotifications(1);
    const found = notificationStore.notifications.find(n => n.id === route.params.id);
    if (found) {
      notification.value = found;
      // Mark as read automatically
      await notificationStore.markAsRead(found.id);
    } else {
      router.push('/notifications');
    }
  } catch (error) {
    router.push('/notifications');
  } finally {
    loading.value = false;
  }
};

const formatDate = (dateString) => {
  const d = new Date(dateString);
  return d.toLocaleString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
};

const getTitle = (notify) => {
  const type = notify.type;
  const data = notify.data;
  switch (type) {
    case 'issue_assigned': return 'Issue Assigned';
    case 'comment_added': return 'New Comment';
    case 'status_changed': return 'Status Changed';
    case 'project_member_added': return 'Project Member Added';
    default: return 'Notification';
  }
};

const getMessage = (notify) => {
  // Reuse the same logic from NotificationItem
  // or just return the raw data message.
  return notify.data?.message || 'Notification';
};

onMounted(loadNotification);
</script>


13. services/realtime/issueRealtime.js::
import echo from '../echo'

export function listenToProjectIssues(projectId, callback) {
  return echo.private(`project.${projectId}`).listen('.issue.created', callback)
}
