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
                    {{-- Ringkasan dashboard dosen --}}
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 md:col-span-2">
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm text-slate-500">Mata Kuliah Diampu</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $lecturerCourseCount ?? 0 }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm text-slate-500">Menunggu Penilaian</p>
                            <p class="mt-2 text-2xl font-bold text-amber-600">
                                {{ $pendingGradingCount ?? 0 }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-sm text-slate-500">Sudah Dinilai</p>
                            <p class="mt-2 text-2xl font-bold text-green-700">
                                {{ $gradedSubmissionCount ?? 0 }}
                            </p>
                        </div>
                    </div>
                    {{-- Daftar submission yang menunggu penilaian --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-2">
                        <h2 class="text-lg font-semibold text-slate-900">
                            Submission Menunggu Penilaian
                        </h2>
                        @forelse ($recentPendingSubmissions ?? [] as $submission)
                            <div class="border-b border-slate-100 py-4 last:border-0">
                                <p class="font-medium text-slate-800">
                                    {{ $submission->assignment->title }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500">
                                    Mahasiswa:
                                    {{ $submission->student?->name ?? 'Mahasiswa tidak tersedia' }}
                                </p>
                                <p class="text-sm text-slate-500">
                                    {{ $submission->assignment->course->code }}
                                    —
                                    {{ $submission->assignment->course->name }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Dikumpulkan:
                                    {{ $submission->submitted_at?->format('d/m/Y H:i') ?? 'Tanggal tidak tersedia' }}
                                </p>
                            </div>
                        @empty
                            <p class="mt-3 text-sm text-slate-500">
                                Belum ada submission yang menunggu penilaian.
                            </p>
                        @endforelse
                    </div>
                    {{-- Akses mata kuliah dosen --}}
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

                    {{-- Ringkasan dashboard mahasiswa --}}
                    @isset($totalAssignments)

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 md:col-span-2">

                            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p class="text-sm text-slate-500">Mata Kuliah</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900">
                                    {{ $courseCount }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p class="text-sm text-slate-500">Total Tugas</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900">
                                    {{ $totalAssignments }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p class="text-sm text-slate-500">Sudah Dikumpulkan</p>
                                <p class="mt-2 text-2xl font-bold text-green-700">
                                    {{ $submittedAssignments }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                <p class="text-sm text-slate-500">Belum Dikumpulkan</p>
                                <p class="mt-2 text-2xl font-bold text-amber-600">
                                    {{ $pendingAssignments }}
                                </p>
                            </div>

                        </div>

                        {{-- Daftar tugas terdekat --}}
                        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-2">

                            <h2 class="text-lg font-semibold text-slate-900">
                                Tugas Terdekat
                            </h2>

                            @forelse ($upcomingAssignments as $assignment)

                                <div class="flex flex-col gap-2 border-b border-slate-100 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">

                                    <div>
                                        <p class="font-medium text-slate-800">
                                            {{ $assignment->title }}
                                        </p>

                                        <p class="text-sm text-slate-500">
                                            {{ $assignment->course->code }}
                                            —
                                            {{ $assignment->course->name }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            Batas pengumpulan:
                                            {{ $assignment->due_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>

                                    @if ($assignment->is_submitted)
                                        <span class="text-sm font-medium text-green-700">
                                            Sudah dikumpulkan
                                        </span>
                                    @else
                                        <span class="text-sm font-medium text-amber-600">
                                            Belum dikumpulkan
                                        </span>
                                    @endif

                                </div>

                            @empty

                                <p class="mt-3 text-sm text-slate-500">
                                    Belum ada tugas yang dipublikasikan.
                                </p>

                            @endforelse

                        </div>

                    @endisset

                    {{-- Akses mata kuliah mahasiswa --}}
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

            {{-- Halaman pengunjung yang belum login --}}
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