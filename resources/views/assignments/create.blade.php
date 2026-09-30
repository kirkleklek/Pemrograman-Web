<x-layout>
    <div class="max-w-4xl mx-auto px-4 py-6">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">
                Tambah Tugas
            </h1>

            <p class="mt-1 text-slate-500">
                Mata Kuliah: {{ $course->name }}
            </p>
        </div>

        <form
            action="{{ route('dosen.courses.assignments.store', $course) }}"
            method="POST"
            class="space-y-6 rounded-lg border border-slate-200 bg-white p-6"
        >
            @csrf

            <div>
                <label
                    for="title"
                    class="block text-sm font-medium text-slate-700"
                >
                    Judul Tugas
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    value="{{ old('title') }}"
                    required
                    class="mt-1 block w-full rounded-lg border-slate-300"
                >

                @error('title')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="instructions"
                    class="block text-sm font-medium text-slate-700"
                >
                    Instruksi
                </label>

                <textarea
                    id="instructions"
                    name="instructions"
                    rows="6"
                    required
                    class="mt-1 block w-full rounded-lg border-slate-300"
                >{{ old('instructions') }}</textarea>

                @error('instructions')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="due_at"
                    class="block text-sm font-medium text-slate-700"
                >
                    Deadline
                </label>

                <input
                    type="datetime-local"
                    id="due_at"
                    name="due_at"
                    value="{{ old('due_at') }}"
                    required
                    class="mt-1 block w-full rounded-lg border-slate-300"
                >

                @error('due_at')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="max_score"
                    class="block text-sm font-medium text-slate-700"
                >
                    Nilai Maksimum
                </label>

                <input
                    type="number"
                    id="max_score"
                    name="max_score"
                    value="{{ old('max_score', 100) }}"
                    min="0"
                    max="100"
                    required
                    class="mt-1 block w-full rounded-lg border-slate-300"
                >

                @error('max_score')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">
                    Pengumpulan Terlambat
                </label>

                <input
                    type="hidden"
                    name="allow_late"
                    value="0"
                >

                <label class="mt-2 inline-flex items-center gap-2">
                    <input
                        type="checkbox"
                        name="allow_late"
                        value="1"
                        {{ old('allow_late', '1') == '1' ? 'checked' : '' }}
                        class="rounded border-slate-300"
                    >

                    <span class="text-sm text-slate-600">
                        Izinkan mahasiswa mengumpulkan setelah deadline
                    </span>
                </label>

                @error('allow_late')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="status"
                    class="block text-sm font-medium text-slate-700"
                >
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                    required
                    class="mt-1 block w-full rounded-lg border-slate-300"
                >
                    <option
                        value="draft"
                        {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}
                    >
                        Draft
                    </option>

                    <option
                        value="published"
                        {{ old('status') === 'published' ? 'selected' : '' }}
                    >
                        Published
                    </option>
                </select>

                @error('status')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="flex items-center justify-between pt-4">
                <a
                    href="{{ route('dosen.courses.assignments.index', $course) }}"
                    class="text-sm font-medium text-slate-600 hover:text-slate-800"
                >
                    Kembali
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-700"
                >
                    Simpan Tugas
                </button>
            </div>

        </form>

    </div>
</x-layout>