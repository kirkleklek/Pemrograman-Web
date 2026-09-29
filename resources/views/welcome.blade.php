<x-layout title="Dashboard">

    <div class="space-y-6">

        {{-- Welcome --}}
        <div class="rounded-2xl bg-slate-900 px-6 py-8 text-white shadow-sm sm:px-8">
            <p class="text-sm font-medium text-slate-300">
                Learning Management System
            </p>

            <h1 class="mt-2 text-3xl font-bold tracking-tight">
                Learning Management System - Kelompok 3
            </h1>

            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                Kelola data akademik Learning Management System dengan mudah melalui menu
                mata kuliah dan pengguna.
            </p>
        </div>


        {{-- Menu --}}
        <div class="grid gap-5 md:grid-cols-2">

            {{-- Mata Kuliah --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-lg">
                    📚
                </div>

                <h2 class="mt-4 text-lg font-semibold text-slate-900">
                    Mata Kuliah
                </h2>

                <p class="mt-1 text-sm leading-6 text-slate-500">
                    Kelola daftar mata kuliah, dosen pengampu, status,
                    dan informasi mata kuliah.
                </p>

                <a
                    href="{{ route('courses.index') }}"
                    class="mt-5 inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Lihat Mata Kuliah
                </a>
            </div>


            {{-- Pengguna --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-lg">
                    👥
                </div>

                <h2 class="mt-4 text-lg font-semibold text-slate-900">
                    Pengguna
                </h2>

                <p class="mt-1 text-sm leading-6 text-slate-500">
                    Kelola data pengguna, NIM/NIP, email, dan role
                    dalam sistem KampusLMS.
                </p>

                <a
                    href="{{ route('users.index') }}"
                    class="mt-5 inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Lihat Pengguna
                </a>
            </div>

        </div>


        {{-- Quick Navigation --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <h2 class="text-lg font-semibold text-slate-900">
                Akses Cepat
            </h2>

            <div class="mt-4 flex flex-wrap gap-3">

                <a
                    href="{{ route('courses.create') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    + Tambah Mata Kuliah
                </a>

                <a
                    href="{{ route('users.create') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    + Tambah Pengguna
                </a>

            </div>

        </div>

    </div>

</x-layout>