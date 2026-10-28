<?php

namespace App\Livewire\Dosen;

use App\Models\FeedbackTemplate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class FeedbackTemplateManager extends Component
{
    use WithPagination;

    public string $title = '';

    public string $content = '';

    #[Locked]
    public ?int $editingId = null;

    /**
     * Pastikan hanya dosen yang dapat menggunakan komponen.
     */
    public function mount(): void
    {
        $this->authorizeLecturer();
    }

    /**
     * Simpan template baru atau perbarui template yang dipilih.
     */
    public function save(): void
    {
        $this->authorizeLecturer();

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:100'],
            'content' => ['required', 'string', 'max:5000'],
        ], [
            'title.required' => 'Judul template wajib diisi.',
            'title.max' => 'Judul maksimal 100 karakter.',
            'content.required' => 'Isi umpan balik wajib diisi.',
            'content.max' => 'Isi umpan balik maksimal 5.000 karakter.',
        ]);

        if ($this->editingId !== null) {
            $template = FeedbackTemplate::query()
                ->where('lecturer_id', Auth::id())
                ->findOrFail($this->editingId);

            $template->update($validated);

            session()->flash(
                'success',
                'Template umpan balik berhasil diperbarui.'
            );
        } else {
            FeedbackTemplate::create([
                ...$validated,
                'lecturer_id' => Auth::id(),
            ]);

            session()->flash(
                'success',
                'Template umpan balik berhasil ditambahkan.'
            );
        }

        $this->resetForm();
        $this->resetPage();
    }

    /**
     * Muat template milik dosen ke dalam form edit.
     */
    public function edit(int $id): void
    {
        $this->authorizeLecturer();

        $template = FeedbackTemplate::query()
            ->where('lecturer_id', Auth::id())
            ->findOrFail($id);

        $this->editingId = $template->id;
        $this->title = $template->title;
        $this->content = $template->content;

        $this->resetValidation();
    }

    /**
     * Hapus template milik dosen yang sedang login.
     */
    public function delete(int $id): void
    {
        $this->authorizeLecturer();

        $template = FeedbackTemplate::query()
            ->where('lecturer_id', Auth::id())
            ->findOrFail($id);

        $template->delete();

        if ($this->editingId === $template->id) {
            $this->resetForm();
        }

        $this->resetPage();

        session()->flash(
            'success',
            'Template umpan balik berhasil dihapus.'
        );
    }

    /**
     * Batalkan mode edit dan kosongkan form.
     */
    public function cancelEdit(): void
    {
        $this->authorizeLecturer();

        $this->resetForm();
    }

    /**
     * Kosongkan input dan status edit.
     */
    private function resetForm(): void
    {
        $this->reset(['title', 'content', 'editingId']);
        $this->resetValidation();
    }

    /**
     * Periksa role pada setiap aksi yang mengubah data.
     */
    private function authorizeLecturer(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->role === 'dosen',
            403
        );
    }

    public function render()
    {
        $this->authorizeLecturer();

        $templates = FeedbackTemplate::query()
            ->where('lecturer_id', Auth::id())
            ->orderByDesc('updated_at')
            ->paginate(10);

        return view(
            'livewire.dosen.feedback-template-manager',
            compact('templates')
        );
    }
}
