<x-layout>
    <div class="mx-auto max-w-7xl px-4 py-6">

        {{-- Informasi tugas --}}
        <div class="mb-6">
            <p class="text-sm text-slate-500">
                Mata Kuliah: {{ $course->name }}
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-800">
                Daftar Submission
            </h1>

            <p class="mt-1 text-slate-500">
                Tugas: {{ $assignment->title }}
            </p>

            <p class="mt-2 text-sm text-slate-500">
                Lihat status pengumpulan, waktu pengumpulan, dan nilai mahasiswa.
            </p>
        </div>

        {{-- Pesan sukses --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        {{-- Daftar status mahasiswa yang diperbarui oleh Livewire --}}
        <livewire:dosen.submission-status-list
            :assignment="$assignment"
        />

        {{-- Kembali ke detail tugas --}}
        <div class="mt-6">
            <a
                href="{{ route('dosen.assignments.show', $assignment) }}"
                class="text-sm font-medium text-slate-600 hover:text-slate-800"
            >
                &larr; Kembali ke tugas
            </a>
        </div>

    </div>
</x-layout>