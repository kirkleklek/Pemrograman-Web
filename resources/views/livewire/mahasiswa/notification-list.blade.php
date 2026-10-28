<section wire:poll.30s class="mx-auto max-w-4xl" aria-labelledby="notifications-heading">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 id="notifications-heading" class="text-2xl font-bold text-slate-900">Notifikasi</h1>
            <p class="mt-1 text-sm text-slate-600" role="status">{{ $unreadCount }} notifikasi belum dibaca</p>
        </div>
        @if ($unreadCount > 0)
            <button type="button" wire:click="markAllAsRead" wire:loading.attr="disabled"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                Tandai semua sudah dibaca
            </button>
        @endif
    </div>

    <p wire:offline class="mb-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Koneksi terputus. Notifikasi akan diperbarui setelah tersambung kembali.</p>

    <ul class="space-y-3" aria-label="Daftar notifikasi">
        @forelse ($notifications as $notification)
            @php
                $message = match ($notification->type) {
                    \App\Notifications\NewAssignmentNotification::class => 'Tugas baru telah dipublikasikan.',
                    \App\Notifications\GradeGivenNotification::class => 'Nilai atau feedback tugas Anda telah diperbarui.',
                    default => $notification->data['message'] ?? 'Ada pembaruan untuk Anda.',
                };
            @endphp
            <li wire:key="notification-{{ $notification->id }}"
                @class(['rounded-xl border p-5', 'border-blue-200 bg-blue-50' => $notification->unread(), 'border-slate-200 bg-white' => $notification->read()])>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="break-words font-semibold text-slate-900">{{ $notification->data['title'] ?? 'Notifikasi' }}</h2>
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs text-slate-600">{{ $notification->unread() ? 'Belum dibaca' : 'Sudah dibaca' }}</span>
                        </div>
                        <p class="mt-2 break-words whitespace-pre-line text-sm text-slate-700">{{ $message }}</p>
                        <time datetime="{{ $notification->created_at->toIso8601String() }}" class="mt-2 block text-xs text-slate-500">
                            {{ $notification->created_at->format('d/m/Y H:i') }}
                        </time>
                        @if ($notification->type === \App\Notifications\NewAssignmentNotification::class && isset($notification->data['assignment_id']))
                            <a href="{{ route('mahasiswa.assignments.show', $notification->data['assignment_id']) }}"
                               class="mt-3 inline-block text-sm font-medium text-blue-700 hover:underline">Lihat tugas</a>
                        @elseif ($notification->type === \App\Notifications\GradeGivenNotification::class && isset($notification->data['submission_id']))
                            <a href="{{ route('mahasiswa.submissions.show', $notification->data['submission_id']) }}"
                               class="mt-3 inline-block text-sm font-medium text-blue-700 hover:underline">Lihat nilai dan feedback</a>
                        @endif
                    </div>
                    @if ($notification->unread())
                        <button type="button" wire:click="markAsRead('{{ $notification->id }}')" wire:loading.attr="disabled"
                                class="shrink-0 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                            Tandai sudah dibaca
                        </button>
                    @endif
                </div>
            </li>
        @empty
            <li class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center">
                <h2 class="font-semibold text-slate-800">Belum ada notifikasi</h2>
                <p class="mt-2 text-sm text-slate-500">Pemberitahuan tugas baru dan hasil penilaian akan muncul di sini.</p>
            </li>
        @endforelse
    </ul>

    <div class="mt-6">{{ $notifications->links() }}</div>
</section>
