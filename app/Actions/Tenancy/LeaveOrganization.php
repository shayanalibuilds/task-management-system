<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class LeaveOrganization
{
    /**
     * Remove the user's membership and re-point their current organization.
     *
     * @param  array{organization: Organization, user: User}  $input
     */
    public function handle(array $input): void
    {
        $organization = $input['organization'];
        $user = $input['user'];

        DB::transaction(function () use ($organization, $user): void {
            $organization->memberships()
                ->where('user_id', $user->getKey())
                ->delete();

            if ($user->current_organization_id === $organization->id) {
                $next = $user->organizations()
                    ->whereKeyNot($organization->id)
                    ->orderBy('name')
                    ->first();

                $user->forceFill([
                    'current_organization_id' => $next?->id,
                ])->save();
            }
        });
    }
}
