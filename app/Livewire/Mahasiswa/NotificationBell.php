<?php

namespace App\Livewire\Mahasiswa;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    #[On('notifications-read')]
    public function refreshCount(): void
    {
        // Livewire merender ulang jumlah setelah status baca berubah.
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'mahasiswa', 403);

        return view('livewire.mahasiswa.notification-bell', [
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
