<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use Illuminate\Validation\ValidationException;

final class RemoveMember
{
    /**
     * Removing a member must never leave the workspace without an owner.
     *
     * @param  array{organization: Organization, membership_id: int}  $input
     */
    public function handle(array $input): void
    {
        $organization = $input['organization'];

        $membership = $organization->memberships()
            ->whereKey($input['membership_id'])
            ->firstOrFail();

        if ($membership->role === OrganizationRole::Owner && $organization->owners()->count() <= 1) {
            throw ValidationException::withMessages([
                'membership' => 'The workspace needs at least one owner. Promote another owner first.',
            ]);
        }

        $membership->delete();
    }
}
