<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenancy;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Rules\NotAnOrganizationMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class StoreMemberInviteRequest extends FormRequest
{
    /**
     * Only owners and admins may invite.
     */
    public function authorize(): bool
    {
        $organization = $this->route('organization');

        return $organization instanceof Organization
            && $this->user()?->can('invite', $organization) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $organization = $this->route('organization');
        $organizationId = $organization instanceof Organization ? $organization->id : 0;

        return [
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('organization_invites')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
                new NotAnOrganizationMember($organizationId),
            ],
            'role' => ['required', new Enum(OrganizationRole::class)],
        ];
    }

    /**
     * @return array{email: string, role: OrganizationRole}
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array{email: string, role: string} $validated */
        $validated = parent::validated($key, $default);

        return [
            'email' => $validated['email'],
            'role' => OrganizationRole::from($validated['role']),
        ];
    }
}
