<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Akses Ditolak - KampusLMS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center px-6">

    <div class="max-w-md w-full rounded-2xl bg-white p-8 text-center shadow-sm">
        <div class="text-6xl font-bold text-slate-800 mb-4">
            403
        </div>

        <h1 class="text-2xl font-semibold text-slate-900 mb-2">
            Akses Ditolak
        </h1>

        <p class="text-slate-600 mb-6">
            Anda tidak memiliki izin untuk mengakses halaman ini.
        </p>

        <a
            href="{{ route('dashboard') }}"
            class="inline-block rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-700"
        >
            Kembali ke Dashboard
        </a>
    </div>

</body>
</html>