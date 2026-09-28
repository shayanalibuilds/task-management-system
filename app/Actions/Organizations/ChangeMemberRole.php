<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class ChangeMemberRole
{
    /**
     * Role changes must always leave at least one owner.
     *
     * @param  array{organization: Organization, membership_id: int, role: OrganizationRole, actor: User}  $input
     */
    public function handle(array $input): void
    {
        $organization = $input['organization'];

        $membership = $organization->memberships()
            ->whereKey($input['membership_id'])
            ->firstOrFail();

        $currentRole = $membership->role;
        $nextRole = $input['role'];

        if ($currentRole === $nextRole) {
            return;
        }

        if ($currentRole === OrganizationRole::Owner && $nextRole !== OrganizationRole::Owner) {
            $ownerCount = $organization->owners()->count();

            if ($ownerCount <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'The workspace needs at least one owner. Promote another owner first.',
                ]);
            }
        }

        $membership->update(['role' => $nextRole->value]);
    }
}
