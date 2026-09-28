<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Organizations\UpdateOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;

final class UpdateOrgSettingsController extends Controller
{
    public function __construct(private readonly UpdateOrganization $updateOrganization) {}

    public function __invoke(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validated();

        $this->updateOrganization->handle([
            'organization' => $organization,
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'webhook_url' => $validated['webhook_url'],
        ]);

        return back()->with('success', 'Workspace updated.');
    }
}
