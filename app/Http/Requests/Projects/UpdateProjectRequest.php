<?php

declare(strict_types=1);

namespace App\Http\Requests\Projects;

use App\Enums\ProjectVisibility;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateProjectRequest extends FormRequest
{
    /**
     * Owners and admins run project settings.
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
        return [
            'name' => ['required', 'string', 'max:120'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['required', 'string', 'max:32'],
            'visibility' => ['required', new Enum(ProjectVisibility::class)],
        ];
    }

    /**
     * @return array{name: string, color: string, icon: string, visibility: ProjectVisibility}
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array{name: string, color: string, icon: string, visibility: string} $validated */
        $validated = parent::validated($key, $default);

        return [
            'name' => $validated['name'],
            'color' => $validated['color'],
            'icon' => $validated['icon'],
            'visibility' => ProjectVisibility::from($validated['visibility']),
        ];
    }
}
