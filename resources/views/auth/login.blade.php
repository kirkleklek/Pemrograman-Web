
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KampusLMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen items-center justify-center bg-slate-100 px-4 text-slate-800">
    <main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-slate-900">KampusLMS</h1>
            <p class="mt-2 text-sm text-slate-500">
                Masuk untuk melanjutkan ke sistem pembelajaran.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-medium">Login gagal.</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-medium">
                    Email
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="username"
                    required
                    autofocus
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                    placeholder="nama@kampus.ac.id"
                >
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">
                    Kata Sandi
                </label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                    class="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                    placeholder="Masukkan kata sandi"
                >
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input
                    type="checkbox"
                    name="remember"
                    value="1"
                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    @checked(old('remember'))
                >
                Ingat saya
            </label>
            <div class="text-right">
                <a
                    href="{{ route('password.request') }}"
                    class="text-sm font-medium text-indigo-600 transition hover:text-indigo-800"
                >
                    Lupa Password?
                </a>
            </div>

            <button
                type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-3 font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                Masuk
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-500">
            KampusLMS — Sistem Informasi Pembelajaran
        </p>
    </main>
</body>
</html>
