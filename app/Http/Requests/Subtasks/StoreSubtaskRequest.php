<?php

declare(strict_types=1);

namespace App\Http\Requests\Subtasks;

use App\Enums\OrganizationRole;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSubtaskRequest extends FormRequest
{
    /**
     * Members and above build checklists.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        $user = $this->user();

        return $project instanceof Project && $user !== null
            && $project->organization->roleFor($user)?->isAtLeast(OrganizationRole::Member) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{title: string}
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array{title: string} $validated */
        $validated = parent::validated($key, $default);

        return ['title' => $validated['title']];
    }
}
