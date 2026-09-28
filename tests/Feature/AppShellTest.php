<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('signed-in users are redirected from login to their organization', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();

    $user->forceFill(['current_organization_id' => $organization->id])->save();

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect('/'.$organization->slug);
});

test('users without an organization fall back to their profile', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('login'))
        ->assertRedirect(route('profile.edit'));
});

test('organization pages share the navigation context', function (): void {
    $organization = Organization::factory()->create(['name' => 'Alpha Studio']);
    $user = User::factory()->create();
    $organization->users()->attach($user, ['role' => OrganizationRole::Admin->value]);

    $other = Organization::factory()->create(['name' => 'Zulu Workspace']);
    $other->users()->attach($user, ['role' => OrganizationRole::Member->value]);

    $this->actingAs($user)
        ->get('/'.$organization->slug)
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->component('Dashboard/Index')
                ->where('organization.slug', $organization->slug)
                ->has('auth.organizations', 2)
                ->where('auth.organizations.0.slug', $organization->slug)
                ->where('auth.organizations.1.slug', $other->slug),
        );
});

test('registration lands the new user in their workspace', function (): void {
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'shell@example.com',
        'password' => 'super-secure-passphrase',
        'password_confirmation' => 'super-secure-passphrase',
    ]);

    $response->assertRedirect('/test-user-workspace');
});
