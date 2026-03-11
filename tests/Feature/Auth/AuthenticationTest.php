<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'registration_number' => $user->registration_number,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_authenticate_with_email_and_password_when_mode_is_set(): void
    {
        config()->set('settings.candidate_login_mode', 'email_password');

        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        config()->set('settings.candidate_login_mode', 'email_password');

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_users_can_not_authenticate_using_registration_number_mode(): void
    {
        $user = User::factory()->create([
            'status' => User::STATUS_INACTIVE,
        ]);

        $response = $this->from('/login')->post('/login', [
            'registration_number' => $user->registration_number,
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'registration_number' => 'Your account is inactive. Please contact the administrator.',
        ]);

        $this->assertGuest();
    }

    public function test_suspended_users_see_status_message_in_email_password_mode(): void
    {
        config()->set('settings.candidate_login_mode', 'email_password');

        $user = User::factory()->create([
            'status' => User::STATUS_SUSPENDED,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'Your account is suspended. Please contact the administrator.',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
