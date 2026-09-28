<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Test helpers
|--------------------------------------------------------------------------
|
| Shared factories for the tenancy layer. Every organization test needs a
| user attached with a role, so one helper keeps those in sync.
|
*/

if (! function_exists('organization_with_member')) {
    /**
     * Create an organization with one member in the given role.
     *
     * @return array{organization: Organization, user: User}
     */
    function organization_with_member(OrganizationRole $role = OrganizationRole::Owner): array
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $organization->users()->attach($user, ['role' => $role->value]);

        return ['organization' => $organization, 'user' => $user];
    }
}

if (! function_exists('attach_member')) {
    /**
     * Attach an extra member to an organization and return the user.
     */
    function attach_member(Organization $organization, OrganizationRole $role = OrganizationRole::Member): User
    {
        $user = User::factory()->create();

        $organization->users()->attach($user, ['role' => $role->value]);

        return $user;
    }
}
