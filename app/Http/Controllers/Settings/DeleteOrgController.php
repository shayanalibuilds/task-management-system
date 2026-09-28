<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Organizations\DeleteOrganization;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DeleteOrgController extends Controller
{
    public function __construct(private readonly DeleteOrganization $deleteOrganization) {}

    public function __invoke(Request $request, Organization $organization): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $role = $organization->roleFor($user);

        abort_unless($role?->isAtLeast(OrganizationRole::Owner) ?? false, 403);

        $this->deleteOrganization->handle([
            'organization' => $organization,
            'user' => $user,
        ]);

        return redirect()->route('home');
    }
}
