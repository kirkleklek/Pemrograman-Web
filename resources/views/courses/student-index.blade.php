<x-layout title="Mata Kuliah Saya">

    <div class="space-y-6">

        <div>
            <p class="text-sm font-medium text-slate-500">
                Aktivitas Pembelajaran
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                Mata Kuliah Saya
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Daftar mata kuliah yang kamu ikuti di KampusLMS.
            </p>
        </div>

        {{-- Pencarian --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form
                action="{{ route('mahasiswa.courses.index') }}"
                method="GET"
                class="flex flex-col gap-3 sm:flex-row"
            >
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari kode atau nama mata kuliah"
                    aria-label="Cari mata kuliah"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                >

                <button
                    type="submit"
                    class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Cari
                </button>

                <a
                    href="{{ route('mahasiswa.courses.index') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Reset
                </a>
            </form>
        </div>

        {{-- Daftar mata kuliah yang diikuti --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">

                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 font-semibold text-slate-700">
                                Kode
                            </th>
                            <th class="px-6 py-4 font-semibold text-slate-700">
                                Nama Mata Kuliah
                            </th>
                            <th class="px-6 py-4 font-semibold text-slate-700">
                                SKS
                            </th>
                            <th class="px-6 py-4 font-semibold text-slate-700">
                                Dosen Pengampu
                            </th>
                            <th class="px-6 py-4 font-semibold text-slate-700">
                                Status
                            </th>
                            <th class="px-6 py-4 text-right font-semibold text-slate-700">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($courses as $course)
                            @can('view', $course)
                                <tr class="hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-900">
                                        {{ $course->code }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        {{ $course->name }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        {{ $course->sks }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-700">
                                        {{ $course->lecturer?->name ?? '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">
                                        @if ($course->status === 'active')
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                Active
                                            </span>
                                        @elseif ($course->status === 'draft')
                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                                Draft
                                            </span>
                                        @else
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                Archived
                                            </span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a
                                                href="{{ route('mahasiswa.courses.materials.index', $course) }}"
                                                class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            >
                                                Materi
                                            </a>

                                            <a
                                                href="{{ route('mahasiswa.courses.assignments.index', $course) }}"
                                                class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                                            >
                                                Tugas
                                            </a>
                                        </div>
                                    </td>

                                </tr>
                            @endcan
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <p class="font-medium text-slate-700">
                                        Belum ada mata kuliah yang diikuti.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Mata kuliah akan muncul setelah kamu didaftarkan.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $courses->links() }}
            </div>

        </div>

    </div>

</x-layout>