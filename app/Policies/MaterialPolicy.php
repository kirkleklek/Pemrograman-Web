<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    /**
     * Admin dapat melihat seluruh materi.
     * Dosen hanya dapat melihat materi di mata kuliah yang diampu.
     * Mahasiswa hanya dapat melihat materi mata kuliah yang diikuti.
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
     * Hak melihat materi mengikuti akses mata kuliahnya.
     */
    public function view(User $user, Material $material): bool
    {
        return $this->viewAny($user, $material->course);
    }

    /**
     * Admin dan dosen pengampu dapat membuat materi.
     */
    public function create(User $user, Course $course): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen'
            && (int) $course->lecturer_id === (int) $user->id;
    }

    /**
     * Admin dan dosen pengampu dapat memperbarui materi.
     */
    public function update(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen'
            && (int) $material->course->lecturer_id === (int) $user->id;
    }

    /**
     * Admin dan dosen pengampu dapat menghapus materi.
     */
    public function delete(User $user, Material $material): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->role === 'dosen'
            && (int) $material->course->lecturer_id === (int) $user->id;
    }
}