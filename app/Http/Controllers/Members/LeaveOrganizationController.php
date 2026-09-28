<?php

declare(strict_types=1);

namespace App\Http\Controllers\Members;

use App\Actions\Tenancy\LeaveOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenancy\LeaveOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class LeaveOrganizationController extends Controller
{
    public function __construct(private readonly LeaveOrganization $leaveOrganization) {}

    public function __invoke(LeaveOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->leaveOrganization->handle([
            'organization' => $organization,
            'user' => $user,
        ]);

        return redirect()->route('home');
    }
}
