<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationInvite;
use App\Models\User;

test('registering creates a personal organization owned by the new user', function (): void {
    $this->post(route('register'), [
        'name' => 'Dana Fields',
        'email' => 'dana@example.com',
        'password' => 'super-secure-passphrase',
        'password_confirmation' => 'super-secure-passphrase',
    ]);

    $user = User::query()->where('email', 'dana@example.com')->firstOrFail();
    $organization = $user->organizations()->first();

    expect($organization)->not->toBeNull()
        ->and($organization->slug)->toBe('dana-fields-workspace')
        ->and($user->fresh()->current_organization_id)->toBe($organization->id)
        ->and($organization->roleFor($user))->toBe(OrganizationRole::Owner);
});

test('the last owner cannot leave their organization', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member(OrganizationRole::Owner);

    $this->actingAs($owner)
        ->post(route('members.leave', $organization))
        ->assertForbidden();

    expect($organization->fresh()->hasMember($owner->id))->toBeTrue();
});

test('an owner can leave when another owner remains', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member(OrganizationRole::Owner);
    attach_member($organization, OrganizationRole::Owner);

    $this->actingAs($owner)
        ->post(route('members.leave', $organization))
        ->assertRedirect(route('home', absolute: false));

    expect($organization->fresh()->hasMember($owner->id))->toBeFalse();
});

test('a member can leave their organization', function (): void {
    ['organization' => $organization, 'user' => $member] = organization_with_member(OrganizationRole::Member);

    $this->actingAs($member)
        ->post(route('members.leave', $organization))
        ->assertRedirect(route('home', absolute: false));

    expect($organization->fresh()->hasMember($member->id))->toBeFalse();
});

test('a viewer cannot invite members', function (): void {
    ['organization' => $organization, 'user' => $viewer] = organization_with_member(OrganizationRole::Viewer);

    $this->actingAs($viewer)
        ->post(route('members.invites.store', $organization), [
            'email' => 'newcomer@example.com',
            'role' => 'member',
        ])
        ->assertForbidden();

    expect(OrganizationInvite::query()->count())->toBe(0);
});

test('a member cannot invite members', function (): void {
    ['organization' => $organization, 'user' => $member] = organization_with_member(OrganizationRole::Member);

    $this->actingAs($member)
        ->post(route('members.invites.store', $organization), [
            'email' => 'newcomer@example.com',
            'role' => 'member',
        ])
        ->assertForbidden();

    expect(OrganizationInvite::query()->count())->toBe(0);
});

test('an admin can invite a new member', function (): void {
    ['organization' => $organization, 'user' => $admin] = organization_with_member(OrganizationRole::Admin);

    $this->actingAs($admin)
        ->post(route('members.invites.store', $organization), [
            'email' => 'newcomer@example.com',
            'role' => 'member',
        ])
        ->assertRedirect();

    $invite = OrganizationInvite::query()->firstOrFail();

    expect($invite->email)->toBe('newcomer@example.com')
        ->and($invite->role)->toBe(OrganizationRole::Member)
        ->and($invite->token)->not->toBeEmpty()
        ->and($invite->invited_by)->toBe($admin->id)
        ->and($invite->organization_id)->toBe($organization->id);
});

test('invites cannot target an existing member', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member(OrganizationRole::Owner);
    $member = attach_member($organization, OrganizationRole::Member);

    $this->actingAs($owner)
        ->from('/'.$organization->slug)
        ->post(route('members.invites.store', $organization), [
            'email' => $member->email,
            'role' => 'admin',
        ])
        ->assertSessionHasErrors('email');

    expect(OrganizationInvite::query()->count())->toBe(0);
});
