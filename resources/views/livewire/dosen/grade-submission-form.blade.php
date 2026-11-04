<div class="rounded-xl border border-slate-200 bg-white p-6">
    <div class="mb-5">
        <h2 class="text-lg font-semibold text-slate-800">
            Form Penilaian
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Berikan nilai dan umpan balik untuk submission mahasiswa.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-5">

        {{-- Nilai --}}
        <div>
            <label for="grade-score"
                   class="mb-1 block text-sm font-medium text-slate-700">
                Nilai (0–100)
            </label>

            <input
                id="grade-score"
                type="number"
                wire:model="score"
                min="0"
                max="100"
                step="0.01"
                required
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >

            @error('score')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Pilih template --}}
        <div>
            <label for="feedback-template"
                   class="mb-1 block text-sm font-medium text-slate-700">
                Gunakan Template Umpan Balik
            </label>

            <select
                id="feedback-template"
                wire:model.live="feedbackTemplateId"
                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                <option value="">
                    Pilih template (opsional)
                </option>

                @foreach ($templates as $template)
                    <option value="{{ $template->id }}">
                        {{ $template->title }}
                    </option>
                @endforeach
            </select>

            <p class="mt-1 text-xs text-slate-500">
                Memilih template akan mengisi kolom feedback di bawah.
                Kamu tetap bisa mengubah isinya.
            </p>
        </div>

        {{-- Feedback --}}
        <div>
            <label for="grade-feedback"
                   class="mb-1 block text-sm font-medium text-slate-700">
                Umpan Balik
            </label>

            <textarea
                id="grade-feedback"
                wire:model="feedback"
                rows="5"
                placeholder="Tulis umpan balik untuk mahasiswa..."
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            ></textarea>

            @error('feedback')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end">
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
                class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="save">
                    Simpan Penilaian
                </span>

                <span wire:loading wire:target="save">
                    Menyimpan...
                </span>
            </button>
        </div>
    </form>
</div>