<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_user_data(): void
    {
        $user = User::factory()->create([
            'email' => 'dosen-auth-test@kampuslms.test',
            'password' => Hash::make('password'),
            'role' => 'dosen',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'auth-api-test',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                ],
            ])
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_invalid_credentials_return_standard_api_error(): void
    {
        $user = User::factory()->create([
            'email' => 'dosen-auth-test@kampuslms.test',
            'password' => Hash::make('password'),
            'role' => 'dosen',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password-salah',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Data yang diberikan tidak valid.'
            )
            ->assertJsonStructure([
                'message',
                'errors' => ['email'],
            ]);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create([
            'role' => 'dosen',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create([
            'role' => 'dosen',
        ]);

        $newToken = $user->createToken('logout-test');
        $tokenId = $newToken->accessToken->id;

        $this->withToken($newToken->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }
}