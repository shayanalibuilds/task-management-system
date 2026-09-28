<?php

declare(strict_types=1);

namespace App\Actions\Tenancy;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateOrganization
{
    /**
     * Create an organization, attach the owner and set their current organization.
     *
     * @param  array{name: string, owner: User, slug?: string}  $input
     */
    public function handle(array $input): Organization
    {
        $owner = $input['owner'];

        $organization = DB::transaction(function () use ($input, $owner): Organization {
            $organization = Organization::query()->create([
                'name' => $input['name'],
                'slug' => $this->uniqueSlug($input['slug'] ?? $input['name']),
            ]);

            $organization->users()->attach($owner->getKey(), ['role' => OrganizationRole::Owner->value]);

            $owner->forceFill(['current_organization_id' => $organization->id])->save();

            return $organization;
        });

        return $organization;
    }

    /**
     * Turn a name into a slug nobody else uses.
     */
    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source);

        if ($base === '') {
            $base = 'organization';
        }

        $slug = $base;
        $suffix = 2;

        while (Organization::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
