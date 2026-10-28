<div wire:poll.30s>
    <a href="{{ route('mahasiswa.notifications.index') }}"
       aria-label="Notifikasi, {{ $unreadCount }} belum dibaca"
       @if (request()->routeIs('mahasiswa.notifications.*')) aria-current="page" @endif
       class="relative inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-blue-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
        </svg>
        @if ($unreadCount > 0)
            <span aria-hidden="true" class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1.5 py-0.5 text-center text-xs font-semibold text-white">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
        <span class="sr-only" role="status">{{ $unreadCount }} notifikasi belum dibaca</span>
    </a>
</div>
