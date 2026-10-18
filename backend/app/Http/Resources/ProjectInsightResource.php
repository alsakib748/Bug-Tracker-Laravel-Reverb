<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectInsightResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'requested_by' => $this->requested_by,
            'insight_type' => $this->insight_type,
            'period_start' => $this->period_start->toDateString(),
            'period_end' => $this->period_end->toDateString(),
            'status' => $this->status,
            'result' => $this->when($this->isCompleted(), fn () => $this->result_json),
            'metrics_snapshot' => $this->when($this->isCompleted(), fn () => $this->metrics_snapshot),
            'error_code' => $this->error_code,
            'model_provider' => $this->model_provider,
            'model_name' => $this->model_name,
            'is_expired' => $this->isCompleted() && $this->isExpired(),
            'generated_at' => $this->generated_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
