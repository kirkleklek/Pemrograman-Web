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
        if ($course->relationLoaded('students')) {
            return $course->students->contains('id', $user->getKey());
        }

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

        $assignment->loadMissing('course');

        $course = $assignment->course;

        if (! $course) {
            return false;
        }

        if ($user->role === 'dosen') {
            return (int) $course->lecturer_id === (int) $user->id;
        }

        if ($user->role === 'mahasiswa') {
            $isEnrolled = $course->relationLoaded('students')
                ? $course->students->contains('id', $user->getKey())
                : $user->courses()
                    ->whereKey($course->getKey())
                    ->exists();

            return $assignment->status === 'published'
                && $isEnrolled;
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
        if ($user->role === 'admin') {
            return true;
        }

        $assignment->loadMissing('course');

        return $user->role === 'dosen'
            && $assignment->course !== null
            && (int) $assignment->course->lecturer_id === (int) $user->id;
    }

    /**
     * Admin atau dosen pemilik dapat menghapus tugas.
     */
    public function delete(User $user, Assignment $assignment): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        $assignment->loadMissing('course');

        return $user->role === 'dosen'
            && $assignment->course !== null
            && (int) $assignment->course->lecturer_id === (int) $user->id;
    }
}