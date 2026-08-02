<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{

    // public function getAllProjects(array $filters = [], int $perPage = 15): LengthAwarePaginator
    // {
    //     $query = Project::query()
    //         ->with(['creator', 'members'])
    //         ->when($filters['search'] ?? null, function ($q, $search) {
    //             $q->where('name', 'like', "%{$search}%")
    //                 ->orWhere('code', 'like', "%{$search}%");
    //         })
    //         ->when($filters['status'] ?? null, function ($q, $status) {
    //             $q->where('status', $status);
    //         });

    //     return $query->paginate($perPage);
    // }

    public function getAllProjects()
    {
        $projects = Project::with(['creator', 'members'])->orderBy('id', 'desc')->get();

        return $projects;
    }

    public function createProject(array $data): Project
    {
        $project = Project::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? null,
            'status' => $data['status'] ?? 'active',
            'created_by' => auth()->id(),
        ]);

        $project->members()->attach(auth()->id(), [
            'role' => 'manager',
            'joined_at' => now(),
        ]);

        return $project->fresh(['creator', 'members']);

    }

    public function getProject(Project $project): Project
    {
        return $project->load(['creator', 'members', 'issues']);
    }

    public function updateProject(Project $project, array $data): Project
    {
        $project->update([
            'name' => $data['name'] ?? $project->name,
            'code' => $data['code'] ?? $project->code,
            'description' => $data['description'] ?? $project->description,
            'color' => $data['color'] ?? $project->color,
            'status' => $data['status'] ?? $project->status,
        ]);

        return $project->fresh(['creator', 'members']);

    }

    public function deleteProject(Project $project): void
    {
        if ($project->issues()->whereNotIn('status', ['resolved', 'closed'])->exists()) {
            throw new \Exception('Cannot delete project with open issues.');
        }

        $project->delete();
    }

}
