<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use Inertia\Testing\AssertableInertia;

test('guests cannot open my tasks or the calendar', function (): void {
    ['organization' => $organization] = organization_with_member();

    $this->get(route('my-tasks.show', $organization))->assertRedirect(route('login', absolute: false));
    $this->get(route('calendar.show', $organization))->assertRedirect(route('login', absolute: false));
});

test('my tasks and calendar render for viewers', function (): void {
    ['organization' => $organization, 'user' => $viewer] = organization_with_member(OrganizationRole::Viewer);

    $this->actingAs($viewer)
        ->get(route('my-tasks.show', $organization))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->component('MyTasks/Index'));

    $this->actingAs($viewer)
        ->get(route('calendar.show', $organization))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page->component('Calendar/Index'));
});

test('an invalid month falls back to the current month', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();

    $this->actingAs($user)
        ->get(route('calendar.show', ['organization' => $organization, 'month' => 'not-a-month']))
        ->assertOk();
});
