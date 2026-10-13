<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_course_owner_have_course_permissions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherLecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        // Admin boleh melihat daftar dan membuat mata kuliah.
        $this->assertTrue(
            Gate::forUser($admin)->allows('viewAny', Course::class)
        );

        $this->assertTrue(
            Gate::forUser($admin)->allows('create', Course::class)
        );

        // Admin boleh melihat, mengubah, dan menghapus mata kuliah.
        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $course)
        );

        $this->assertTrue(
            Gate::forUser($admin)->allows('update', $course)
        );

        $this->assertTrue(
            Gate::forUser($admin)->allows('delete', $course)
        );

        // Dosen pemilik hanya boleh melihat mata kuliahnya.
        $this->assertTrue(
            Gate::forUser($owner)->allows('view', $course)
        );

        $this->assertFalse(
            Gate::forUser($owner)->allows('update', $course)
        );

        $this->assertFalse(
            Gate::forUser($owner)->allows('delete', $course)
        );

        // Dosen lain tidak boleh melihat, mengubah, atau menghapus.
        $this->assertFalse(
            Gate::forUser($otherLecturer)->allows('view', $course)
        );

        $this->assertFalse(
            Gate::forUser($otherLecturer)->allows('update', $course)
        );

        $this->assertFalse(
            Gate::forUser($otherLecturer)->allows('delete', $course)
        );
    }

    public function test_only_enrolled_students_can_view_course(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $enrolledStudent = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $otherStudent = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        $enrolledStudent->courses()->attach($course->id, [
            'enrolled_at' => now(),
        ]);

        $this->assertTrue(
            Gate::forUser($enrolledStudent)->allows('view', $course)
        );

        $this->assertFalse(
            Gate::forUser($otherStudent)->allows('view', $course)
        );
    }

    public function test_admin_and_course_owner_can_manage_materials(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherLecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $owner->id,
            'title' => 'Materi Pengujian',
            'description' => 'Materi untuk pengujian policy.',
            'type' => 'link',
            'external_url' => 'https://example.com/materi',
        ]);

        // Admin boleh melihat dan mengelola materi.
        $this->assertTrue(
            Gate::forUser($admin)->allows(
                'viewAny',
                [Material::class, $course]
            )
        );

        $this->assertTrue(
            Gate::forUser($admin)->allows(
                'create',
                [Material::class, $course]
            )
        );

        $this->assertTrue(
            Gate::forUser($admin)->allows('view', $material)
        );

        $this->assertTrue(
            Gate::forUser($admin)->allows('update', $material)
        );

        $this->assertTrue(
            Gate::forUser($admin)->allows('delete', $material)
        );

        // Dosen pemilik boleh mengelola materi mata kuliahnya.
        $this->assertTrue(
            Gate::forUser($owner)->allows(
                'viewAny',
                [Material::class, $course]
            )
        );

        $this->assertTrue(
            Gate::forUser($owner)->allows(
                'create',
                [Material::class, $course]
            )
        );

        $this->assertTrue(
            Gate::forUser($owner)->allows('view', $material)
        );

        $this->assertTrue(
            Gate::forUser($owner)->allows('update', $material)
        );

        $this->assertTrue(
            Gate::forUser($owner)->allows('delete', $material)
        );

        // Dosen lain tidak boleh mengakses atau mengelola materi.
        $this->assertFalse(
            Gate::forUser($otherLecturer)->allows(
                'viewAny',
                [Material::class, $course]
            )
        );

        $this->assertFalse(
            Gate::forUser($otherLecturer)->allows(
                'create',
                [Material::class, $course]
            )
        );

        $this->assertFalse(
            Gate::forUser($otherLecturer)->allows('view', $material)
        );

        $this->assertFalse(
            Gate::forUser($otherLecturer)->allows('update', $material)
        );

        $this->assertFalse(
            Gate::forUser($otherLecturer)->allows('delete', $material)
        );
    }

    public function test_enrolled_students_can_view_but_not_manage_materials(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        $student->courses()->attach($course->id, [
            'enrolled_at' => now(),
        ]);

        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $owner->id,
            'title' => 'Materi Mahasiswa',
            'description' => null,
            'type' => 'link',
            'external_url' => 'https://example.com/materi',
        ]);

        $this->assertTrue(
            Gate::forUser($student)->allows(
                'viewAny',
                [Material::class, $course]
            )
        );

        $this->assertTrue(
            Gate::forUser($student)->allows('view', $material)
        );

        $this->assertFalse(
            Gate::forUser($student)->allows(
                'create',
                [Material::class, $course]
            )
        );

        $this->assertFalse(
            Gate::forUser($student)->allows('update', $material)
        );

        $this->assertFalse(
            Gate::forUser($student)->allows('delete', $material)
        );
    }

    public function test_unenrolled_students_cannot_view_materials(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        $material = Material::create([
            'course_id' => $course->id,
            'uploaded_by' => $owner->id,
            'title' => 'Materi Terbatas',
            'description' => null,
            'type' => 'link',
            'external_url' => 'https://example.com/materi',
        ]);

        $this->assertFalse(
            Gate::forUser($student)->allows(
                'viewAny',
                [Material::class, $course]
            )
        );

        $this->assertFalse(
            Gate::forUser($student)->allows('view', $material)
        );
    }
}