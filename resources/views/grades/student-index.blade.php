<x-layout title="Nilai Saya">

    <div class="space-y-6">

        <div>
            <h1 class="text-3xl font-bold text-slate-900">
                Nilai Saya
            </h1>

            <p class="mt-2 text-sm text-slate-600">
                Daftar nilai dan feedback dosen untuk tugas yang telah Anda kumpulkan.
            </p>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">

                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3">Mata Kuliah</th>
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
                                    {{ $submission->assignment?->course?->name ?? '-' }}
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
                                    Belum ada tugas yang dapat ditampilkan.
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
