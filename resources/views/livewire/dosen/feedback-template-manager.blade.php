<div class="space-y-6">

    {{-- Form tambah dan edit template --}}
    <section class="rounded-xl border border-slate-200 bg-white p-6">
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-slate-800">
                {{ $editingId !== null ? 'Edit Template Umpan Balik' : 'Tambah Template Umpan Balik' }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Simpan komentar yang sering digunakan agar bisa dipakai kembali saat menilai tugas.
            </p>
        </div>

        <form wire:submit="save" class="space-y-4">

            {{-- Judul --}}
            <div>
                <label for="feedback-title"
                       class="mb-1 block text-sm font-medium text-slate-700">
                    Judul Template
                </label>

                <input
                    id="feedback-title"
                    type="text"
                    wire:model="title"
                    maxlength="100"
                    placeholder="Contoh: Jawaban Sudah Baik"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >

                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Isi --}}
            <div>
                <label for="feedback-content"
                       class="mb-1 block text-sm font-medium text-slate-700">
                    Isi Umpan Balik
                </label>

                <textarea
                    id="feedback-content"
                    wire:model="content"
                    rows="5"
                    maxlength="5000"
                    placeholder="Tuliskan komentar yang nantinya dapat digunakan kembali..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                ></textarea>

                @error('content')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tombol form --}}
            <div class="flex flex-wrap gap-3">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="save">
                        {{ $editingId !== null ? 'Simpan Perubahan' : 'Tambah Template' }}
                    </span>

                    <span wire:loading wire:target="save">
                        Menyimpan...
                    </span>
                </button>

                @if ($editingId !== null)
                    <button
                        type="button"
                        wire:click="cancelEdit"
                        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        Batal
                    </button>
                @endif
            </div>
        </form>
    </section>

    {{-- Daftar template --}}
    <section>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-800">
                    Template Saya
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Hanya template milik akun dosen yang sedang login yang ditampilkan.
                </p>
            </div>
        </div>

        @if ($templates->isEmpty())
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center">
                <h3 class="font-medium text-slate-700">
                    Belum ada template umpan balik
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    Tambahkan template melalui form di atas.
                </p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($templates as $template)
                    <article
                        wire:key="feedback-template-{{ $template->id }}"
                        class="rounded-xl border border-slate-200 bg-white p-5"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-slate-800">
                                    {{ $template->title }}
                                </h3>

                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $template->content }}</p>

                                <p class="mt-3 text-xs text-slate-400">
                                    Diperbarui:
                                    {{ $template->updated_at->format('d/m/Y H:i') }}
                                </p>
                            </div>

                            <div class="flex shrink-0 gap-2">
                                <button
                                    type="button"
                                    wire:click="edit({{ $template->id }})"
                                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                                >
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    wire:click="delete({{ $template->id }})"
                                    wire:confirm="Yakin ingin menghapus template ini?"
                                    wire:loading.attr="disabled"
                                    class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 disabled:opacity-60"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-5">
                {{ $templates->links() }}
            </div>
        @endif
    </section>

</div>