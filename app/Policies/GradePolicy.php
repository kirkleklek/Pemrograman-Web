<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;

class GradePolicy
{
    /**
     * Admin dapat melihat rekap nilai.
     * Dosen hanya dapat melihat rekap nilai
     * pada mata kuliah yang diampunya.
     */
    public function viewAny(User $user, Course $course): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen'
            && (int) $course->lecturer_id === (int) $user->id;
    }

    /**
     * Admin dapat melihat nilai.
     * Dosen dapat melihat nilai pada mata kuliahnya.
     * Mahasiswa hanya dapat melihat nilai miliknya sendiri.
     */
    public function view(User $user, Grade $grade): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $submission = $grade->submission;
        $course = $submission->assignment->course;

        if ($user->role === 'dosen') {
            return (int) $course->lecturer_id === (int) $user->id;
        }

        return $user->role === 'mahasiswa'
            && (int) $submission->user_id === (int) $user->id
            && $user->courses()
                ->whereKey($course->getKey())
                ->exists();
    }

    /**
     * Hanya dosen pengampu yang dapat memberikan nilai.
     */
    public function create(User $user, Submission $submission): bool
    {
        return $user->role === 'dosen'
            && (int) $submission->assignment->course->lecturer_id
                === (int) $user->id;
    }

    /**
     * Hanya dosen pengampu yang dapat memperbarui nilai.
     */
    public function update(User $user, Grade $grade): bool
    {
        return $user->role === 'dosen'
            && (int) $grade->submission->assignment->course->lecturer_id
                === (int) $user->id;
    }

    /**
     * Penghapusan nilai belum menjadi bagian alur aplikasi.
     */
    public function delete(User $user, Grade $grade): bool
    {
        return false;
    }
}