<x-layout title="Daftar Mata Kuliah">

    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">
                    Manajemen Akademik
                </p>

                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                    Daftar Mata Kuliah
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Kelola data mata kuliah yang tersedia di KampusLMS.
                </p>
            </div>

            <a
                href="{{ route('admin.courses.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
            >
                + Tambah Mata Kuliah
            </a>
        </div>


        {{-- Search & Filter --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <form
                action="{{ route('admin.courses.index') }}"
                method="GET"
                class="grid gap-4 md:grid-cols-[1fr_220px_auto_auto]"
            >

                <div>
                    <label
                        for="q"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Cari Mata Kuliah
                    </label>

                    <input
                        type="text"
                        id="q"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Kode atau nama mata kuliah"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >
                </div>

                <div>
                    <label
                        for="status"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >
                        <option value="">Semua Status</option>

                        <option
                            value="draft"
                            @selected(request('status') === 'draft')
                        >
                            Draft
                        </option>

                        <option
                            value="active"
                            @selected(request('status') === 'active')
                        >
                            Active
                        </option>

                        <option
                            value="archived"
                            @selected(request('status') === 'archived')
                        >
                            Archived
                        </option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Cari
                    </button>
                </div>

                <div class="flex items-end">
                    <a
                        href="{{ route('admin.courses.index') }}"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Reset
                    </a>
                </div>

            </form>

        </div>


        {{-- Table --}}
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
                                Dosen
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

                            <tr class="transition hover:bg-slate-50">

                                <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-900">
                                    {{ $course->code }}
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    {{ $course->name }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-slate-700">
                                    {{ $course->sks }}
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    {{ $course->lecturer?->name ?? '-' }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">

                                    @if ($course->status === 'active')
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Active
                                        </span>
                                    @elseif ($course->status === 'draft')
                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            Draft
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            Archived
                                        </span>
                                    @endif

                                </td>

                                <td class="whitespace-nowrap px-6 py-4">

                                    <div class="flex items-center justify-end gap-2">

                                        <a
                                            href="{{ route('admin.courses.show', $course) }}"
                                            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                        >
                                            Detail
                                        </a>

                                        <a
                                            href="{{ route('admin.courses.edit', $course) }}"
                                            class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-800"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            action="{{ route('admin.courses.destroy', $course) }}"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus mata kuliah ini?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-50"
                                            >
                                                Hapus
                                            </button>
                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-12 text-center"
                                >
                                    <p class="font-medium text-slate-700">
                                        Belum ada data mata kuliah.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Silakan tambahkan mata kuliah terlebih dahulu.
                                    </p>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{-- Pagination --}}
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $courses->links() }}
            </div>

        </div>

    </div>

</x-layout>