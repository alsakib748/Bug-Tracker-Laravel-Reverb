# Bug Tracker — Complete Audit Fix Plan

Audited against the target architecture guide. 40+ issues found across critical bugs,
security vulnerabilities, missing real-time features, frontend problems, and zero test
coverage. This plan fixes all of them across 5 phases.

**Project layout:** `backend/` (Laravel 13, PHP 8.3) + `frontend/` (Vue 3, Pinia, Echo/Reverb).

---

## PHASE 1 — Critical Backend Bug Fixes (app is broken without these)

### 1.1 `backend/app/Models/User.php` — role helpers always return false
`role` is cast to `UserRole` enum, but helpers compare to strings.
- `isAdmin(): return $this->role === 'admin'` → always false
- `isDeveloper()` / `isTester()` same

**Fix:** compare to enum cases.
```php
public function isAdmin(): bool    { return $this->role === UserRole::ADMIN; }
public function isDeveloper(): bool { return $this->role === UserRole::DEVELOPER; }
public function isTester(): bool    { return $this->role === UserRole::TESTER; }
```
This one bug disables every admin bypass in the app (isVisibleTo, scopes, attachment
delete, dashboard, activity filters, StoreIssueRequest).

### 1.2 `backend/app/Services/IssueService.php` — enum/string bugs
- `changeStatus()`: `IssueStatus::tryFrom($issue->status)` passes an enum object to
  `tryFrom(string|int)` → **TypeError → HTTP 500 on every status change**.
  Fix: `$issue->status` is already the enum; use it directly.
- `changeStatus()`: passes enum objects to `IssueStatusChanged` (typed `string`),
  `sendIssueStatusChanged(string,string)`, `logIssueStatusChanged(string,string)`.
  Fix: pass `->value`.
- `reopenIssue()`: `$issue->status !== IssueStatus::RESOLVED->value` (enum vs string)
  → **always throws**. Fix: `!== IssueStatus::RESOLVED`.
- `closeIssue()`: same pattern → **always throws**. Fix same.
- `assignIssue()`: `$issue->status === IssueStatus::OPEN->value` → never true, so
  open→assigned transition never fires. Fix: `=== IssueStatus::OPEN`.
- `assignIssue()`: sends assignment notification **twice** (once via
  `app(NotificationService::class)`, once via `$this->notificationService`). Remove the
  first call.
- `createIssue()`: typo `$data['due_data']` → due dates never saved. Fix: `due_date`.
- `reopenIssue()` / `closeIssue()`: add real event dispatch, notification, activity log
  (currently `// TODO`).

### 1.3 DB enum mismatch — new migration
`database/migrations/2026_07_30_085542_create_issues_table.php` defines status enum with
`'resolve'`; PHP `IssueStatus` enum + seeder use `'resolved'`. On MySQL, writing
`resolved` fails and `scopeClosed()`/dashboard/delete-guard queries never match.

**Fix:** new migration `2026_xx_xx_fix_issue_status_enum.php` altering the column to
`['open','assigned','in_progress','code_review','testing','resolved','closed','reopened']`.
Also update `DatabaseSeeder` if needed. Use `DB::statement` to alter enum on MySQL, with
a SQLite-safe guard.

### 1.4 `backend/app/Http/Controllers/Api/NotificationController.php`
`markAllAsRead()` calls `->markAsRead()` on `unreadNotifications` (a Collection) — invalid.
**Fix:** `$request->user()->unreadNotifications()->update(['read_at' => now()]);`

### 1.5 `backend/app/Models/Project.php` — missing pivot
`members()` lacks `->withPivot('role','joined_at')->withTimestamps()` (commented out).
Uncomment so `ProjectResource` returns role.

### 1.6 `backend/app/Services/UserService.php` — double hashing
`Hash::make()` + User cast `'password' => 'hashed'` → password hashed twice → created
users can't log in. Fix: pass raw `$data['password']` in `createUser()`/`updateUser()`/
`updateProfile()`.

---

## PHASE 2 — Security & Authorization

### 2.1 Create `backend/app/Policies/` (directory does not exist)
- `ProjectPolicy.php` — viewAny, view, create, update, delete, manageMembers
- `IssuePolicy.php` — viewAny, view, create, update, delete, assign, changeStatus, reopen, close
- `CommentPolicy.php` — view, create, update (owner/admin), delete (owner/admin)
- `AttachmentPolicy.php` — view, create, delete (uploader/admin)
- `UserPolicy.php` — viewAny, create, update, delete (admin only)

Use `isAdmin()` + `isVisibleTo()` + ownership. Register via Laravel auto-discovery
(Policies in `app/Policies` matching model names are discovered automatically in
Laravel 11+; verify) or explicit `Gate::policy()` in `AppServiceProvider`.

### 2.2 Wire authorization in controllers (all `$this->authorize()` are commented out)
- `ProjectController.php` — uncomment/create authorize calls
- `IssueController.php` — uncomment/create authorize calls
- `CommentController.php` — uncomment + **add ownership checks to update/destroy**
  (currently any authenticated user can edit/delete any comment)
