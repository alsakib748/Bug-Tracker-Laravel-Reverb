<?php

namespace App\Providers;

use App\Events\CommentCreated;
use App\Events\IssueAssigned;
use App\Events\IssueCreated;
use App\Events\IssueLabelsUpdated;
use App\Events\IssueStatusChanged;
use App\Events\IssueUpdated;
use App\Integrations\GitHub\Events\GitHubChecksFailed;
use App\Integrations\GitHub\Events\GitHubCommitPushed;
use App\Integrations\GitHub\Events\GitHubPullRequestClosed;
use App\Integrations\GitHub\Events\GitHubPullRequestMerged;
use App\Integrations\GitHub\Events\GitHubPullRequestOpened;
use App\Integrations\GitHub\Events\PullRequestSynced;
use App\Listeners\Automation\DiscoverAutomationRules;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Administrators bypass all authorization checks (gates, policies and
        // the `can:` middleware). This intentionally does not bypass explicit
        // `hasPermissionTo()` checks.
        Gate::before(function (User $user, string $ability) {
            if ($user->hasRole(User::ROLE_ADMIN)) {
                return true;
            }

            return null;
        });

        // Spatie's Role model lives outside App\Models, so register its policy
        // explicitly rather than relying on convention-based discovery.
        Gate::policy(Role::class, RolePolicy::class);

        // Automation engine: turn domain events into queued rule evaluations.
        Event::listen(IssueCreated::class, DiscoverAutomationRules::class);
        Event::listen(IssueUpdated::class, DiscoverAutomationRules::class);
        Event::listen(IssueStatusChanged::class, DiscoverAutomationRules::class);
        Event::listen(IssueAssigned::class, DiscoverAutomationRules::class);
        Event::listen(IssueLabelsUpdated::class, DiscoverAutomationRules::class);
        Event::listen(CommentCreated::class, DiscoverAutomationRules::class);
        Event::listen(PullRequestSynced::class, DiscoverAutomationRules::class);
        Event::listen(GitHubPullRequestOpened::class, DiscoverAutomationRules::class);
        Event::listen(GitHubPullRequestMerged::class, DiscoverAutomationRules::class);
        Event::listen(GitHubPullRequestClosed::class, DiscoverAutomationRules::class);
        Event::listen(GitHubChecksFailed::class, DiscoverAutomationRules::class);
        Event::listen(GitHubCommitPushed::class, DiscoverAutomationRules::class);
    }
}
