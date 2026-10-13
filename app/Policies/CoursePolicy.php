<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Admin dapat melihat daftar seluruh mata kuliah.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Admin, dosen pemilik, atau mahasiswa yang terdaftar
     * dapat melihat detail mata kuliah.
     */
    public function view(User $user, Course $course): bool
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
     * Pembuatan mata kuliah hanya untuk admin.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
         * Admin atau dosen pemilik dapat memperbarui mata kuliah.
         */
        /**
     * Hanya admin yang dapat memperbarui mata kuliah.
     */
    public function update(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Hanya admin yang dapat menghapus mata kuliah.
     */
    public function delete(User $user, Course $course): bool
    {
        return $user->role === 'admin';
    }

    public function manageEnrollments(User $user, Course $course): bool
    {
        return $user->role === 'admin'
            || (
                $user->role === 'dosen'
                && (int) $course->lecturer_id === (int) $user->id
            );
    }

    public function viewTeaching(User $user): bool
    {
        return $user->role === 'dosen';
    }
}