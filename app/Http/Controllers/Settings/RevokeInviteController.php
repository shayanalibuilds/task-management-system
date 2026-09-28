<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RevokeInviteController extends Controller
{
    public function __invoke(Request $request, Organization $organization, OrganizationInvite $invite): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $organization), 403);
        abort_unless($invite->organization_id === $organization->id, 404);

        $invite->delete();

        return back()->with('success', 'Invite revoked.');
    }
}
