<x-layout title="Edit Pengguna">

    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Header --}}
        <div>
            <p class="text-sm font-medium text-slate-500">
                Manajemen Sistem
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                Edit Pengguna
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Perbarui informasi pengguna yang dipilih.
            </p>
        </div>


        {{-- Validation Error --}}
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">
                <p class="font-semibold text-red-700">
                    Data belum valid
                </p>

                <ul class="mt-2 space-y-1 text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- Form --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <form
                action="{{ route('users.update', $user) }}"
                method="POST"
                class="space-y-6"
            >
                @csrf
                @method('PUT')


                {{-- Nama --}}
                <div>
                    <label
                        for="name"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Nama
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        placeholder="Masukkan nama pengguna"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >

                    @error('name')
                        <p class="mt-1.5 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- Email --}}
                <div>
                    <label
                        for="email"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        placeholder="contoh@email.com"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >

                    @error('email')
                        <p class="mt-1.5 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- NIM/NIP --}}
                <div>
                    <label
                        for="nim_nip"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        NIM/NIP
                    </label>

                    <input
                        type="text"
                        id="nim_nip"
                        name="nim_nip"
                        value="{{ old('nim_nip', $user->nim_nip) }}"
                        placeholder="Masukkan NIM atau NIP"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >

                    @error('nim_nip')
                        <p class="mt-1.5 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- Role --}}
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
                        <option value="">
                            Pilih Role
                        </option>

                        @foreach ($roles as $role)
                            <option
                                value="{{ $role }}"
                                @selected(old('role', $user->role) === $role)
                            >
                                {{ ucfirst($role) }}
                            </option>
                        @endforeach
                    </select>

                    @error('role')
                        <p class="mt-1.5 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- Password --}}
                <div>
                    <label
                        for="password"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Password Baru
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Kosongkan jika tidak ingin mengubah"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >

                    @error('password')
                        <p class="mt-1.5 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- Password Confirmation --}}
                <div>
                    <label
                        for="password_confirmation"
                        class="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Konfirmasi Password Baru
                    </label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Masukkan ulang password baru"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                    >
                </div>


                {{-- Action --}}
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-6">

                    <a
                        href="{{ route('users.show', $user) }}"
                        class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                    >
                        Simpan Perubahan
                    </button>

                </div>

            </form>

        </div>

    </div>

</x-layout>