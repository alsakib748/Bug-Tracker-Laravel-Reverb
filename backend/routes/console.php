<?php

use App\Models\AiBugReportSuggestion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:prune-analyses', function () {
    $actionable = ['queued', 'processing', 'ready', 'partially_applied'];

    $expired = AiBugReportSuggestion::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->whereIn('status', $actionable)
        ->update([
            'status' => 'expired',
            'error_code' => 'expired',
            'completed_at' => now(),
        ]);

    $pruned = AiBugReportSuggestion::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now()->subDays(7))
        ->whereIn('status', ['expired', 'dismissed', 'failed', 'applied', 'partially_applied'])
        ->delete();

    $this->info("Expired {$expired} analyses; pruned {$pruned} old records.");
})->purpose('Expire stale AI bug report analyses and prune old ones');

Schedule::command('ai:prune-analyses')->hourly();