- `AttachmentController.php` — uncomment + **add membership check to index/download**
- `UserController.php` — uncomment (admin-only)
- `ProjectMemberController.php` — uncomment (manager/admin gated, not any member)

### 2.3 Broadcast channel authorization — `backend/routes/channels.php`
- `issue.{issueId}` currently returns true for ANY authenticated user → **cross-project
  data leak**. Fix: check `Issue::find()->isVisibleTo($user)`.
- `project.{projectId}` denies admins who aren't members. Fix: mirror `isVisibleTo`.

### 2.4 `backend/app/Http/Requests/UploadAttachmentRequest.php`
Only `file|max:20480`. Add `mimes:jpg,jpeg,png,gif,webp,pdf,txt,log,csv,zip` (prevents
`.php`/`.html`/`.svg` upload to public disk).

### 2.5 `backend/routes/web.php` — remove unauthenticated `/test-broadcast` debug route.

### 2.6 `backend/app/Services/AttachmentService.php` — streamed storage
Replace `file_get_contents($file)` + `Storage::put()` with `$file->store(path, 'public')`.

---

## PHASE 3 — Real-time Completion

### 3.1 `IssueService` reopen/close: dispatch `IssueStatusChanged`, notify, log activity.

### 3.2 `backend/app/Services/ActivityLogService.php`: dispatch `event(new ActivityLogged($log))`
after `ActivityLog::create()` (event currently defined but never dispatched → activity
timeline never updates in real time).

### 3.3 `frontend/src/stores/notifications.js`
- `.listen('notification.created')` → `.listen('.notification.created')` (leading dot to
  match `broadcastAs()`; other listeners use dots).
- Remove `.listen('notification.read')` — no backend event exists.
- **Remove XSRF token console.log** (security).
- Add `stopListeners()` and `echo.leave('user.{id}')`.

### 3.4 Optional: comment update/delete broadcast events (nice-to-have; keep scope sane).

### 3.5 `frontend/src/stores/auth.js` logout: call `echo.leave('user.{id}')` and
`resetCsrfToken()` so session-channel + CSRF state reset.

### 3.6 `frontend/src/main.js`: explicitly import/init Echo service; remove unused
`axios` + `api` imports.

---

## PHASE 4 — Frontend Fixes

### 4.1 `frontend/src/router/index.js`
- Add `/signup` route (Signup.vue exists, Signin links to it, route missing → dead link).
- Add catch-all `/:pathMatch(.*)*` → `Errors/FourZeroFour.vue`.
- Remove/fix `/blank` sidebar links (AppSidebar "Profile"/"System").

### 4.2 Remove ~53 `console.log` statements across `frontend/src` (keep console.error in
catch blocks). Priority: `stores/notifications.js` token log, `stores/dashboard.js`,
`views/Admin/Issues/Show.vue`.

### 4.3 Remove unused imports
- `main.js`: `axios`, `api`
- `stores/users.js`: `toast`, `extractErrorMessage`
- `stores/issues.js`: `useAuthStore`
- `stores/comments.js`: `echo`
- `stores/auth.js`: `resetCsrfToken` (or use it)
- `components/layout/AppHeader.vue`: `NotificationMenu` (template usage commented out)

### 4.4 `frontend/src/services/api.js`
Reset `csrfTokenPromise` on success or explicitly on logout (stale CSRF → 419 after
session expiry). Remove commented dead code.

### 4.5 `frontend/src/stores/projects.js`
`fetchAllProjects` sets `pagination.total = this.projects.length` (wrong). Consolidate
duplicate `fetchProjects`/`fetchAllProjects`.

### 4.6 Delete stray 0-byte file `frontend/src/components/components`.

### 4.7 `frontend/src/stores/comments.js`
Add missing `stopListeners` action (called with `?.` from Show.vue but undefined).

---

## PHASE 5 — Tests

`backend/tests/Feature/` (currently only trivial ExampleTest):
- `IssueLifecycleTest.php` — create → assign → change status → reopen → close;
  assert enum transitions enforce correctly (regression for Phase 1).
- `AuthenticationTest.php` — login/logout, protected routes reject unauthenticated.
- `AuthorizationTest.php` — policies enforce; cross-project access denied; non-admin
  cannot manage users; comment edit/delete ownership.

Use `RefreshDatabase` + SQLite (phpunit.xml already configured). Fix the enum migration
to be SQLite-compatible so tests run.

---

## VERIFICATION
1. `php artisan migrate:fresh --seed` (MySQL) — confirm resolved status seeds OK.
2. `php artisan test` — all feature tests pass.
3. `cd frontend; npx vite build` — build succeeds (eslint config has pre-existing
   missing `@vue/eslint-config-typescript` dependency; note but not caused by us).
4. Manual: two-browser comment/status real-time test; cross-project access denial.

---

## EXECUTION ORDER
Phase 1 (critical) → Phase 2 (security) → Phase 3 (real-time) → Phase 4 (frontend) →
Phase 5 (tests) → verify.

~35 files created/modified.
