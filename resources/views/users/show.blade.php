<x-layout title="Detail Pengguna">

    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Header --}}
        <div>
            <p class="text-sm font-medium text-slate-500">
                Manajemen Sistem
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                Detail Pengguna
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Informasi lengkap mengenai pengguna yang dipilih.
            </p>
        </div>


        {{-- Detail --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="divide-y divide-slate-100">

                <div class="grid gap-2 px-6 py-4 sm:grid-cols-3">
                    <div class="text-sm font-medium text-slate-500">
                        Nama
                    </div>

                    <div class="text-sm font-semibold text-slate-900 sm:col-span-2">
                        {{ $user->name }}
                    </div>
                </div>


                <div class="grid gap-2 px-6 py-4 sm:grid-cols-3">
                    <div class="text-sm font-medium text-slate-500">
                        Email
                    </div>

                    <div class="text-sm text-slate-900 sm:col-span-2">
                        {{ $user->email }}
                    </div>
                </div>


                <div class="grid gap-2 px-6 py-4 sm:grid-cols-3">
                    <div class="text-sm font-medium text-slate-500">
                        NIM/NIP
                    </div>

                    <div class="text-sm text-slate-900 sm:col-span-2">
                        {{ $user->nim_nip ?? '-' }}
                    </div>
                </div>


                <div class="grid gap-2 px-6 py-4 sm:grid-cols-3">
                    <div class="text-sm font-medium text-slate-500">
                        Role
                    </div>

                    <div class="sm:col-span-2">

                        @if ($user->role === 'admin')
                            <span class="inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">
                                Admin
                            </span>
                        @elseif ($user->role === 'dosen')
                            <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                Dosen
                            </span>
                        @else
                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                Mahasiswa
                            </span>
                        @endif

                    </div>
                </div>

            </div>


            {{-- Action --}}
            <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">

                <a
                    href="{{ route('admin.users.index') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-white"
                >
                    Kembali
                </a>

                <a
                    href="{{ route('admin.users.edit', $user) }}"
                    class="rounded-lg bg-slate-900 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Edit Pengguna
                </a>

            </div>

        </div>

    </div>

</x-layout>
