<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DeleteOrganization
{
    /**
     * Projects, columns, tasks, memberships and invites fall through
     * cascade deletes.
     *
     * @param  array{organization: Organization, user: User}  $input
     */
    public function handle(array $input): void
    {
        $organization = $input['organization'];
        $user = $input['user'];

        DB::transaction(function () use ($organization, $user): void {
            $organization->delete();

            if ($user->current_organization_id === $organization->id) {
                $next = $user->organizations()->first();

                $user->forceFill([
                    'current_organization_id' => $next?->id,
                ])->save();
            }
        });
    }
}
