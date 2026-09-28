<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Support\Str;

final class CreateInvite
{
    /**
     * Create a pending invitation for an email to join the organization.
     *
     * @param  array{organization: Organization, email: string, role: OrganizationRole, invited_by: User}  $input
     */
    public function handle(array $input): OrganizationInvite
    {
        return OrganizationInvite::query()->create([
            'organization_id' => $input['organization']->id,
            'email' => $input['email'],
            'role' => $input['role']->value,
            'token' => Str::random(40),
            'invited_by' => $input['invited_by']->getKey(),
        ]);
    }
}
