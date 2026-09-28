<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

final class OrganizationPolicy
{
    /**
     * Owners and admins manage the roster.
     */
    public function invite(User $user, Organization $organization): bool
    {
        return $organization->roleFor($user)?->canManage() ?? false;
    }

    /**
     * Owners and admins run the organization.
     */
    public function manage(User $user, Organization $organization): bool
    {
        return $organization->roleFor($user)?->canManage() ?? false;
    }

    /**
     * Members may leave; the last owner may not.
     */
    public function leave(User $user, Organization $organization): bool
    {
        $role = $organization->roleFor($user);

        if ($role === null) {
            return false;
        }

        if (! $role->isAtLeast(OrganizationRole::Owner)) {
            return true;
        }

        return $organization->owners()->count() > 1;
    }
}
