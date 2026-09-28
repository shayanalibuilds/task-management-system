<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Where signed-in users land. Their current organization wins; the
 * profile is the fallback for users without any workspace.
 */
trait RedirectsAfterAuth
{
    private function intendedDestination(Request $request): string
    {
        $user = $request->user();

        if ($user instanceof User && $user->currentOrganization !== null) {
            return route('org.dashboard', ['organization' => $user->currentOrganization->slug]);
        }

        return route('profile.edit');
    }
}
