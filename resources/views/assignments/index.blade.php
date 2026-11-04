<x-layout>
    @php
        $routePrefix = auth()->user()->role;
    @endphp

    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Tugas</h1>
                <p class="text-slate-500 mt-1">Mata Kuliah: {{ $course->name }}</p>
            </div>

            @if(in_array(auth()->user()->role, ['admin', 'dosen'], true))
                @can('create', [\App\Models\Assignment::class, $course])
                    <a href="{{ route($routePrefix . '.courses.assignments.create', $course) }}"
                       class="px-4 py-2 rounded-lg bg-slate-800 text-white hover:bg-slate-700">
                        Tambah Tugas
                    </a>
                @endcan
            @endif
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if($assignments->isEmpty())
            <div class="rounded-lg border border-slate-200 bg-white p-6 text-center">
                <p class="text-slate-500">Belum ada tugas untuk mata kuliah ini.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Judul</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Deadline</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-700">Status</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-slate-700">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200">
                        @foreach($assignments as $assignment)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-800">{{ $assignment->title }}</div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        Nilai maksimum: {{ $assignment->max_score }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-sm text-slate-600">
                                    {{ $assignment->due_at->format('d/m/Y H:i') }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-600">{{ ucfirst($assignment->status) }}</span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    @can('view', $assignment)
                                        <a href="{{ route($routePrefix . '.assignments.show', $assignment) }}"
                                           class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                            Lihat
                                        </a>
                                    @endcan

                                    @if(in_array(auth()->user()->role, ['admin', 'dosen'], true))
                                        @can('update', $assignment)
                                            <a href="{{ route($routePrefix . '.assignments.edit', $assignment) }}"
                                               class="ml-3 text-sm font-medium text-amber-600 hover:text-amber-800">
                                                Edit
                                            </a>
                                        @endcan

                                        @can('delete', $assignment)
                                            <form action="{{ route($routePrefix . '.assignments.destroy', $assignment) }}"
                                                  method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="ml-3 text-sm font-medium text-red-600 hover:text-red-800"
                                                        onclick="return confirm('Yakin ingin menghapus tugas ini?')">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $assignments->links() }}
            </div>
        @endif
    </div>
</x-layout>
