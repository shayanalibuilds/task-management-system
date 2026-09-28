<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class MarkAllReadController extends Controller
{
    public function __invoke(Request $request, Organization $organization): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        Notification::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'Inbox cleared.');
    }
}
