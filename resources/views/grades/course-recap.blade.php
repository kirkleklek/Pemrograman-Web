<x-layout title="Rekap Nilai">

    <div class="space-y-6">

        <div>
            <a
                href="{{ route('dosen.courses.index') }}"
                class="text-sm text-blue-600 hover:underline"
            >
                &larr; Kembali ke Mata Kuliah Saya
            </a>

            <h1 class="mt-3 text-3xl font-bold text-slate-900">
                Rekap Nilai
            </h1>

            <p class="mt-2 text-slate-600">
                {{ $course->code }} - {{ $course->name }}
            </p>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">

                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3">Mahasiswa</th>
                            <th class="px-5 py-3">Tugas</th>
                            <th class="px-5 py-3">Nilai</th>
                            <th class="px-5 py-3">Feedback</th>
                            <th class="px-5 py-3">Tanggal Penilaian</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($submissions as $submission)
                            <tr>
                                <td class="px-5 py-4">
                                    {{ $submission->student?->name ?? '-' }}
                                </td>

                                <td class="px-5 py-4">
                                    {{ $submission->assignment?->title ?? '-' }}
                                </td>

                                <td class="px-5 py-4 font-semibold">
                                    @if ($submission->grade)
                                        {{ number_format((float) $submission->grade->score, 2) }}
                                    @else
                                        <span class="text-amber-600">
                                            Belum dinilai
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-4">
                                    {{ $submission->grade?->feedback ?: '-' }}
                                </td>

                                <td class="px-5 py-4">
                                    {{ $submission->grade?->graded_at?->format('d/m/Y H:i') ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="px-5 py-10 text-center text-slate-500"
                                >
                                    Belum ada pengumpulan tugas pada mata kuliah ini.
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $submissions->links() }}
            </div>

        </div>
    </div>

</x-layout>
