<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Labels\CreateLabel;
use App\Http\Controllers\Controller;
use App\Models\Label;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LabelSettingsController extends Controller
{
    public function __construct(private readonly CreateLabel $createLabel) {}

    public function store(Request $request, Organization $organization): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $organization), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $this->createLabel->handle([
            'organization' => $organization,
            'name' => $validated['name'],
            'color' => $validated['color'],
        ]);

        return back()->with('success', 'Label created.');
    }

    public function update(Request $request, Organization $organization, Label $label): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $organization), 403);
        abort_unless($label->organization_id === $organization->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:40'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $label->update($validated);

        return back()->with('success', 'Label updated.');
    }

    public function destroy(Request $request, Organization $organization, Label $label): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->can('manage', $organization), 403);
        abort_unless($label->organization_id === $organization->id, 404);

        $label->delete();

        return back()->with('success', 'Label deleted.');
    }
}
