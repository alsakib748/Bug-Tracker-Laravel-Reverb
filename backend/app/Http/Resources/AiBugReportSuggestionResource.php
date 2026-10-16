<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiBugReportSuggestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'issue_id' => $this->issue_id,
            'status' => $this->status,
            'suggestions' => $this->when(
                $this->isReviewable(),
                fn () => $this->suggestions,
            ),
            'review_decisions' => $this->when(
                $this->review_decisions !== null,
                fn () => $this->review_decisions,
            ),
            'applied_fields' => $this->when(
                $this->applied_fields !== null,
                fn () => $this->applied_fields,
            ),
            'error_code' => $this->error_code,
            'provider' => $this->provider,
            'model' => $this->model,
            'input_tokens' => $this->input_tokens,
            'output_tokens' => $this->output_tokens,
            'created_at' => $this->created_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
        ];
    }
}
