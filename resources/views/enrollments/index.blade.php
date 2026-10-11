<x-layout>
    @php
        $routePrefix = auth()->user()->role === 'admin'
            ? 'admin'
            : 'dosen';
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 p-6">
        <div>
            <a href="{{ route('dashboard') }}"
                class="text-sm text-blue-600 hover:underline">
                &larr; Kembali ke Dashboard
            </a>

            <h1 class="mt-3 text-2xl font-bold">
                Kelola Peserta Mata Kuliah
            </h1>

            <p class="mt-2 text-gray-600">
                {{ $course->code }} - {{ $course->name }}
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded border border-red-300 bg-red-50 p-4 text-red-800">
                <p class="font-semibold">
                    Ada kesalahan pada input:
                </p>

                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="rounded-lg border bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">
                Tambahkan Mahasiswa
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Pilih mahasiswa yang belum terdaftar pada mata kuliah ini.
            </p>

            @if ($availableStudents->isEmpty())
                <p class="mt-4 text-sm text-gray-600">
                    Tidak ada mahasiswa yang tersedia untuk didaftarkan.
                </p>
            @else
                <form
                    action="{{ route($routePrefix . '.courses.enrollments.store', $course) }}"
                    method="POST"
                    class="mt-4 space-y-4"
                >
                    @csrf

                    <div>
                        <label for="student_id"
                            class="mb-2 block text-sm font-medium">
                            Mahasiswa
                        </label>

                        <select
                            id="student_id"
                            name="student_id"
                            required
                            class="w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="">
                                -- Pilih mahasiswa --
                            </option>

                            @foreach ($availableStudents as $student)
                                <option
                                    value="{{ $student->id }}"
                                    @selected(old('student_id') == $student->id)
                                >
                                    {{ $student->name }}
                                    ({{ $student->nim_nip ?? '-' }})
                                    - {{ $student->email }}
                                </option>
                            @endforeach
                        </select>

                        @error('student_id')
                            <p class="mt-1 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                    >
                        Daftarkan Mahasiswa
                    </button>
                </form>
            @endif
        </section>

        <section class="rounded-lg border bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold">
                Mahasiswa Terdaftar
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Total peserta terdaftar:
                {{ $students->total() }}
            </p>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50">
                            <th class="p-3">No.</th>
                            <th class="p-3">Nama</th>
                            <th class="p-3">NIM</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">Tanggal Pendaftaran</th>
                            <th class="p-3">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($students as $student)
                            <tr class="border-b">
                                <td class="p-3">
                                    {{ $students->firstItem() + $loop->index }}
                                </td>

                                <td class="p-3">
                                    {{ $student->name }}
                                </td>

                                <td class="p-3">
                                    {{ $student->nim_nip ?? '-' }}
                                </td>

                                <td class="p-3">
                                    {{ $student->email }}
                                </td>

                                <td class="p-3">
                                    {{ $student->pivot->enrolled_at
                                        ? \Illuminate\Support\Carbon::parse($student->pivot->enrolled_at)->format('d-m-Y H:i')
                                        : '-' }}
                                </td>

                                <td class="p-3">
                                    <form
                                        action="{{ route($routePrefix . '.courses.enrollments.destroy', [
                                            'course' => $course,
                                            'student' => $student,
                                        ]) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin mengeluarkan mahasiswa ini dari mata kuliah?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="rounded-md bg-red-600 px-3 py-2 text-white hover:bg-red-700"
                                        >
                                            Keluarkan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6"
                                    class="p-4 text-center text-gray-500">
                                    Belum ada mahasiswa yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $students->links() }}
            </div>
        </section>
    </div>
</x-layout>