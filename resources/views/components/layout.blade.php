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
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">

            <a
                href="{{ route('dashboard') }}"
                class="{{ request()->routeIs('dashboard')
                    ? 'font-semibold text-slate-900'
                    : 'text-slate-600 hover:text-slate-900' }}
                    transition"
            >
                KampusLMS
            </a>

            <div class="flex items-center gap-6 text-sm font-medium">
                <a
                    href="{{ route('dashboard') }}"
                    class="text-slate-600 transition hover:text-slate-900"
                >
                    Dashboard
                </a>

                <a
                    href="{{ route('admin.courses.index') }}"
                    class="{{ request()->routeIs('admin.courses.*')
                        ? 'font-semibold text-slate-900'
                        : 'text-slate-600 hover:text-slate-900' }}
                        transition"
                >
                    Mata Kuliah
                </a>

                <a
                    href="{{ route('admin.users.index') }}"
                    class="{{ request()->routeIs('admin.users.*')
                        ? 'font-semibold text-slate-900'
                        : 'text-slate-600 hover:text-slate-900' }}
                        transition"
                >
                    Pengguna
                </a>

                <a
                    href="{{ route('tentang') }}"
                    class="{{ request()->routeIs('tentang')
                        ? 'font-semibold text-slate-900'
                        : 'text-slate-600 hover:text-slate-900' }}
                        transition"
                >
                    Tentang
                </a>
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