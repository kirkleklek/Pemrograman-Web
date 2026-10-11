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

    public function test_user_hanya_melihat_notification_miliknya_dengan_pagination(): void
    {
        $user = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $otherUser = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $ownNotificationIds = [];

        foreach (range(1, 16) as $number) {
            $notification = $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'TestNotification',
                'data' => [
                    'message' => "Notifikasi {$number}",
                ],
            ]);

            $ownNotificationIds[] = $notification->id;
        }

        $otherNotification = $otherUser->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'TestNotification',
            'data' => [
                'message' => 'Notifikasi milik orang lain',
            ],
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 16);

        $firstPageIds = collect($response->json('data'))
            ->pluck('id')
            ->all();

        $this->assertNotContains(
            $otherNotification->id,
            $firstPageIds
        );

        foreach ($firstPageIds as $id) {
            $this->assertContains($id, $ownNotificationIds);
        }

        $response = $this->getJson('/api/v1/notifications?page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 16);

        $secondPageIds = collect($response->json('data'))
            ->pluck('id')
            ->all();

        $this->assertNotContains(
            $otherNotification->id,
            $secondPageIds
        );

        foreach ($secondPageIds as $id) {
            $this->assertContains($id, $ownNotificationIds);
        }
    }

    public function test_daftar_notification_memerlukan_authentication(): void
    {
        $this->getJson('/api/v1/notifications')
            ->assertUnauthorized();
    }
}
