<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLabelRequest;
use App\Http\Requests\UpdateLabelRequest;
use App\Http\Resources\LabelResource;
use App\Models\Label;
use App\Models\Project;
use App\Services\Issues\LabelService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class LabelController extends Controller
{
    public function __construct(
        private readonly LabelService $labels,
    ) {
    }

    public function index(Project $project): AnonymousResourceCollection
    {
        $this->authorize('view', $project);

        return LabelResource::collection($this->labels->listForProject($project));
    }

    public function store(StoreLabelRequest $request, Project $project): LabelResource
    {
        $this->authorize('update', $project);

        $label = $this->labels->create($project, $request->user(), $request->validated());

        return new LabelResource($label);
    }

    public function update(UpdateLabelRequest $request, Label $label): LabelResource
    {
        $this->authorize('update', $label->project);

        return new LabelResource($this->labels->update($label, $request->validated()));
    }

    public function destroy(Label $label): Response
    {
        $this->authorize('update', $label->project);

        $this->labels->delete($label);

        return response()->noContent();
    }
}
