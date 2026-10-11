<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}