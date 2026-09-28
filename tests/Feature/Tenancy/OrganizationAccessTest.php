<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('renders the organization home for a member', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();

    $this->actingAs($user)
        ->get('/'.$organization->slug)
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Dashboard/Index')
                ->where('organization.name', $organization->name)
                ->where('organization.slug', $organization->slug)
                ->where('organization.role', 'owner'),
        );
});

test('a signed-in user cannot open an organization they do not belong to', function (): void {
    $organization = Organization::factory()->create();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get('/'.$organization->slug)
        ->assertNotFound();
});

test('membership in one organization does not grant access to another', function (): void {
    ['user' => $member] = organization_with_member();
    $foreign = Organization::factory()->create();

    $this->actingAs($member)
        ->get('/'.$foreign->slug)
        ->assertNotFound();
});

test('unknown organization slugs are not found', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/does-not-exist')->assertNotFound();
});

test('guests are redirected to login from organization routes', function (): void {
    $organization = Organization::factory()->create();

    $this->get('/'.$organization->slug)
        ->assertRedirect(route('login', absolute: false));
});
