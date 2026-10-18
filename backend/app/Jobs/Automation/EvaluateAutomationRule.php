<?php

namespace App\Jobs\Automation;

use App\Models\AutomationRule;
use App\Services\Automation\AutomationExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateAutomationRule implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $ruleId,
        public array $context,
    ) {}

    public function handle(AutomationExecutionService $service): void
    {
        $rule = AutomationRule::find($this->ruleId);

        if (! $rule || ! $rule->is_enabled) {
            return;
        }

        $service->execute($rule, $this->context);
    }
}
