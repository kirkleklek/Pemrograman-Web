<x-layout title="Mata Kuliah Saya">

    <div class="space-y-6">

        <div>
            <p class="text-sm font-medium text-slate-500">
                Manajemen Akademik
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                Mata Kuliah Saya
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Daftar mata kuliah yang kamu ampu.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form
                action="{{ route('dosen.courses.index') }}"
                method="GET"
                class="flex flex-col gap-3 sm:flex-row"
            >
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari kode atau nama mata kuliah"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm"
                >

                <button
                    type="submit"
                    class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Cari
                </button>

                <a
                    href="{{ route('dosen.courses.index') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Reset
                </a>
            </form>
        </div>

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
                                Status
                            </th>

                            <th class="px-6 py-4 text-right font-semibold text-slate-700">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse ($courses as $course)
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

                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">

                                        <a
                                            href="{{ route('dosen.courses.enrollments.index', $course) }}"
                                            class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                                        >
                                            Kelola Peserta
                                        </a>

                                        <a
                                            href="{{ route('dosen.courses.materials.index', $course) }}"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Materi
                                        </a>

                                        <a
                                            href="{{ route('dosen.courses.assignments.index', $course) }}"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Tugas
                                        </a>

                                        <a
                                            href="{{ route('dosen.courses.grades.index', $course) }}"
                                            class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                        >
                                            Rekap Nilai
                                        </a>

                                    </div>
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <p class="font-medium text-slate-700">
                                        Belum ada mata kuliah yang kamu ampu.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Mata kuliah akan muncul jika admin menetapkan kamu sebagai dosen pengampu.
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
