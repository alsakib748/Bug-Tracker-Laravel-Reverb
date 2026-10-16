<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentIssueId = (int) ($request->route('issue')?->id ?? 0);

        $isSource = (int) $this->source_issue_id === $currentIssueId;
        $other = $isSource ? $this->targetIssue : $this->sourceIssue;
        $type = $this->link_type;

        return [
            'id' => $this->id,
            'source_issue_id' => $this->source_issue_id,
            'target_issue_id' => $this->target_issue_id,
            'link_type' => $type?->value,
            'label' => $isSource ? $type?->label() : $type?->inverseLabel(),
            'direction' => $isSource ? 'outgoing' : 'incoming',
            'issue' => $other ? new IssueMiniResource($other) : null,
            'created_at' => $this->created_at?->toISOString(),
            'created_by' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
        ];
    }
}
