<x-layout>
    <div class="max-w-7xl mx-auto px-4 py-6">

        <div class="mb-6">
            <p class="text-sm text-slate-500">
                Mata Kuliah: {{ $course->name }}
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-800">
                Submission
            </h1>

            <p class="mt-1 text-slate-500">
                Tugas: {{ $assignment->title }}
            </p>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if($submissions->isEmpty())
            <div class="rounded-lg border border-slate-200 bg-white p-6 text-center">
                <p class="text-slate-500">
                    Belum ada mahasiswa yang mengumpulkan tugas ini.
                </p>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">
                                Mahasiswa
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">
                                File
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">
                                Dikumpulkan
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">
                                Status
                            </th>

                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">
                                Nilai
                            </th>

                            <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">

                        @foreach($submissions as $submission)
                            <tr>

                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800">
                                        {{ $submission->student->name }}
                                    </div>

                                    <div class="text-sm text-slate-500">
                                        {{ $submission->student->email }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-700">
                                        {{ $submission->original_name }}
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        {{ number_format($submission->file_size / 1024, 1) }} KB
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $submission->submitted_at->format('d/m/Y H:i') }}
                                </td>

                                <td class="px-6 py-4">
                                    @if($submission->is_late)
                                        <span class="text-sm font-medium text-red-600">
                                            Terlambat
                                        </span>
                                    @else
                                        <span class="text-sm font-medium text-green-600">
                                            Tepat waktu
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    @if($submission->grade)
                                        <span class="text-sm font-medium text-slate-700">
                                            {{ $submission->grade->score }}
                                        </span>
                                    @else
                                        <span class="text-sm text-slate-500">
                                            Belum dinilai
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <a
                                        href="{{ route('dosen.submissions.show', $submission) }}"
                                        class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                    >
                                        Lihat
                                    </a>
                                </td>

                            </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>
        @endif

        <div class="mt-6">
            <a
                href="{{ route('dosen.assignments.show', $assignment) }}"
                class="text-sm font-medium text-slate-600 hover:text-slate-800"
            >
                Kembali ke tugas
            </a>
        </div>

    </div>
</x-layout>