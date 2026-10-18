<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Automation\StoreAutomationRuleRequest;
use App\Http\Requests\Automation\UpdateAutomationRuleRequest;
use App\Http\Resources\AutomationExecutionResource;
use App\Http\Resources\AutomationRuleResource;
use App\Models\AutomationRule;
use App\Models\Project;
use App\Services\Automation\AutomationRuleValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AutomationRuleController extends Controller
{
    public function __construct(
        private readonly AutomationRuleValidator $validator,
    ) {}

    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        return AutomationRuleResource::collection(
            $project->automationRules()
                ->withCount('executions')
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function store(StoreAutomationRuleRequest $request, Project $project): AutomationRuleResource
    {
        $this->authorize('update', $project);

        // Normalize from the raw payload: `validated()` would drop nested keys
        // (such as condition `value`) that carry no explicit validation rule.
        $validated = $this->validator->validate($project, $request->all());

        $rule = AutomationRule::create([
            'project_id' => $project->id,
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'trigger_type' => $validated['trigger_type'],
            'conditions' => $validated['conditions'],
            'actions' => $validated['actions'],
            'is_enabled' => $request->boolean('is_enabled', false),
            'run_on_actor_changes' => $request->boolean('run_on_actor_changes', false),
            'cooldown_seconds' => $request->validated('cooldown_seconds'),
            'created_by' => $request->user()->id,
            'version' => 1,
        ]);

        return new AutomationRuleResource($rule);
    }

    public function show(AutomationRule $rule): AutomationRuleResource
    {
        $this->authorize('view', $rule->project);

        return new AutomationRuleResource($rule->loadCount('executions'));
    }

    public function update(UpdateAutomationRuleRequest $request, AutomationRule $rule): AutomationRuleResource
    {
        $this->authorize('update', $rule->project);

        $payload = array_merge([
            'trigger_type' => $rule->trigger_type,
            'conditions' => $rule->conditions,
            'actions' => $rule->actions,
        ], $request->all());

        $validated = $this->validator->validate($rule->project, $payload);

        $data = $request->validated();

        $rule->fill([
            'name' => $data['name'] ?? $rule->name,
            'description' => array_key_exists('description', $data) ? $data['description'] : $rule->description,
            'is_enabled' => array_key_exists('is_enabled', $data) ? (bool) $data['is_enabled'] : $rule->is_enabled,
            'run_on_actor_changes' => array_key_exists('run_on_actor_changes', $data)
                ? (bool) $data['run_on_actor_changes']
                : $rule->run_on_actor_changes,
            'cooldown_seconds' => array_key_exists('cooldown_seconds', $data) ? $data['cooldown_seconds'] : $rule->cooldown_seconds,
        ]);

        $rule->trigger_type = $validated['trigger_type'];
        $rule->conditions = $validated['conditions'];
        $rule->actions = $validated['actions'];
        $rule->updated_by = $request->user()->id;
        $rule->version = $rule->version + 1;
        $rule->save();

        return new AutomationRuleResource($rule->fresh()->loadCount('executions'));
    }

    public function enable(AutomationRule $rule): AutomationRuleResource
    {
        $this->authorize('update', $rule->project);

        $rule->update(['is_enabled' => true]);

        return new AutomationRuleResource($rule);
    }

    public function disable(AutomationRule $rule): AutomationRuleResource
    {
        $this->authorize('update', $rule->project);

        $rule->update(['is_enabled' => false]);

        return new AutomationRuleResource($rule);
    }

    public function destroy(AutomationRule $rule): JsonResponse
    {
        $this->authorize('update', $rule->project);

        $rule->delete();

        return response()->json(['message' => 'Rule deleted.']);
    }

    public function executions(Request $request, Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        $executions = $project->automationExecutions()
            ->with('actions')
            ->when($request->integer('rule_id'), fn ($query, $ruleId) => $query->where('automation_rule_id', $ruleId))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(20);

        return AutomationExecutionResource::collection($executions);
    }
}
