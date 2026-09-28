<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Foreign organizations look exactly like missing ones: 404, never 403.
 */
final class EnsureOrganizationMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $organization = $request->route('organization');

        if (! $user instanceof User
            || ! $organization instanceof Organization
            || ! $organization->hasMember($user->getKey())) {
            abort(404);
        }

        return $next($request);
    }
}
