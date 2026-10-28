<?php

namespace Tests\Feature;

use App\Livewire\Mahasiswa\NotificationBell;
use App\Livewire\Mahasiswa\NotificationList;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationWebTest extends TestCase
{
    use RefreshDatabase;

    private function notification(User $user, string $message = 'Materi baru'): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'TestNotification',
            'data' => ['message' => $message],
        ]);
    }

    public function test_notification_page_requires_a_student_session(): void
    {
        $url = route('mahasiswa.notifications.index');
        $this->get($url)->assertRedirect(route('login'));

        foreach (['admin', 'dosen'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->actingAs($user)->get($url)->assertForbidden();
            Livewire::actingAs($user)->test(NotificationBell::class)->assertForbidden();
            Livewire::actingAs($user)->test(NotificationList::class)->assertForbidden();
        }

        $student = User::factory()->create(['role' => 'mahasiswa']);
        $this->actingAs($student)->get($url)->assertOk()
            ->assertSeeLivewire(NotificationBell::class)
            ->assertSeeLivewire(NotificationList::class)
            ->assertSee('Belum ada notifikasi');
    }

    public function test_list_is_paginated_newest_first_and_only_contains_own_notifications(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $other = User::factory()->create(['role' => 'mahasiswa']);
        $this->notification($other, 'Rahasia mahasiswa lain');
        foreach (range(1, 16) as $number) {
            $this->notification($student, "Pemberitahuan nomor {$number}")
                ->update(['created_at' => now()->subMinutes(17 - $number)]);
        }

        Livewire::actingAs($student)->test(NotificationList::class)
            ->assertViewHas('notifications', fn ($items) => $items->total() === 16 && $items->count() === 15)
            ->assertSeeInOrder(['Pemberitahuan nomor 16', 'Pemberitahuan nomor 15'])
            ->assertDontSee('Rahasia mahasiswa lain')
            ->call('gotoPage', 2)
            ->assertViewHas('notifications', fn ($items) => $items->count() === 1)
            ->assertSee('Pemberitahuan nomor 1')
            ->assertDontSee('Rahasia mahasiswa lain');
    }

    public function test_read_action_is_idempotent_and_refreshes_the_bell(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $notification = $this->notification($student);
        $component = Livewire::actingAs($student)->test(NotificationList::class)
            ->call('markAsRead', $notification->id)
            ->assertDispatched('notifications-read')
            ->assertSee('0 notifikasi belum dibaca');
        $readAt = $notification->fresh()->read_at;
        $this->assertNotNull($readAt);

        $this->travel(1)->minutes();
        $component->call('markAsRead', $notification->id)->assertOk();
        $this->assertTrue($readAt->equalTo($notification->fresh()->read_at));
    }

    public function test_student_cannot_mark_another_students_notification_as_read(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $foreign = $this->notification(User::factory()->create(['role' => 'mahasiswa']));

        $this->expectException(ModelNotFoundException::class);
        try {
            Livewire::actingAs($student)->test(NotificationList::class)
                ->call('markAsRead', $foreign->id);
        } finally {
            $this->assertNull($foreign->fresh()->read_at);
        }
    }

    public function test_notification_scope_checks_notifiable_type_and_handles_unknown_ids(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $foreignType = $this->notification($student);
        $foreignType->update(['notifiable_type' => 'AnotherModel']);

        foreach ([$foreignType->id, (string) Str::uuid()] as $id) {
            try {
                Livewire::actingAs($student)->test(NotificationList::class)
                    ->call('markAsRead', $id);
                $this->fail('A notification outside the user scope must not be found.');
            } catch (ModelNotFoundException $exception) {
                $this->assertSame([$id], $exception->getIds());
            }
        }
        $this->assertNull($foreignType->fresh()->read_at);
    }

    public function test_mark_all_only_updates_own_unread_notifications(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $unread = $this->notification($student);
        $alreadyRead = $this->notification($student);
        $alreadyRead->update(['read_at' => now()->subDay()]);
        $readAt = $alreadyRead->fresh()->read_at;
        $foreign = $this->notification(User::factory()->create(['role' => 'mahasiswa']));

        Livewire::actingAs($student)->test(NotificationList::class)
            ->call('markAllAsRead')->assertDispatched('notifications-read');

        $this->assertNotNull($unread->fresh()->read_at);
        $this->assertTrue($readAt->equalTo($alreadyRead->fresh()->read_at));
        $this->assertNull($foreign->fresh()->read_at);
    }

    public function test_polling_and_events_refresh_counts_and_new_notifications(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $this->notification(User::factory()->create(['role' => 'mahasiswa']));
        $bell = Livewire::actingAs($student)->test(NotificationBell::class)
            ->assertViewHas('unreadCount', 0)->assertSee('wire:poll.30s', false);
        $list = Livewire::test(NotificationList::class)->assertSee('Belum ada notifikasi');

        $notification = $this->notification($student, 'Pembaruan terbaru');
        $bell->call('$refresh')->assertViewHas('unreadCount', 1);
        $list->call('$refresh')->assertSee('Pembaruan terbaru');

        $notification->markAsRead();
        $bell->dispatch('notifications-read')->assertViewHas('unreadCount', 0);
    }

    public function test_read_action_rechecks_authentication_after_initial_render(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $notification = $this->notification($student);
        $component = Livewire::actingAs($student)->test(NotificationList::class);
        auth()->logout();

        $component->call('markAsRead', $notification->id)->assertForbidden();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_notification_content_is_escaped(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $this->notification($student, '<script>alert(1)</script>');

        Livewire::actingAs($student)->test(NotificationList::class)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
