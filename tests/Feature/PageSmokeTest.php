<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

test('auth screens render through the guest shell', function (string $routeName, string $component): void {
    $this->get(route($routeName))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page->component($component),
        );
})->with([
    'login' => ['login', 'Auth/Login'],
    'register' => ['register', 'Auth/Register'],
    'forgot password' => ['password.request', 'Auth/ForgotPassword'],
]);

test('the account page renders inside the app shell', function (): void {
    ['organization' => $organization, 'user' => $user] = organization_with_member();

    $user->forceFill(['current_organization_id' => $organization->id])->save();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page): AssertableInertia => $page->component('Profile/Edit'),
        );
});
