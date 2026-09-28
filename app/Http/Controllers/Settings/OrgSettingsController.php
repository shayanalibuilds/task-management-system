<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OrgSettingsController extends Controller
{
    public function __invoke(Request $request, Organization $organization): Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $organization), 403);

        return Inertia::render('Settings/Organization', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'webhook_url' => $organization->webhook_url,
            ],
            'labels' => $organization->labels()->orderBy('name')->get()->map(fn ($label): array => [
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
            ])->all(),
        ]);
    }
}
