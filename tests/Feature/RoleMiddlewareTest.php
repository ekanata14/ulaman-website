<?php

use App\Models\User;

it('allows super_admin to access admin routes', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('redirects regular user away from admin routes', function () {
    $user = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('user.dashboard'));
});

it('redirects super_admin away from user routes', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);

    $this->actingAs($admin)
        ->get(route('user.dashboard'))
        ->assertRedirect(route('admin.dashboard'));
});

it('redirects unauthenticated requests to login', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});
