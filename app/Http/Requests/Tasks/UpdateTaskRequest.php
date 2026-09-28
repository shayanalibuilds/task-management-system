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

final class UpdateTaskRequest extends FormRequest
{
    /**
     * Members and above shape the board.
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
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'priority' => ['sometimes', 'nullable', new Enum(TaskPriority::class)],
            'assignee_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->whereIn('id', function ($query) use ($organizationId): void {
                $query->select('user_id')->from('memberships')->where('organization_id', $organizationId);
            })],
            'start_on' => ['sometimes', 'nullable', 'date'],
            'due_on' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_on'],
            'position_after' => ['sometimes', 'nullable', 'numeric'],
        ];
    }

    private function project(): Project
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $project;
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array<string, mixed> $validated */
        $validated = parent::validated($key, $default);

        $resolved = [];

        $resolved['column_id'] = (int) $validated['column_id'];

        if (array_key_exists('title', $validated)) {
            $resolved['title'] = (string) $validated['title'];
        }

        if (array_key_exists('description', $validated)) {
            $resolved['description'] = $validated['description'];
        }

        if (array_key_exists('priority', $validated)) {
            $resolved['priority'] = $validated['priority'] === null
                ? TaskPriority::None
                : TaskPriority::from((string) $validated['priority']);
        }

        if (array_key_exists('assignee_id', $validated)) {
            $resolved['assignee'] = $validated['assignee_id'] === null
                ? null
                : User::query()->findOrFail((int) $validated['assignee_id']);
        }

        if (array_key_exists('start_on', $validated)) {
            $resolved['start_on'] = $validated['start_on'];
        }

        if (array_key_exists('due_on', $validated)) {
            $resolved['due_on'] = $validated['due_on'];
        }

        if (array_key_exists('position_after', $validated)) {
            $resolved['position_after'] = $validated['position_after'] === null
                ? null
                : (float) $validated['position_after'];
        }

        return $resolved;
    }
}
