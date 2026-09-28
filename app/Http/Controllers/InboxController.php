<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InboxController extends Controller
{
    public function __invoke(Request $request, Organization $organization): Response
    {
        /** @var User $user */
        $user = $request->user();

        $notifications = Notification::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(50)
            ->with(['actor:id,name', 'task:id,title,project_id'])
            ->get();

        return Inertia::render('Inbox/Index', [
            'notifications' => $notifications->map(fn (Notification $notification): array => [
                'id' => $notification->id,
                'type' => $notification->type,
                'message' => $notification->message,
                'read_at' => $notification->read_at?->toISOString(),
                'actor' => $notification->actor?->name,
                'task' => $notification->task !== null ? [
                    'id' => $notification->task->id,
                    'title' => $notification->task->title,
                    'project_id' => $notification->task->project_id,
                ] : null,
                'created_at' => $notification->created_at->toISOString(),
            ])->all(),
            'unread_count' => $this->unreadCount($organization, $user),
        ]);
    }

    private function unreadCount(Organization $organization, User $user): int
    {
        return Notification::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
