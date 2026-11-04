
@props(['title' => 'KampusLMS'])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'KampusLMS' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-800">

    <nav class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">

            <a
                href="{{ route('dashboard') }}"
                class="{{ request()->routeIs('dashboard')
                    ? 'font-semibold text-slate-900'
                    : 'text-slate-600 hover:text-slate-900' }}
                    transition"
            >
                KampusLMS
            </a>

            <div class="flex flex-wrap items-center gap-4 text-sm font-medium sm:gap-6">

                <a
                    href="{{ route('dashboard') }}"
                    class="text-slate-600 transition hover:text-slate-900"
                >
                    Dashboard
                </a>

                @auth
                    @if (auth()->user()->role === 'admin')
                        <a
                            href="{{ route('admin.courses.index') }}"
                            class="{{ request()->routeIs('admin.courses.*')
                                ? 'font-semibold text-slate-900'
                                : 'text-slate-600 hover:text-slate-900' }} transition"
                        >
                            Mata Kuliah
                        </a>

                        <a
                            href="{{ route('admin.users.index') }}"
                            class="{{ request()->routeIs('admin.users.*')
                                ? 'font-semibold text-slate-900'
                                : 'text-slate-600 hover:text-slate-900' }} transition"
                        >
                            Pengguna
                        </a>

                    @elseif (auth()->user()->role === 'dosen')
                        <a
                            href="{{ route('dosen.courses.index') }}"
                            class="{{ request()->routeIs('dosen.courses.*')
                                ? 'font-semibold text-slate-900'
                                : 'text-slate-600 hover:text-slate-900' }} transition"
                        >
                            Mata Kuliah Saya
                        </a>

                    @elseif (auth()->user()->role === 'mahasiswa')
                        <a
                            href="{{ route('mahasiswa.courses.index') }}"
                            class="{{ request()->routeIs('mahasiswa.courses.*')
                                ? 'font-semibold text-slate-900'
                                : 'text-slate-600 hover:text-slate-900' }} transition"
                        >
                            Mata Kuliah Saya
                        </a>

                        <a
                            href="{{ route('mahasiswa.grades.index') }}"
                            class="{{ request()->routeIs('mahasiswa.grades.*')
                                ? 'font-semibold text-slate-900'
                                : 'text-slate-600 hover:text-slate-900' }} transition"
                        >
                            Nilai Saya
                        </a>
                    @endif
                @endauth

                <a
                    href="{{ route('tentang') }}"
                    class="{{ request()->routeIs('tentang')
                        ? 'font-semibold text-slate-900'
                        : 'text-slate-600 hover:text-slate-900' }} transition"
                >
                    Tentang
                </a>

                @auth
                    @if (auth()->user()->role === 'mahasiswa')
                        <livewire:mahasiswa.notification-bell />
                    @endif

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf

                        <button
                            type="submit"
                            class="text-slate-600 transition hover:text-red-600"
                        >
                            Logout
                        </button>
                    </form>
                @endauth

            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-7xl px-6 py-8">

        @if (session('success'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}

    </main>

</body>
</html>
