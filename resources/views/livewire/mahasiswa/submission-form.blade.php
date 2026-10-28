<div>
    @if (session()->has('success'))
        <p role="status">
            {{ session('success') }}
        </p>
    @endif

    @if ($existingSubmission)
        <section>
            <h3>Submission Sebelumnya</h3>

            <p>
                Nama berkas:
                {{ $existingSubmission->original_name }}
            </p>

            @can('download', $existingSubmission)
                <a href="{{ route('submissions.download', $existingSubmission) }}">
                    Unduh berkas
                </a>
            @endcan

            <p>
                Waktu pengumpulan:
                {{ $existingSubmission->submitted_at?->format('d-m-Y H:i') }}
            </p>

            <p>
                Status:
                {{ $existingSubmission->is_late ? 'Terlambat' : 'Tepat waktu' }}
            </p>

            <p>
                Untuk mengumpulkan ulang, pilih berkas pengganti di bawah.
            </p>
        </section>
    @else
        <h3>Pengumpulan Tugas</h3>

        <p>Silakan pilih berkas tugas yang akan dikumpulkan.</p>
    @endif

    <form wire:submit="submit">
        <div>
            <label for="submission-file">
                Berkas Tugas (PDF, DOC, atau DOCX; maksimal 5 MB)
            </label>

            <input
                id="submission-file"
                type="file"
                wire:model="file"
                accept=".pdf,.doc,.docx"
                required
            >

            @error('file')
                <p role="alert">{{ $message }}</p>
            @enderror

            <div wire:loading wire:target="file">
                Sedang mengunggah berkas...
            </div>
        </div>

        <div>
            <label for="submission-note">Catatan (opsional)</label>

            <textarea
                id="submission-note"
                wire:model="note"
                rows="3"
            ></textarea>

            @error('note')
                <p role="alert">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="submit"
        >
            {{ $existingSubmission ? 'Kumpulkan Ulang' : 'Kumpulkan Tugas' }}
        </button>

        <div wire:loading wire:target="submit">
            Sedang memproses pengumpulan tugas...
        </div>
    </form>
</div>
