
<x-layout title="KampusLMS">

    <div class="space-y-6">

        @auth
            {{-- Dashboard untuk pengguna yang sudah login --}}
            <div class="rounded-2xl bg-slate-900 px-6 py-8 text-white shadow-sm sm:px-8">
                <p class="text-sm font-medium text-slate-300">
                    Learning Management System
                </p>

                <h1 class="mt-2 text-3xl font-bold tracking-tight">
                    Selamat datang, {{ auth()->user()->name }}
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                    @if (auth()->user()->role === 'admin')
                        Kelola data pengguna dan mata kuliah KampusLMS.
                    @elseif (auth()->user()->role === 'dosen')
                        Kelola mata kuliah yang kamu ampu, peserta, materi, dan tugas.
                    @else
                        Selamat belajar melalui KampusLMS.
                    @endif
                </p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">

                @if (auth()->user()->role === 'admin')
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">
                            Mata Kuliah
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Kelola daftar mata kuliah, dosen pengampu, dan status.
                        </p>

                        <a
                            href="{{ route('admin.courses.index') }}"
                            class="mt-5 inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Lihat Mata Kuliah
                        </a>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-slate-900">
                            Pengguna
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Kelola pengguna, NIM/NIP, email, dan role.
                        </p>

                        <a
                            href="{{ route('admin.users.index') }}"
                            class="mt-5 inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Lihat Pengguna
                        </a>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-2">
                        <h2 class="text-lg font-semibold text-slate-900">
                            Akses Cepat
                        </h2>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <a
                                href="{{ route('admin.courses.create') }}"
                                class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Tambah Mata Kuliah
                            </a>

                            <a
                                href="{{ route('admin.users.create') }}"
                                class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Tambah Pengguna
                            </a>
                        </div>
                    </div>

                @elseif (auth()->user()->role === 'dosen')
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-2">
                        <h2 class="text-lg font-semibold text-slate-900">
                            Mata Kuliah Saya
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Buka mata kuliah yang kamu ampu untuk mengelola peserta,
                            materi, dan tugas.
                        </p>

                        <a
                            href="{{ route('dosen.courses.index') }}"
                            class="mt-5 inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Lihat Mata Kuliah Saya
                        </a>
                    </div>

                @elseif (auth()->user()->role === 'mahasiswa')
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-2">

                        <h2 class="text-lg font-semibold text-slate-900">
                            Mata Kuliah Saya
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Lihat mata kuliah yang kamu ikuti, beserta akses ke materi
                            dan tugas pada setiap mata kuliah.
                        </p>

                        <a
                            href="{{ route('mahasiswa.courses.index') }}"
                            class="mt-5 inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Lihat Mata Kuliah Saya
                        </a>

                    </div>
                @endif
            </div>

        @else
            {{-- Halaman untuk pengunjung yang belum login --}}
            <div class="rounded-2xl bg-slate-900 px-6 py-8 text-white shadow-sm sm:px-8">
                <p class="text-sm font-medium text-slate-300">
                    Learning Management System
                </p>

                <h1 class="mt-2 text-3xl font-bold tracking-tight">
                    KampusLMS — Kelompok 3
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                    Platform untuk mendukung pengelolaan kegiatan pembelajaran
                    dan data akademik.
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">
                    Selamat datang
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Silakan login untuk mengakses fitur sesuai dengan role akunmu.
                </p>

                <a
                    href="{{ route('login') }}"
                    class="mt-5 inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Login
                </a>
            </div>
        @endauth

    </div>

</x-layout>