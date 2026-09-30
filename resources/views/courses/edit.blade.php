<x-layout title="Edit Mata Kuliah">

    <div class="mx-auto max-w-3xl space-y-6">

        {{-- Header --}}
        <div>
            <p class="text-sm font-medium text-slate-500">
                Manajemen Akademik
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                Edit Mata Kuliah
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Perbarui informasi mata kuliah yang dipilih.
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
                action="{{ route('admin.courses.update', $course) }}"
                method="POST"
                class="space-y-6"
            >
                @csrf
                @method('PUT')

                <x-courses.form
                    :course="$course"
                    :lecturers="$lecturers"
                />

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-6">

                    <a
                        href="{{ route('admin.courses.show', $course) }}"
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