<?php

namespace App\Policies;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\User;

class AssignmentPolicy
{
    /**
     * Admin, dosen pemilik, atau mahasiswa terdaftar
     * boleh melihat daftar tugas suatu mata kuliah.
     */
    public function viewAny(User $user, Course $course): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'dosen') {
            return (int) $course->lecturer_id === (int) $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return $user->courses()
                ->whereKey($course->getKey())
                ->exists();
        }

        return false;
    }

    /**
     * Admin dan dosen pemilik dapat melihat tugas.
     * Mahasiswa hanya boleh melihat tugas published
     * pada mata kuliah yang diikutinya.
     */
    public function view(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $course = $assignment->course;

        if ($user->role === 'dosen') {
            return (int) $course->lecturer_id === (int) $user->id;
        }

        if ($user->role === 'mahasiswa') {
            return $assignment->status === 'published'
                && $user->courses()
                    ->whereKey($course->getKey())
                    ->exists();
        }

        return false;
    }

    /**
     * Admin atau dosen pemilik dapat membuat tugas.
     */
    public function create(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || (
                $user->role === 'dosen'
                && (int) $course->lecturer_id === (int) $user->id
            );
    }

    /**
     * Admin atau dosen pemilik dapat memperbarui tugas.
     */
    public function update(User $user, Assignment $assignment): bool
    {
        return $user->role === 'admin'
            || (
                $user->role === 'dosen'
                && (int) $assignment->course->lecturer_id === (int) $user->id
            );
    }

    /**
     * Admin atau dosen pemilik dapat menghapus tugas.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        return $user->role === 'admin'
            || (
                $user->role === 'dosen'
                && (int) $assignment->course->lecturer_id === (int) $user->id
            );
    }
}