<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Organizations\ChangeMemberRole;
use App\Actions\Organizations\RemoveMember;
use App\Enums\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MembersSettingsController extends Controller
{
    public function __construct(
        private readonly ChangeMemberRole $changeMemberRole,
        private readonly RemoveMember $removeMember,
    ) {}

    public function index(Request $request, Organization $organization): Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $organization), 403);

        $members = $organization->users()
            ->orderBy('name')
            ->get()
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $organization->roleFor($member)?->value,
                'membership_id' => $organization->memberships()->where('user_id', $member->id)->value('id'),
            ]);

        $invites = $organization->invites()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($invite): array => [
                'id' => $invite->id,
                'email' => $invite->email,
                'role' => $invite->role->value,
                'token' => $invite->token,
            ]);

        return Inertia::render('Settings/Members', [
            'members' => $members,
            'invites' => $invites,
        ]);
    }

    public function update(Request $request, Organization $organization, int $membershipId): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $organization), 403);

        $validated = $request->validate([
            'role' => ['required', 'in:owner,admin,member,viewer'],
        ]);

        $this->changeMemberRole->handle([
            'organization' => $organization,
            'membership_id' => $membershipId,
            'role' => OrganizationRole::from($validated['role']),
            'actor' => $user,
        ]);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Organization $organization, int $membershipId): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $organization), 403);

        $this->removeMember->handle([
            'organization' => $organization,
            'membership_id' => $membershipId,
        ]);

        return back()->with('success', 'Member removed.');
    }
}
