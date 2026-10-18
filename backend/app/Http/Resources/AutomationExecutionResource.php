<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationExecutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'automation_rule_id' => $this->automation_rule_id,
            'rule_version' => $this->rule_version,
            'project_id' => $this->project_id,
            'event_id' => $this->event_id,
            'event_type' => $this->event_type,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'status' => $this->status,
            'matched_conditions' => $this->matched_conditions,
            'error_code' => $this->error_code,
            'started_at' => $this->started_at?->toISOString(),
            'finished_at' => $this->finished_at?->toISOString(),
            'actions' => $this->whenLoaded('actions', fn () => $this->actions->map(fn ($action) => [
                'action_key' => $action->action_key,
                'action_type' => $action->action_type,
                'status' => $action->status,
                'attempt_count' => $action->attempt_count,
                'error_code' => $action->error_code,
                'input_summary' => $action->input_summary,
                'result_summary' => $action->result_summary,
                'started_at' => $action->started_at?->toISOString(),
                'finished_at' => $action->finished_at?->toISOString(),
            ])),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
