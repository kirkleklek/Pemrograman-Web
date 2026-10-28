<div>
    {{-- Pencarian dan filter status --}}
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="w-full md:max-w-md">
            <label for="submission-search" class="sr-only">
                Cari mahasiswa
            </label>

            <input
                id="submission-search"
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama atau email mahasiswa..."
                class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
            >
        </div>

        <div class="w-full md:w-56">
            <label for="submission-status" class="sr-only">
                Filter status pengumpulan
            </label>

            <select
                id="submission-status"
                wire:model.live="status"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
            >
                <option value="semua">Semua mahasiswa</option>
                <option value="sudah">Sudah mengumpulkan</option>
                <option value="belum">Belum mengumpulkan</option>
            </select>
        </div>
    </div>

    {{-- Indikator ketika Livewire sedang memuat data --}}
    <div
        wire:loading.delay
        wire:target="search,status,gotoPage,previousPage,nextPage"
        class="mb-3 text-sm text-slate-500"
    >
        Memperbarui daftar mahasiswa...
    </div>

    {{-- Daftar mahasiswa --}}
    @if ($students->isEmpty())
        <div class="rounded-lg border border-slate-200 bg-white p-6 text-center">
            @if (trim($search) !== '' || $status !== 'semua')
                <p class="text-slate-500">
                    Tidak ada mahasiswa yang sesuai dengan pencarian atau filter.
                </p>
            @else
                <p class="text-slate-500">
                    Belum ada mahasiswa yang terdaftar pada mata kuliah ini.
                </p>
            @endif
        </div>
    @else
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
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
                    @foreach ($students as $student)
                        @php
                            // Submission terbaru untuk tugas yang sedang dibuka.
                            $submission = $student->submissions->first();
                        @endphp

                        <tr wire:key="student-{{ $student->id }}">
                            {{-- Identitas mahasiswa --}}
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-800">
                                    {{ $student->name }}
                                </div>

                                <div class="text-sm text-slate-500">
                                    {{ $student->email }}
                                </div>
                            </td>

                            {{-- File submission --}}
                            <td class="px-6 py-4">
                                @if ($submission)
                                    <div class="text-sm text-slate-700">
                                        {{ $submission->original_name }}
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        {{ number_format(($submission->file_size ?? 0) / 1024, 1) }}
                                        KB
                                    </div>
                                @else
                                    <span class="text-sm text-slate-500">
                                        —
                                    </span>
                                @endif
                            </td>

                            {{-- Waktu pengumpulan --}}
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $submission?->submitted_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>

                            {{-- Status pengumpulan --}}
                            <td class="px-6 py-4">
                                @if (! $submission)
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                        Belum mengumpulkan
                                    </span>
                                @elseif ($submission->is_late)
                                    <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700">
                                        Terlambat
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700">
                                        Tepat waktu
                                    </span>
                                @endif
                            </td>

                            {{-- Nilai submission --}}
                            <td class="px-6 py-4">
                                @if ($submission?->grade)
                                    <span class="text-sm font-medium text-slate-700">
                                        {{ $submission->grade->score }}
                                    </span>
                                @elseif ($submission)
                                    <span class="text-sm text-slate-500">
                                        Belum dinilai
                                    </span>
                                @else
                                    <span class="text-sm text-slate-500">
                                        —
                                    </span>
                                @endif
                            </td>

                            {{-- Aksi --}}
                            <td class="px-6 py-4 text-right">
                                @if ($submission)
                                    @can('view', $submission)
                                        <a
                                            href="{{ route('dosen.submissions.show', $submission) }}"
                                            class="text-sm font-medium text-blue-600 hover:text-blue-800"
                                        >
                                            Lihat
                                        </a>
                                    @endcan

                                    @can('download', $submission)
                                        <a
                                            href="{{ route('submissions.download', $submission) }}"
                                            class="ml-3 whitespace-nowrap text-sm font-medium text-blue-600 hover:text-blue-800"
                                        >
                                            Unduh berkas
                                        </a>
                                    @endcan
                                @else
                                    <span class="text-sm text-slate-400">
                                        Tidak ada submission
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Informasi dan navigasi pagination --}}
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                Menampilkan
                {{ $students->firstItem() ?? 0 }}
                sampai
                {{ $students->lastItem() ?? 0 }}
                dari
                {{ $students->total() }}
                mahasiswa
            </p>

            <div>
                {{ $students->links() }}
            </div>
        </div>
    @endif
</div>