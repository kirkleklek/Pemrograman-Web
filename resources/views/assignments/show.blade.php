<x-layout>
    <div class="max-w-4xl mx-auto px-4 py-6">

        <div class="mb-6">
            <p class="text-sm text-slate-500">
                Mata Kuliah: {{ $course->name }}
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-800">
                {{ $assignment->title }}
            </h1>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-lg border border-slate-200 bg-white p-6">

            <div class="mb-6">
                <h2 class="text-sm font-semibold text-slate-700">
                    Instruksi
                </h2>

                <p class="mt-2 whitespace-pre-line text-slate-600">
                    {{ $assignment->instructions }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 mb-6">

                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase text-slate-500">
                        Deadline
                    </p>

                    <p class="mt-1 text-slate-700">
                        {{ $assignment->due_at->format('d/m/Y H:i') }}
                    </p>
                </div>

                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase text-slate-500">
                        Nilai Maksimum
                    </p>

                    <p class="mt-1 text-slate-700">
                        {{ $assignment->max_score }}
                    </p>
                </div>

                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase text-slate-500">
                        Status
                    </p>

                    <p class="mt-1 text-slate-700">
                        {{ ucfirst($assignment->status) }}
                    </p>
                </div>

                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-medium uppercase text-slate-500">
                        Pengumpulan Terlambat
                    </p>

                    <p class="mt-1 text-slate-700">
                        {{ $assignment->allow_late ? 'Diizinkan' : 'Tidak diizinkan' }}
                    </p>
                </div>

            </div>

            @if(auth()->user()->role === 'dosen')
                <div class="border-t border-slate-200 pt-6">
                    <a
                        href="{{ route('dosen.assignments.edit', $assignment) }}"
                        class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                    >
                        Edit Tugas
                    </a>

                    <a
                        href="{{ route('dosen.assignments.submissions.index', $assignment) }}"
                        class="ml-3 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Lihat Submission
                    </a>
                </div>
            @endif

        </div>

        <div class="mt-6">
            <a
                href="{{ auth()->user()->role === 'dosen'
                    ? route('dosen.courses.assignments.index', $course)
                    : route('mahasiswa.courses.assignments.index', $course) }}"
                class="text-sm font-medium text-slate-600 hover:text-slate-800"
            >
                Kembali ke daftar tugas
            </a>
        </div>

    </div>
</x-layout>