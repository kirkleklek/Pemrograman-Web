<x-layout>
    @php
        $routePrefix = auth()->user()->role;
    @endphp

    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Materi</h1>
                <p class="text-slate-500 mt-1">Mata Kuliah: {{ $course->name }}</p>
            </div>

            @can('create', [\App\Models\Material::class, $course])
                <a href="{{ route($routePrefix . '.courses.materials.create', $course) }}"
                   class="px-4 py-2 rounded-lg bg-slate-800 text-white hover:bg-slate-700">
                    Tambah Materi
                </a>
            @endcan
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if($materials->isEmpty())
            <div class="rounded-lg border border-slate-200 bg-white p-6 text-center">
                <p class="text-slate-500">Belum ada materi untuk mata kuliah ini.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Judul</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Tipe</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Dibuat</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">
                        @foreach($materials as $material)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800">{{ $material->title }}</div>
                                    @if($material->description)
                                        <div class="mt-1 text-sm text-slate-500">{{ $material->description }}</div>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ strtoupper($material->type) }}
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $material->created_at->format('d/m/Y') }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    @can('view', $material)
                                        <a href="{{ route($routePrefix . '.materials.show', $material) }}"
                                           class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            Lihat
                                        </a>
                                    @endcan

                                    @can('update', $material)
                                        <a href="{{ route($routePrefix . '.materials.edit', $material) }}"
                                           class="ml-3 text-sm font-medium text-amber-600 hover:text-amber-800">
                                            Edit
                                        </a>
                                    @endcan

                                    @can('delete', $material)
                                        <form action="{{ route($routePrefix . '.materials.destroy', $material) }}"
                                              method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="ml-3 text-sm font-medium text-red-600 hover:text-red-800">
                                                Hapus
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-layout>
