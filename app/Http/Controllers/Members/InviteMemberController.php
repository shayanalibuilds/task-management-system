<?php

declare(strict_types=1);

namespace App\Http\Controllers\Members;

use App\Actions\Tenancy\CreateInvite;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenancy\StoreMemberInviteRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class InviteMemberController extends Controller
{
    public function __construct(private readonly CreateInvite $createInvite) {}

    public function __invoke(StoreMemberInviteRequest $request, Organization $organization): RedirectResponse
    {
        /** @var User $invitedBy */
        $invitedBy = $request->user();
        $validated = $request->validated();

        $this->createInvite->handle([
            'organization' => $organization,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'invited_by' => $invitedBy,
        ]);

        return back()->with('success', 'Invite sent to '.$validated['email'].'.');
    }
}
