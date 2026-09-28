<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use Inertia\Testing\AssertableInertia;

test('guests cannot open the dashboard', function (): void {
    ['organization' => $organization] = organization_with_member();

    $this->get('/'.$organization->slug)->assertRedirect(route('login', absolute: false));
});

test('viewers see the dashboard without write affordances', function (): void {
    ['organization' => $organization, 'user' => $viewer] = organization_with_member(OrganizationRole::Viewer);

    $this->actingAs($viewer)
        ->get('/'.$organization->slug)
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('organization.role', 'viewer')
                ->where('stats.open_tasks', 0),
        );
});

test('empty organizations render a zeroed dashboard', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();

    $this->actingAs($user)
        ->get('/'.$organization->slug)
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('stats.open_tasks', 0)
                ->where('stats.overdue', 0)
                ->where('today', [])
                ->where('due_soon', []),
        );
});

test('creating a project through the action refreshes dashboard numbers', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();

    $this->actingAs($user)
        ->post(route('projects.store', $organization), [
            'name' => 'Fresh Project',
            'color' => '#4F46E5',
            'icon' => 'folder',
            'visibility' => 'open',
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->get('/'.$organization->slug)
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->where('recent_projects.0.name', 'Fresh Project'),
        );
});
