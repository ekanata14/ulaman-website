<?php

test('registration screen can be rendered', function () {
    $this->get(route('register'))->assertOk();
});

test('new users can register', function () {
    $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertAuthenticated();
});
