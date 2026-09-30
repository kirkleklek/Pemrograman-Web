<x-layout>
    <div class="max-w-4xl mx-auto px-4 py-6">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-800">
                Edit Materi
            </h1>

            <p class="mt-1 text-slate-500">
                Mata Kuliah: {{ $course->name }}
            </p>
        </div>

        <form
            action="{{ route('dosen.materials.update', $material) }}"
            method="POST"
            enctype="multipart/form-data"
            class="space-y-6 rounded-lg border border-slate-200 bg-white p-6"
        >
            @csrf
            @method('PUT')

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
                    value="{{ old('title', $material->title) }}"
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
                >{{ old('description', $material->description) }}</textarea>

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
                    <option
                        value="file"
                        {{ old('type', $material->type) === 'file' ? 'selected' : '' }}
                    >
                        File
                    </option>

                    <option
                        value="link"
                        {{ old('type', $material->type) === 'link' ? 'selected' : '' }}
                    >
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
                    Ganti File Materi
                </label>

                <input
                    type="file"
                    id="file"
                    name="file"
                    accept=".pdf,.pptx"
                    class="mt-1 block w-full text-sm"
                >

                <p class="mt-1 text-xs text-slate-500">
                    Kosongkan jika ingin mempertahankan file yang sekarang.
                    Format: PDF atau PPTX.
                </p>

                @if($material->type === 'file' && $material->original_name)
                    <p class="mt-2 text-sm text-slate-600">
                        File saat ini:
                        <span class="font-medium">
                            {{ $material->original_name }}
                        </span>
                    </p>
                @endif

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
                    value="{{ old('external_url', $material->external_url) }}"
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
                    href="{{ route('dosen.materials.show', $material) }}"
                    class="text-sm font-medium text-slate-600 hover:text-slate-800"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-slate-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-slate-700"
                >
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>
</x-layout>