<div class="space-y-5">

    {{-- Kode --}}
    <div>
        <label
            for="code"
            class="mb-1.5 block text-sm font-medium text-slate-700"
        >
            Kode Mata Kuliah
        </label>

        <input
            type="text"
            id="code"
            name="code"
            value="{{ old('code', $course->code ?? '') }}"
            placeholder="Contoh: SI2514024"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
        >

        @error('code')
            <p class="mt-1.5 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>


    {{-- Nama --}}
    <div>
        <label
            for="name"
            class="mb-1.5 block text-sm font-medium text-slate-700"
        >
            Nama Mata Kuliah
        </label>

        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $course->name ?? '') }}"
            placeholder="Masukkan nama mata kuliah"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
        >

        @error('name')
            <p class="mt-1.5 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>


    {{-- Deskripsi --}}
    <div>
        <label
            for="description"
            class="mb-1.5 block text-sm font-medium text-slate-700"
        >
            Deskripsi
        </label>

        <textarea
            id="description"
            name="description"
            rows="4"
            placeholder="Masukkan deskripsi mata kuliah"
            class="w-full resize-y rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
        >{{ old('description', $course->description ?? '') }}</textarea>

        @error('description')
            <p class="mt-1.5 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>


    {{-- SKS --}}
    <div>
        <label
            for="sks"
            class="mb-1.5 block text-sm font-medium text-slate-700"
        >
            SKS
        </label>

        <input
            type="number"
            id="sks"
            name="sks"
            min="1"
            max="6"
            value="{{ old('sks', $course->sks ?? '') }}"
            placeholder="1 - 6"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
        >

        @error('sks')
            <p class="mt-1.5 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>


    {{-- Dosen --}}
    <div>
        <label
            for="lecturer_id"
            class="mb-1.5 block text-sm font-medium text-slate-700"
        >
            Dosen
        </label>

        <select
            id="lecturer_id"
            name="lecturer_id"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
        >
            <option value="">Pilih Dosen</option>

            @foreach ($lecturers as $lecturer)
                <option
                    value="{{ $lecturer->id }}"
                    @selected(
                        old('lecturer_id', $course->lecturer_id ?? '') == $lecturer->id
                    )
                >
                    {{ $lecturer->name }}
                </option>
            @endforeach
        </select>

        @error('lecturer_id')
            <p class="mt-1.5 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>


    {{-- Status --}}
    <div>
        <label
            for="status"
            class="mb-1.5 block text-sm font-medium text-slate-700"
        >
            Status
        </label>

        <select
            id="status"
            name="status"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
        >
            <option value="">Pilih Status</option>

            <option
                value="draft"
                @selected(old('status', $course->status ?? '') === 'draft')
            >
                Draft
            </option>

            <option
                value="active"
                @selected(old('status', $course->status ?? '') === 'active')
            >
                Active
            </option>

            <option
                value="archived"
                @selected(old('status', $course->status ?? '') === 'archived')
            >
                Archived
            </option>
        </select>

        @error('status')
            <p class="mt-1.5 text-sm text-red-600">
                {{ $message }}
            </p>
        @enderror
    </div>

</div>