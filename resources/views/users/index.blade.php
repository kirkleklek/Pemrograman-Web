<x-layout title="Daftar Pengguna">

    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">
                    Manajemen Sistem
                </p>

                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                    Daftar Pengguna
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Kelola data pengguna dan role dalam KampusLMS.
                </p>
            </div>

            <a
                href="{{ route('users.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
            >
                + Tambah Pengguna
            </a>
        </div>


        {{-- Search & Filter --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <form
                action="{{ route('users.index') }}"
                method="GET"
                class="grid gap-4 md:grid-cols-[1fr_220px_auto_auto]"
            >

                <div>
                    <label
                        for="q"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Cari Pengguna
                    </label>

                    <input
                        type="text"
                        id="q"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Nama atau email"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >
                </div>


                <div>
                    <label
                        for="role"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Role
                    </label>

                    <select
                        id="role"
                        name="role"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >
                        <option value="">Semua Role</option>

                        @foreach ($roles as $role)
                            <option
                                value="{{ $role }}"
                                @selected(request('role') === $role)
                            >
                                {{ ucfirst($role) }}
                            </option>
                        @endforeach
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
                        href="{{ route('users.index') }}"
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
                                Nama
                            </th>

                            <th class="px-6 py-4 font-semibold text-slate-700">
                                Email
                            </th>

                            <th class="px-6 py-4 font-semibold text-slate-700">
                                NIM/NIP
                            </th>

                            <th class="px-6 py-4 font-semibold text-slate-700">
                                Role
                            </th>

                            <th class="px-6 py-4 text-right font-semibold text-slate-700">
                                Aksi
                            </th>
                        </tr>
                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse ($users as $user)

                            <tr class="transition hover:bg-slate-50">

                                <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-900">
                                    {{ $user->name }}
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    {{ $user->email }}
                                </td>

                                <td class="px-6 py-4 text-slate-700">
                                    {{ $user->nim_nip ?? '-' }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">

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

                                </td>

                                <td class="whitespace-nowrap px-6 py-4">

                                    <div class="flex items-center justify-end gap-2">

                                        <a
                                            href="{{ route('users.show', $user) }}"
                                            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                        >
                                            Detail
                                        </a>

                                        <a
                                            href="{{ route('users.edit', $user) }}"
                                            class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-800"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            action="{{ route('users.destroy', $user) }}"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')"
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
                                    colspan="5"
                                    class="px-6 py-12 text-center"
                                >
                                    <p class="font-medium text-slate-700">
                                        Belum ada data pengguna.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Silakan tambahkan pengguna terlebih dahulu.
                                    </p>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $users->links() }}
            </div>

        </div>

    </div>

</x-layout>