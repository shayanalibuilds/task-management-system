<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    /**
     * Organization home. Real stats land with the dashboard PR.
     */
    public function __invoke(Request $request, Organization $organization): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Dashboard/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'role' => $user->roleIn($organization)?->value,
                'member_count' => $organization->memberships()->count(),
            ],
        ]);
    }
}
