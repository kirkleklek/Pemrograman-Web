<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => $user->email,
            ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas(
            'status',
            'Jika email terdaftar, instruksi reset password akan dikirim.'
        );

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unregistered_email_receives_generic_response(): void
    {
        Notification::fake();

        $response = $this->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => 'tidak-terdaftar@example.com',
            ]);

        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas(
            'status',
            'Jika email terdaftar, instruksi reset password akan dikirim.'
        );

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => 'password-lama',
        ]);

        $token = Password::broker()->createToken($user);
        $newPassword = 'password-baru-123';

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas(
            'status',
            'Password berhasil diubah. Silakan login kembali.'
        );

        $user->refresh();

        $this->assertTrue(
            Hash::check($newPassword, $user->password)
        );

        $this->assertGuest('web');
    }

    public function test_password_reset_revokes_all_existing_sanctum_tokens(): void
    {
        $user = User::factory()->create([
            'password' => 'password-lama',
        ]);

        $oldApiToken = $user->createToken('device-lama')->plainTextToken;
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $resetToken = Password::broker()->createToken($user);
        $newPassword = 'password-baru-123';

        $this->post(route('password.store'), [
            'token' => $resetToken,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertRedirect(route('login'));

        $user->refresh();

        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withToken($oldApiToken)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_user_cannot_reset_password_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'password' => 'password-lama',
        ]);

        $response = $this->from(
            route('password.reset', ['token' => 'token-tidak-valid'])
        )->post(route('password.store'), [
            'token' => 'token-tidak-valid',
            'email' => $user->email,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ]);

        $response->assertRedirect(
            route('password.reset', ['token' => 'token-tidak-valid'])
        );
        $response->assertSessionHasErrors('email');

        $user->refresh();

        $this->assertTrue(
            Hash::check('password-lama', $user->password)
        );
    }
}