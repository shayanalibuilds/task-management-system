<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Notification;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version for cache busting.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default across every page.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
            ],
            'auth' => [
                'user' => fn (): ?array => $request->user()?->only(
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                ),
                'organizations' => fn (): array => $this->organizationsFor($request),
            ],
            'organization' => fn (): ?array => $this->currentOrganization($request),
            'inbox' => [
                'unread_count' => fn (): ?int => $this->unreadCount($request),
            ],
            'status' => fn (): ?string => $request->session()->get('status'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }

    /**
     * Unread inbox rows for the route organization, when on an org page.
     */
    private function unreadCount(Request $request): ?int
    {
        $user = $request->user();
        $organization = $request->route('organization');

        if (! $user instanceof \App\Models\User || ! $organization instanceof Organization) {
            return null;
        }

        return (int) Notification::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->getKey())
            ->whereNull('read_at')
            ->count();
    }

    /**
     * The user's organizations for the switcher, ordered by name.
     *
     * @return list<array{id: int, name: string, slug: string}>
     */
    private function organizationsFor(Request $request): array
    {
        $user = $request->user();

        if ($user === null) {
            return [];
        }

        return $user->organizations()
            ->orderBy('name')
            ->get()
            ->map(fn (Organization $organization): array => $organization->only('id', 'name', 'slug'))
            ->all();
    }

    /**
     * The organization addressed by the current route, when on an org page.
     *
     * @return array{id: int, name: string, slug: string}|null
     */
    private function currentOrganization(Request $request): ?array
    {
        $organization = $request->route('organization');

        if (! $organization instanceof Organization) {
            return null;
        }

        return $organization->only('id', 'name', 'slug');
    }
}
