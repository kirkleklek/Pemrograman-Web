<?php

namespace App\Livewire\Mahasiswa;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationList extends Component
{
    use WithPagination;

    protected function student(): User
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'mahasiswa', 403);

        return $user;
    }

    public function markAsRead(string $id): void
    {
        // Scope relasi memeriksa ID pemilik sekaligus tipe notifiable.
        $this->student()->notifications()->findOrFail($id)->markAsRead();

        $this->dispatch('notifications-read');
    }

    public function markAllAsRead(): void
    {
        $this->student()->unreadNotifications()->update(['read_at' => now()]);

        $this->dispatch('notifications-read');
    }

    public function render()
    {
        $user = $this->student();

        return view('livewire.mahasiswa.notification-list', [
            'notifications' => $user->notifications()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(15),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
