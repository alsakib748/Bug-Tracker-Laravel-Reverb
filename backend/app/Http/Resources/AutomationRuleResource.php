<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutomationRuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'description' => $this->description,
            'trigger_type' => $this->trigger_type,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'is_enabled' => (bool) $this->is_enabled,
            'run_on_actor_changes' => (bool) $this->run_on_actor_changes,
            'cooldown_seconds' => $this->cooldown_seconds,
            'version' => $this->version,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'executions_count' => $this->whenCounted('executions'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
