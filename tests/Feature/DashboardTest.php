<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('authenticated super_admin can visit admin dashboard', function () {
    $admin = User::factory()->create(['role' => 'super_admin', 'email_verified_at' => now()]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('authenticated user can visit user dashboard', function () {
    $user = User::factory()->create(['role' => 'user', 'email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('user.dashboard'))
        ->assertOk();
});
