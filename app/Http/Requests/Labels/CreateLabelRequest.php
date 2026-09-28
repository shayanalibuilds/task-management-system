<?php

declare(strict_types=1);

namespace App\Http\Requests\Labels;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

final class CreateLabelRequest extends FormRequest
{
    /**
     * Owners and admins define the organization's label palette.
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
            'name' => ['required', 'string', 'max:40'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    /**
     * @return array{name: string, color: string}
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array{name: string, color: string} $validated */
        $validated = parent::validated($key, $default);

        return ['name' => $validated['name'], 'color' => $validated['color']];
    }
}
