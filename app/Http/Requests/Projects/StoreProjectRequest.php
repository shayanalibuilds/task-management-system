<?php

declare(strict_types=1);

namespace App\Http\Requests\Projects;

use App\Enums\ProjectVisibility;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreProjectRequest extends FormRequest
{
    /**
     * Owners and admins create projects.
     */
    public function authorize(): bool
    {
        $organization = $this->route('organization');

        return $organization instanceof Organization
            && $this->user()?->can('manage', $organization) === true;
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
