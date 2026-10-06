<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\User;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_dapat_menandai_notification_miliknya_sebagai_read(): void
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'TestNotification',
            'data' => [
                'message' => 'Notifikasi test',
            ],
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson(
            "/api/v1/notifications/{$notification->id}/read"
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.type', 'TestNotification')
            ->assertJsonPath(
                'data.data.message',
                'Notifikasi test'
            );

        $this->assertNotNull(
            $notification->fresh()->read_at
        );
    }

    public function test_user_tidak_dapat_menandai_notification_milik_user_lain(): void
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $otherUser = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $notification = $otherUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'TestNotification',
            'data' => [
                'message' => 'Notification milik user lain',
            ],
        ]);

        Sanctum::actingAs($user);

        $this->postJson(
            "/api/v1/notifications/{$notification->id}/read"
        )
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);

        $this->assertNull(
            $notification->fresh()->read_at
        );
    }
}