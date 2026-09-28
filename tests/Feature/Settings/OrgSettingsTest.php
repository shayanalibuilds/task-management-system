<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Label;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Project;
use App\Models\OrganizationInvite;
use App\Models\Task;
use Inertia\Testing\AssertableInertia;

test('an owner updates general settings including slug and webhook', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();

    $this->actingAs($owner)
        ->patch(route('settings.update', $organization), [
            'name' => 'Northwind Studio Two',
            'slug' => 'northwind-two',
            'webhook_url' => 'https://hooks.example.com/dailytm',
        ])
        ->assertRedirect();

    expect($organization->fresh()->name)->toBe('Northwind Studio Two')
        ->and($organization->fresh()->slug)->toBe('northwind-two')
        ->and($organization->fresh()->webhook_url)->toBe('https://hooks.example.com/dailytm');
});

test('a duplicate slug is rejected', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    Organization::factory()->create(['slug' => 'taken']);

    $this->actingAs($owner)
        ->from('/'.$organization->slug)
        ->patch(route('settings.update', $organization), [
            'name' => 'Renamed',
            'slug' => 'taken',
        ])
        ->assertSessionHasErrors('slug');
});

test('members and viewers cannot open or change org settings', function (): void {
    ['organization' => $organization, 'user' => $member] = organization_with_member(OrganizationRole::Member);

    $this->actingAs($member)
        ->get(route('settings.show', $organization))
        ->assertForbidden();

    $this->actingAs($member)
        ->patch(route('settings.update', $organization), [
            'name' => 'Nope',
            'slug' => 'nope',
        ])
        ->assertForbidden();
});

test('an admin manages organization labels', function (): void {
    ['organization' => $organization, 'user' => $admin] = organization_with_member(OrganizationRole::Admin);

    $this->actingAs($admin)
        ->post(route('settings.labels.store', $organization), [
            'name' => 'Design',
            'color' => '#EC4899',
        ])
        ->assertRedirect();

    $label = Label::query()->where('name', 'Design')->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('settings.labels.update', ['organization' => $organization, 'label' => $label]), [
            'name' => 'Design System',
            'color' => '#10B981',
        ])
        ->assertRedirect();

    expect($label->fresh()->name)->toBe('Design System')
        ->and($label->fresh()->color)->toBe('#10B981');

    $this->actingAs($admin)
        ->delete(route('settings.labels.destroy', ['organization' => $organization, 'label' => $label]))
        ->assertRedirect();

    expect(Label::query()->whereKey($label->id)->exists())->toBeFalse();
});

test('only owners can delete the organization', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $admin = attach_member($organization, OrganizationRole::Admin);

    $project = Project::factory()->for($organization)->create();
    $column = \App\Models\ProjectColumn::factory()->for($project)->create();
    Task::factory()->for($project)->create(['column_id' => $column->id]);

    $this->actingAs($admin)
        ->delete(route('settings.destroy', $organization))
        ->assertForbidden();

    $this->actingAs($owner)
        ->delete(route('settings.destroy', $organization))
        ->assertRedirect(route('home', absolute: false));

    expect(Organization::query()->whereKey($organization->id)->exists())->toBeFalse()
        ->and(Project::query()->whereKey($project->id)->exists())->toBeFalse();
});

test('an admin changes a member role', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $member = attach_member($organization, OrganizationRole::Member);

    $membershipId = $organization->memberships()->where('user_id', $member->id)->firstOrFail()->id;

    $this->actingAs($owner)
        ->patch(route('settings.members.update', ['organization' => $organization, 'membership' => $membershipId]), [
            'role' => 'admin',
        ])
        ->assertRedirect();

    expect($organization->roleFor($member))->toBe(OrganizationRole::Admin);
});

test('the last owner cannot be demoted', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();

    $membershipId = $organization->memberships()->where('user_id', $owner->id)->firstOrFail()->id;

    $this->actingAs($owner)
        ->from('/'.$organization->slug)
        ->patch(route('settings.members.update', ['organization' => $organization, 'membership' => $membershipId]), [
            'role' => 'member',
        ])
        ->assertSessionHasErrors('role');

    expect($organization->roleFor($owner))->toBe(OrganizationRole::Owner);
});

test('a member can be removed from the organization', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $member = attach_member($organization, OrganizationRole::Member);

    $membershipId = $organization->memberships()->where('user_id', $member->id)->firstOrFail()->id;

    $this->actingAs($owner)
        ->delete(route('settings.members.destroy', ['organization' => $organization, 'membership' => $membershipId]))
        ->assertRedirect();

    expect($organization->fresh()->hasMember($member->id))->toBeFalse();
});

test('the members page lists members and pending invites with copy links', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $invite = OrganizationInvite::factory()->for($organization)->create();

    $this->actingAs($owner)
        ->get(route('settings.members', $organization))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page
                ->has('members', 1)
                ->where('members.0.role', 'owner')
                ->has('invites', 1)
                ->where('invites.0.token', $invite->token),
        );
});

test('a pending invite can be revoked', function (): void {
    ['organization' => $organization, 'user' => $owner] = organization_with_member();
    $invite = OrganizationInvite::factory()->for($organization)->create();

    $this->actingAs($owner)
        ->delete(route('settings.invites.destroy', ['organization' => $organization, 'invite' => $invite->id]))
        ->assertRedirect();

    expect(OrganizationInvite::query()->whereKey($invite->id)->exists())->toBeFalse();
});
