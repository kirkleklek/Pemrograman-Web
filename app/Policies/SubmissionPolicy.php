<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    /**
     * Admin dapat melihat semua submission.
     * Dosen hanya dapat melihat submission dari tugas
     * pada mata kuliah yang diampunya.
     * Mahasiswa tidak boleh melihat daftar submission orang lain.
     */
    public function viewAny(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen'
            && (int) $assignment->course->lecturer_id === (int) $user->id;
    }

    /**
     * Admin dapat melihat semua submission.
     * Dosen hanya dapat melihat submission di mata kuliahnya.
     * Mahasiswa hanya boleh melihat submission miliknya sendiri
     * jika masih terdaftar pada mata kuliah tersebut.
     */
    public function view(User $user, Submission $submission): bool
    {
        $assignment = $submission->assignment;
        $course = $assignment->course;

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return (int) $course->lecturer_id === (int) $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return (int) $submission->user_id === (int) $user->id
                && $user->courses()
                    ->whereKey($course->getKey())
                    ->exists();
        }

        return false;
    }

    /**
     * Mahasiswa hanya dapat mengumpulkan tugas yang published
     * pada mata kuliah yang diikutinya.
     * Pemeriksaan deadline dan submission ganda tetap dilakukan
     * pada proses penyimpanan.
     */
    public function create(User $user, Assignment $assignment): bool
    {
        return $user->role === 'mahasiswa'
            && $assignment->status === 'published'
            && $user->courses()
                ->whereKey($assignment->course->getKey())
                ->exists();
    }

    /**
     * Pembaruan submission belum didukung oleh alur aplikasi saat ini.
     */
    public function update(User $user, Submission $submission): bool
    {
        return false;
    }

    /**
     * Penghapusan submission belum didukung oleh alur aplikasi saat ini.
     */
    public function delete(User $user, Submission $submission): bool
    {
        return false;
    }
}