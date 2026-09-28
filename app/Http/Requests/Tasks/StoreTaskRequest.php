<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Enums\OrganizationRole;
use App\Enums\TaskPriority;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreTaskRequest extends FormRequest
{
    /**
     * Members and above create tasks.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && $project->organization->roleFor($this->user())?->isAtLeast(OrganizationRole::Member) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = $this->route('organization');
        $organizationId = $organization instanceof Organization ? $organization->id : 0;

        return [
            'column_id' => ['required', 'integer', Rule::exists('project_columns', 'id')->where('project_id', $this->project()->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['nullable', new Enum(TaskPriority::class)],
            'assignee_id' => ['nullable', Rule::exists('users', 'id')->whereIn('id', function ($query) use ($organizationId): void {
                $query->select('user_id')->from('memberships')->where('organization_id', $organizationId);
            })],
            'start_on' => ['nullable', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:start_on'],
        ];
    }

    private function project(): Project
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $project;
    }

    /**
     * @return array{column_id: int, title: string, description: ?string, priority: TaskPriority, assignee: ?User, start_on: ?string, due_on: ?string}
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array{column_id: int, title: string, description?: ?string, priority?: ?string, assignee_id?: ?int, start_on?: ?string, due_on?: ?string} $validated */
        $validated = parent::validated($key, $default);

        $assignee = isset($validated['assignee_id'])
            ? User::query()->findOrFail($validated['assignee_id'])
            : null;

        return [
            'column_id' => (int) $validated['column_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => isset($validated['priority']) ? TaskPriority::from($validated['priority']) : TaskPriority::None,
            'assignee' => $assignee,
            'start_on' => $validated['start_on'] ?? null,
            'due_on' => $validated['due_on'] ?? null,
        ];
    }
}
