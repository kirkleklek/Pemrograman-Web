<?php

namespace App\Livewire\Dosen;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class SubmissionStatusList extends Component
{
    use WithPagination;

    public int $assignmentId;

    public string $search = '';

    public string $status = 'semua';

    /**
     * Memastikan pengguna berhak melihat daftar submission.
     */
    public function mount(Assignment $assignment): void
    {
        Gate::authorize('viewAny', [
            Submission::class,
            $assignment,
        ]);

        $this->assignmentId = (int) $assignment->id;
    }

    /**
     * Kembali ke halaman pertama ketika pencarian berubah.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Memvalidasi filter status dan mereset pagination.
     */
    public function updatedStatus(): void
    {
        if (! in_array($this->status, [
            'semua',
            'sudah',
            'belum',
        ], true)) {
            $this->status = 'semua';
        }

        $this->resetPage();
    }

    /**
     * Mengambil mahasiswa beserta submission dan nilai.
     */
    public function render()
    {
        $assignment = Assignment::query()
            ->with('course')
            ->findOrFail($this->assignmentId);

        // Memeriksa izin setiap permintaan Livewire.
        Gate::authorize('viewAny', [
            Submission::class,
            $assignment,
        ]);

        // Mengambil data mahasiswa menggunakan eager loading.
        $studentsQuery = $assignment->course
            ->students()
            ->select([
                'users.id',
                'users.name',
                'users.email',
            ])
            ->with([
                'submissions' => function ($query) use ($assignment) {
                    $query
                        ->select([
                            'id',
                            'assignment_id',
                            'user_id',
                            'original_name',
                            'file_size',
                            'submitted_at',
                            'is_late',
                        ])
                        ->where('assignment_id', $assignment->id)
                        ->with('grade:id,submission_id,score')
                        ->orderByDesc('submitted_at');
                },
            ]);

        // Pencarian berdasarkan nama atau email.
        $search = trim($this->search);

        if ($search !== '') {
            $studentsQuery->where(function ($query) use ($search) {
                $query
                    ->where('users.name', 'like', '%' . $search . '%')
                    ->orWhere('users.email', 'like', '%' . $search . '%');
            });
        }

        // Filter mahasiswa yang sudah mengumpulkan tugas.
        if ($this->status === 'sudah') {
            $studentsQuery->whereHas(
                'submissions',
                function ($query) use ($assignment) {
                    $query->where(
                        'assignment_id',
                        $assignment->id
                    );
                }
            );
        } elseif ($this->status === 'belum') {

            // Filter mahasiswa yang belum mengumpulkan tugas.
            $studentsQuery->whereDoesntHave(
                'submissions',
                function ($query) use ($assignment) {
                    $query->where(
                        'assignment_id',
                        $assignment->id
                    );
                }
            );
        }

        // Pagination untuk membatasi data yang ditampilkan.
        $students = $studentsQuery
            ->orderBy('users.name')
            ->paginate(10);

        return view(
            'livewire.dosen.submission-status-list',
            compact('assignment', 'students')
        );
    }
}