<?php

namespace App\Enums;

enum AutomationExecutionStatus: string
{
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case MATCHED = 'matched';
    case SKIPPED = 'skipped';
    case SUCCEEDED = 'succeeded';
    case PARTIALLY_FAILED = 'partially_failed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Queued',
            self::RUNNING => 'Running',
            self::MATCHED => 'Matched',
            self::SKIPPED => 'Skipped',
            self::SUCCEEDED => 'Succeeded',
            self::PARTIALLY_FAILED => 'Partially failed',
            self::FAILED => 'Failed',
        };
    }
}
