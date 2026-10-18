<?php

namespace App\Providers;

use App\Events\CommentCreated;
use App\Events\IssueAssigned;
use App\Events\IssueCreated;
use App\Events\IssueLabelsUpdated;
use App\Events\IssueStatusChanged;
use App\Events\IssueUpdated;
use App\Listeners\Automation\DiscoverAutomationRules;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
        // Automation engine: turn domain events into queued rule evaluations.
        Event::listen(IssueCreated::class, DiscoverAutomationRules::class);
        Event::listen(IssueUpdated::class, DiscoverAutomationRules::class);
        Event::listen(IssueStatusChanged::class, DiscoverAutomationRules::class);
        Event::listen(IssueAssigned::class, DiscoverAutomationRules::class);
        Event::listen(IssueLabelsUpdated::class, DiscoverAutomationRules::class);
        Event::listen(CommentCreated::class, DiscoverAutomationRules::class);
    }
}
