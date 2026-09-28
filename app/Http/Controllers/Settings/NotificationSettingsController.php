<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class NotificationSettingsController extends Controller
{
    public function edit(Request $request, Organization $organization): Response
    {
        /** @var User $user */
        $user = $request->user();
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();

        return Inertia::render('Settings/Notifications', [
            'email_prefs' => $this->prefs($membership->email_prefs),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $membership = $organization->memberships()->where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'email_mentions' => ['required', 'boolean'],
            'email_assignments' => ['required', 'boolean'],
            'email_comments' => ['required', 'boolean'],
            'email_due_reminders' => ['required', 'boolean'],
        ]);

        $membership->update([
            'email_prefs' => [
                'mention' => (bool) $validated['email_mentions'],
                'assignment' => (bool) $validated['email_assignments'],
                'comment' => (bool) $validated['email_comments'],
                'due_reminder' => (bool) $validated['email_due_reminders'],
            ],
        ]);

        return back()->with('success', 'Notification settings saved.');
    }

    /**
     * @return array<string, bool>
     */
    private function prefs(mixed $stored): array
    {
        $decoded = is_string($stored) ? (array) json_decode($stored, true) : (is_array($stored) ? $stored : []);

        return [
            'mention' => (bool) ($decoded['mention'] ?? true),
            'assignment' => (bool) ($decoded['assignment'] ?? true),
            'comment' => (bool) ($decoded['comment'] ?? true),
            'due_reminder' => (bool) ($decoded['due_reminder'] ?? true),
        ];
    }
}
