<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EnrollmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_and_remove_a_student(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.courses.enrollments.index', $course))
            ->assertOk();

        $this->post(
            route('admin.courses.enrollments.store', $course),
            [
                'student_id' => $student->id,
            ]
        )->assertRedirect();

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);

        $this->delete(
            route('admin.courses.enrollments.destroy', [
                'course' => $course,
                'student' => $student,
            ])
        )->assertRedirect();

        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_course_owner_can_register_a_student(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $this->actingAs($lecturer)
            ->get(route('dosen.courses.enrollments.index', $course))
            ->assertOk();

        $this->post(
            route('dosen.courses.enrollments.store', $course),
            [
                'student_id' => $student->id,
            ]
        )->assertRedirect();

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_other_lecturer_cannot_manage_enrollments(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherLecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        $this->actingAs($otherLecturer)
            ->get(route('dosen.courses.enrollments.index', $course))
            ->assertForbidden();

        $this->post(
            route('dosen.courses.enrollments.store', $course),
            [
                'student_id' => $student->id,
            ]
        )->assertForbidden();

        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_student_cannot_manage_enrollments(): void
    {
        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $this->actingAs($student)
            ->get(route('admin.courses.enrollments.index', $course))
            ->assertForbidden();

        $this->post(
            route('dosen.courses.enrollments.store', $course),
            [
                'student_id' => $student->id,
            ]
        )->assertForbidden();

        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_duplicate_enrollment_is_rejected(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $course->students()->attach($student->id, [
            'enrolled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(
                route('admin.courses.enrollments.store', $course),
                [
                    'student_id' => $student->id,
                ]
            )
            ->assertSessionHasErrors('student_id');

        $this->assertSame(
            1,
            $course->students()
                ->whereKey($student->id)
                ->count()
        );
    }

    public function test_non_student_cannot_be_enrolled(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $this->actingAs($admin)
            ->post(
                route('admin.courses.enrollments.store', $course),
                [
                    'student_id' => $lecturer->id,
                ]
            )
            ->assertSessionHasErrors('student_id');

        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $lecturer->id,
        ]);
    }

    public function test_enrollment_lists_paginate_registered_and_available_students_separately(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        // Buat 2 mahasiswa terdaftar dan 16 belum terdaftar.
        for ($index = 1; $index <= 18; $index++) {
            $student = User::factory()->create([
                'role' => 'mahasiswa',
            ]);

            if ($index <= 2) {
                $course->students()->attach($student->id, [
                    'enrolled_at' => now(),
                ]);
            }
        }

        // Periksa halaman pertama.
        $this->actingAs($admin)
            ->get(route('admin.courses.enrollments.index', $course))
            ->assertOk()
            ->assertViewHas('students', function ($students) {
                return $students->total() === 2
                    && $students->getPageName() === 'students_page';
            })
            ->assertViewHas('availableStudents', function ($availableStudents) {
                return $availableStudents->total() === 16
                    && $availableStudents->count() === 15
                    && $availableStudents->getPageName() === 'available_page';
            });

        // Periksa halaman kedua mahasiswa yang tersedia.
        $this->actingAs($admin)
            ->get(route('admin.courses.enrollments.index', $course).'?available_page=2')
            ->assertOk()
            ->assertViewHas('availableStudents', function ($availableStudents) {
                return $availableStudents->currentPage() === 2
                    && $availableStudents->count() === 1;
            })
            ->assertViewHas('students', function ($students) {
                return $students->currentPage() === 1
                    && $students->total() === 2;
            });
    }

    /**
     * Memastikan cache dashboard terhapus ketika mahasiswa didaftarkan.
     */
    public function test_enrollment_invalidates_student_and_lecturer_dashboard_cache(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        // Mengisi cache dashboard mahasiswa dan dosen.
        Cache::put(
            "dashboard:student:{$student->id}",
            ['cached' => true],
            now()->addMinutes(5)
        );

        Cache::put(
            "dashboard:lecturer:{$lecturer->id}",
            ['cached' => true],
            now()->addMinutes(5)
        );

        $this->assertTrue(Cache::has("dashboard:student:{$student->id}"));
        $this->assertTrue(Cache::has("dashboard:lecturer:{$lecturer->id}"));

        // Mendaftarkan mahasiswa.
        $this->actingAs($admin)
            ->post(
                route('admin.courses.enrollments.store', $course),
                [
                    'student_id' => $student->id,
                ]
            )
            ->assertRedirect();

        // Memastikan cache mahasiswa dan dosen telah terhapus.
        $this->assertFalse(Cache::has("dashboard:student:{$student->id}"));
        $this->assertFalse(Cache::has("dashboard:lecturer:{$lecturer->id}"));
    }

    /**
     * Memastikan cache dashboard terhapus ketika mahasiswa dikeluarkan.
     */
    public function test_unenrollment_invalidates_student_and_lecturer_dashboard_cache(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $otherStudent = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        // Mendaftarkan dua mahasiswa terlebih dahulu.
        $course->students()->attach($student->id, [
            'enrolled_at' => now(),
        ]);

        $course->students()->attach($otherStudent->id, [
            'enrolled_at' => now(),
        ]);

        // Mengisi cache dashboard mahasiswa dan dosen.
        Cache::put(
            "dashboard:student:{$student->id}",
            ['cached' => true],
            now()->addMinutes(5)
        );

        Cache::put(
            "dashboard:student:{$otherStudent->id}",
            ['cached' => true],
            now()->addMinutes(5)
        );

        Cache::put(
            "dashboard:lecturer:{$lecturer->id}",
            ['cached' => true],
            now()->addMinutes(5)
        );

        // Memastikan cache awal tersedia.
        $this->assertTrue(Cache::has("dashboard:student:{$student->id}"));
        $this->assertTrue(Cache::has("dashboard:student:{$otherStudent->id}"));
        $this->assertTrue(Cache::has("dashboard:lecturer:{$lecturer->id}"));

        // Mengeluarkan mahasiswa pertama.
        $this->actingAs($admin)
            ->delete(
                route('admin.courses.enrollments.destroy', [
                    'course' => $course,
                    'student' => $student,
                ])
            )
            ->assertRedirect();

        // Cache mahasiswa yang dikeluarkan harus terhapus.
        $this->assertFalse(Cache::has("dashboard:student:{$student->id}"));

        // Cache dosen dan mahasiswa lain juga harus diperbarui.
        $this->assertFalse(Cache::has("dashboard:lecturer:{$lecturer->id}"));
        $this->assertFalse(Cache::has("dashboard:student:{$otherStudent->id}"));
    }
}
