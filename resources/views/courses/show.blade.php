<x-layout title="Detail Mata Kuliah">

    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Header --}}
        <div>
            <p class="text-sm font-medium text-slate-500">
                Manajemen Akademik
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                Detail Mata Kuliah
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Informasi lengkap mengenai mata kuliah yang dipilih.
            </p>
        </div>


        {{-- Detail --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="divide-y divide-slate-100">

                <div class="grid gap-2 px-6 py-4 sm:grid-cols-3">
                    <div class="text-sm font-medium text-slate-500">
                        Kode
                    </div>

                    <div class="text-sm font-semibold text-slate-900 sm:col-span-2">
                        {{ $course->code }}
                    </div>
                </div>


                <div class="grid gap-2 px-6 py-4 sm:grid-cols-3">
                    <div class="text-sm font-medium text-slate-500">
                        Nama Mata Kuliah
                    </div>

                    <div class="text-sm text-slate-900 sm:col-span-2">
                        {{ $course->name }}
                    </div>
                </div>


                <div class="grid gap-2 px-6 py-4 sm:grid-cols-3">
                    <div class="text-sm font-medium text-slate-500">
                        Deskripsi
                    </div>

                    <div class="whitespace-pre-line text-sm text-slate-700 sm:col-span-2">
                        {{ $course->description ?? '-' }}
                    </div>
                </div>


                <div class="grid gap-2 px-6 py-4 sm:grid-cols-3">
                    <div class="text-sm font-medium text-slate-500">
                        SKS
                    </div>

                    <div class="text-sm text-slate-900 sm:col-span-2">
                        {{ $course->sks }}
                    </div>
                </div>


                <div class="grid gap-2 px-6 py-4 sm:grid-cols-3">
                    <div class="text-sm font-medium text-slate-500">
                        Dosen
                    </div>

                    <div class="text-sm text-slate-900 sm:col-span-2">
                        {{ $course->lecturer?->name ?? '-' }}
                    </div>
                </div>


                <div class="grid gap-2 px-6 py-4 sm:grid-cols-3">
                    <div class="text-sm font-medium text-slate-500">
                        Status
                    </div>

                    <div class="sm:col-span-2">

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

                    </div>
                </div>

            </div>


            {{-- Action --}}
            <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">

                <a
                    href="{{ route('admin.courses.index') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-white"
                >
                    Kembali
                </a>

                <a
                    href="{{ route('admin.courses.edit', $course) }}"
                    class="rounded-lg bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Edit Mata Kuliah
                </a>

            </div>

        </div>

    </div>

</x-layout>