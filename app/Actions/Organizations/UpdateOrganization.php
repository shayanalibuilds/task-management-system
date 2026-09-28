<?php

declare(strict_types=1);

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Support\DashboardCache;
use Illuminate\Support\Facades\DB;

final class UpdateOrganization
{
    /**
     * @param  array{organization: Organization, name: string, slug: string, webhook_url?: ?string}  $input
     */
    public function handle(array $input): Organization
    {
        $organization = $input['organization'];

        $organization = DB::transaction(function () use ($organization, $input): Organization {
            $organization->update([
                'name' => $input['name'],
                'slug' => $input['slug'],
                'webhook_url' => $input['webhook_url'] ?? null,
            ]);

            return $organization;
        });

        DashboardCache::bust($organization->id);

        return $organization;
    }
}
