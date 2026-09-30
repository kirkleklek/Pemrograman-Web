<x-layout>
    <div class="max-w-4xl mx-auto px-4 py-6">

        <div class="mb-6">
            <p class="text-sm text-slate-500">
                Mata Kuliah: {{ $course->name }}
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-800">
                Detail Submission
            </h1>

            <p class="mt-1 text-slate-500">
                Tugas: {{ $submission->assignment->title }}
            </p>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-6">

            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Informasi Mahasiswa
                </h2>

                <div class="mt-4">
                    <p class="text-sm text-slate-500">
                        Nama
                    </p>

                    <p class="mt-1 font-medium text-slate-700">
                        {{ $submission->student->name }}
                    </p>
                </div>

                <div class="mt-4">
                    <p class="text-sm text-slate-500">
                        Email
                    </p>

                    <p class="mt-1 text-slate-700">
                        {{ $submission->student->email }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Submission
                </h2>

                <div class="mt-4">
                    <p class="text-sm text-slate-500">
                        File
                    </p>

                    <p class="mt-1 font-medium text-slate-700">
                        {{ $submission->original_name }}
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ number_format($submission->file_size / 1024, 1) }} KB
                    </p>
                </div>

                <div class="mt-4">
                    <p class="text-sm text-slate-500">
                        Waktu Pengumpulan
                    </p>

                    <p class="mt-1 text-slate-700">
                        {{ $submission->submitted_at->format('d/m/Y H:i') }}
                    </p>
                </div>

                <div class="mt-4">
                    <p class="text-sm text-slate-500">
                        Status
                    </p>

                    @if($submission->is_late)
                        <p class="mt-1 font-medium text-red-600">
                            Terlambat
                        </p>
                    @else
                        <p class="mt-1 font-medium text-green-600">
                            Tepat waktu
                        </p>
                    @endif
                </div>

                @if($submission->note)
                    <div class="mt-4">
                        <p class="text-sm text-slate-500">
                            Catatan
                        </p>

                        <p class="mt-1 whitespace-pre-line text-slate-700">
                            {{ $submission->note }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-800">
                    Nilai
                </h2>

                @if($submission->grade)
                    <div class="mt-4">
                        <p class="text-2xl font-bold text-slate-800">
                            {{ $submission->grade->score }}
                        </p>
                    </div>

                    @if($submission->grade->feedback)
                        <div class="mt-4">
                            <p class="text-sm text-slate-500">
                                Feedback
                            </p>

                            <p class="mt-1 whitespace-pre-line text-slate-700">
                                {{ $submission->grade->feedback }}
                            </p>
                        </div>
                    @endif

                    <div class="mt-4 text-sm text-slate-500">
                        Dinilai pada:
                        {{ $submission->grade->graded_at?->format('d/m/Y H:i') }}
                    </div>
                @else
                    <p class="mt-4 text-slate-500">
                        Submission ini belum dinilai.
                    </p>
                @endif
            </div>

        </div>

        <div class="mt-6">
            @if(auth()->user()->role === 'dosen')
                <a
                    href="{{ route('dosen.assignments.submissions.index', $submission->assignment) }}"
                    class="text-sm font-medium text-slate-600 hover:text-slate-800"
                >
                    Kembali ke daftar submission
                </a>
            @else
                <a
                    href="{{ route('mahasiswa.assignments.show', $submission->assignment) }}"
                    class="text-sm font-medium text-slate-600 hover:text-slate-800"
                >
                    Kembali ke tugas
                </a>
            @endif
        </div>

    </div>
</x-layout>