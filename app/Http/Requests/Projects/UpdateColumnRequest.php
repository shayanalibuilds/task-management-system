<?php

declare(strict_types=1);

namespace App\Http\Requests\Projects;

use App\Enums\ColumnCategory;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateColumnRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:60'],
            'category' => ['required', new Enum(ColumnCategory::class)],
        ];
    }

    /**
     * @return array{name: string, category: ColumnCategory}
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array{name: string, category: string} $validated */
        $validated = parent::validated($key, $default);

        return [
            'name' => $validated['name'],
            'category' => ColumnCategory::from($validated['category']),
        ];
    }
}
