<x-layout>
    <div class="max-w-4xl mx-auto px-4 py-6">

        <div class="mb-6">
            <p class="text-sm text-slate-500">
                Mata Kuliah: {{ $course->name }}
            </p>

            <h1 class="mt-1 text-2xl font-bold text-slate-800">
                {{ $material->title }}
            </h1>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-lg border border-slate-200 bg-white p-6">

            @if($material->description)
                <div class="mb-6">
                    <h2 class="text-sm font-semibold text-slate-700">
                        Deskripsi
                    </h2>

                    <p class="mt-2 whitespace-pre-line text-slate-600">
                        {{ $material->description }}
                    </p>
                </div>
            @endif

            <div class="mb-6">
                <h2 class="text-sm font-semibold text-slate-700">
                    Tipe Materi
                </h2>

                <p class="mt-1 text-slate-600">
                    {{ strtoupper($material->type) }}
                </p>
            </div>

            @if($material->type === 'file')
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-700">
                        {{ $material->original_name }}
                    </p>

                    @if($material->file_size)
                        <p class="mt-1 text-xs text-slate-500">
                            {{ number_format($material->file_size / 1024, 1) }} KB
                        </p>
                    @endif

                    <p class="mt-2 text-xs text-slate-500">
                        File tersimpan secara privat dan tidak menggunakan URL publik.
                    </p>
                </div>
            @elseif($material->type === 'link')
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm text-slate-500 mb-2">
                        Tautan materi:
                    </p>

                    <a
                        href="{{ $material->external_url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="break-all text-blue-600 hover:text-blue-800"
                    >
                        {{ $material->external_url }}
                    </a>
                </div>
            @endif

            <div class="mt-6 text-sm text-slate-500">
                Diunggah pada {{ $material->created_at->format('d/m/Y H:i') }}
            </div>
        </div>

        <div class="mt-6 flex items-center justify-between">

            <a
                href="{{ auth()->user()->role === 'dosen'
                    ? route('dosen.courses.materials.index', $course)
                    : route('mahasiswa.courses.materials.index', $course) }}"
                class="text-sm font-medium text-slate-600 hover:text-slate-800"
            >
                Kembali
            </a>

            @if(auth()->user()->role === 'dosen')
                <a
                    href="{{ route('dosen.materials.edit', $material) }}"
                    class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
                >
                    Edit Materi
                </a>
            @endif

        </div>
    </div>
</x-layout>