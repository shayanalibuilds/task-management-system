<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrganizationRequest extends FormRequest
{
    /**
     * Owners and admins run the workspace.
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
        $organization = $this->route('organization');
        $organizationId = $organization instanceof Organization ? $organization->id : 0;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('organizations', 'slug')->ignore($organizationId),
            ],
            'webhook_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    /**
     * @return array{name: string, slug: string, webhook_url: ?string}
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array{name: string, slug: string, webhook_url?: ?string} $validated */
        $validated = parent::validated($key, $default);

        return [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'webhook_url' => $validated['webhook_url'] ?? null,
        ];
    }
}
