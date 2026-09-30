<x-layout>
    <div class="max-w-4xl mx-auto px-4 py-6">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">
                Tambah Materi
            </h1>

            <p class="mt-1 text-slate-500">
                Mata Kuliah: {{ $course->name }}
            </p>
        </div>

        <form
            action="{{ route('dosen.courses.materials.store', $course) }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6 rounded-lg border border-slate-200 bg-white p-6"
        >
            @csrf

            <div>
                <label
                    for="title"
                    class="block text-sm font-medium text-slate-700"
                >
                    Judul Materi
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
                    for="description"
                    class="block text-sm font-medium text-slate-700"
                >
                    Deskripsi
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                    class="mt-1 block w-full rounded-lg border-slate-300"
                >{{ old('description') }}</textarea>

                @error('description')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="type"
                    class="block text-sm font-medium text-slate-700"
                >
                    Tipe Materi
                </label>

                <select
                    id="type"
                    name="type"
                    class="mt-1 block w-full rounded-lg border-slate-300"
                    required
                >
                    <option value="file" {{ old('type', 'file') === 'file' ? 'selected' : '' }}>
                        File
                    </option>
                    <option value="link" {{ old('type') === 'link' ? 'selected' : '' }}>
                        Link
                    </option>
                </select>

                @error('type')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="file"
                    class="block text-sm font-medium text-slate-700"
                >
                    File Materi
                </label>

                <input
                    type="file"
                    id="file"
                    name="file"
                    accept=".pdf,.pptx"
                    class="mt-1 block w-full text-sm"
                >

                <p class="mt-1 text-xs text-slate-500">
                    Format yang diperbolehkan: PDF atau PPTX.
                </p>

                @error('file')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label
                    for="external_url"
                    class="block text-sm font-medium text-slate-700"
                >
                    URL Materi
                </label>

                <input
                    type="url"
                    id="external_url"
                    name="external_url"
                    value="{{ old('external_url') }}"
                    placeholder="https://..."
                    class="mt-1 block w-full rounded-lg border-slate-300"
                >

                @error('external_url')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="flex items-center justify-between pt-4">
                <a
                    href="{{ route('dosen.courses.materials.index', $course) }}"
                    class="text-sm font-medium text-slate-600 hover:text-slate-800"
                >
                    Kembali
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-700"
                >
                    Simpan Materi
                </button>
            </div>

        </form>

    </div>
</x-layout>