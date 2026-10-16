<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueMiniResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'issue_key' => $this->issue_key ?? ('ISS-' . $this->id),
            'title' => $this->title,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
        ];
    }
}
