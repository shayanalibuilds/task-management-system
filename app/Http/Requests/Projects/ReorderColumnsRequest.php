<?php

declare(strict_types=1);

namespace App\Http\Requests\Projects;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReorderColumnsRequest extends FormRequest
{
    /**
     * Owners and admins manage columns.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && $this->user()?->can('manage', $project) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->id : 0;

        return [
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => [
                'integer',
                Rule::exists('project_columns', 'id')->where('project_id', $projectId),
            ],
        ];
    }

    /**
     * @return array{columns: list<int>}
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array{columns: list<int|string>} $validated */
        $validated = parent::validated($key, $default);

        return [
            'columns' => array_map(intval(...), $validated['columns']),
        ];
    }
}
