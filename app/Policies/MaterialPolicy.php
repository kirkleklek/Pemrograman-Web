<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    /**
     * Dosen pemilik atau mahasiswa yang terdaftar
     * dapat melihat daftar materi suatu mata kuliah.
     */
    public function viewAny(User $user, Course $course): bool
    {
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
     * Aturan melihat materi mengikuti akses terhadap mata kuliahnya.
     */
    public function view(User $user, Material $material): bool
    {
        return $this->viewAny($user, $material->course);
    }

    /**
     * Hanya dosen pemilik mata kuliah yang dapat membuat materi.
     */
    public function create(User $user, Course $course): bool
    {
        return $user->role === 'dosen'
            && (int) $course->lecturer_id === (int) $user->id;
    }

    /**
     * Hanya dosen pemilik mata kuliah yang dapat memperbarui materi.
     */
    public function update(User $user, Material $material): bool
    {
        return $user->role === 'dosen'
            && (int) $material->course->lecturer_id === (int) $user->id;
    }

    /**
     * Hanya dosen pemilik mata kuliah yang dapat menghapus materi.
     */
    public function delete(User $user, Material $material): bool
    {
        return $user->role === 'dosen'
            && (int) $material->course->lecturer_id === (int) $user->id;
    }
}