<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AssignmentSubmissionService
{
    public function submit(
        Assignment $assignment,
        User $student,
        UploadedFile $file,
        ?string $note = null
    ): Submission {
        $submittedAt = now();

        $isLate = $submittedAt->greaterThan($assignment->due_at);

        if ($isLate && ! $assignment->allow_late) {
            throw ValidationException::withMessages([
                'file' => 'Batas pengumpulan sudah lewat dan dosen tidak mengizinkan keterlambatan.',
            ]);
        }

        // Penyimpanan tetap privat pada disk local.
        $disk = 'local';

        $existingSubmission = $assignment->submissions()
            ->where('user_id', $student->id)
            ->first();

        // UploadedFile::store menghasilkan nama fisik baru.
        $newFilePath = $file->store('submissions', $disk);

        if (! $newFilePath) {
            throw new \RuntimeException(
                'Berkas tugas gagal disimpan.'
            );
        }

        try {
            $submission = $assignment->submissions()->updateOrCreate(
                [
                    'user_id' => $student->id,
                ],
                [
                    'file_path' => $newFilePath,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'note' => $note,
                    'submitted_at' => $submittedAt,
                    'is_late' => $isLate,
                ]
            );
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($newFilePath);

            throw $exception;
        }

        // File lama dihapus setelah record berhasil disimpan.
        if (
            $existingSubmission &&
            $existingSubmission->file_path !== $newFilePath
        ) {
            Storage::disk($disk)->delete(
                $existingSubmission->file_path
            );
        }

        return $submission;
    }
}