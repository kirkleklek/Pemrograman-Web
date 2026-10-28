<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCourseListTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_only_sees_courses_they_are_enrolled_in(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $enrolledCourse = Course::factory()->create([
            'code' => 'MK-TERDAFTAR',
            'name' => 'Pemrograman Web',
            'lecturer_id' => $lecturer->id,
        ]);

        $otherCourse = Course::factory()->create([
            'code' => 'MK-RAHASIA',
            'name' => 'Mata Kuliah Mahasiswa Lain',
            'lecturer_id' => $lecturer->id,
        ]);

        $student->courses()->attach($enrolledCourse->id, [
            'enrolled_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.courses.index'))
            ->assertOk()
            ->assertSeeText('Pemrograman Web')
            ->assertSeeText('MK-TERDAFTAR')
            ->assertDontSeeText('Mata Kuliah Mahasiswa Lain')
            ->assertDontSeeText('MK-RAHASIA')
            ->assertSee(
                route('mahasiswa.courses.materials.index', $enrolledCourse),
                false
            )
            ->assertSee(
                route('mahasiswa.courses.assignments.index', $enrolledCourse),
                false
            );
    }

    public function test_non_student_cannot_access_student_course_list(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $this->actingAs($lecturer)
            ->get(route('mahasiswa.courses.index'))
            ->assertForbidden();
    }

    public function test_student_can_search_only_enrolled_courses(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $enrolledCourse = Course::factory()->create([
            'code' => 'MK-WEB',
            'name' => 'Pemrograman Web',
            'lecturer_id' => $lecturer->id,
        ]);

        $otherCourse = Course::factory()->create([
            'code' => 'MK-BASIS',
            'name' => 'Basis Data',
            'lecturer_id' => $lecturer->id,
        ]);

        $student->courses()->attach($enrolledCourse->id, [
            'enrolled_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('mahasiswa.courses.index', ['q' => 'Basis']))
            ->assertOk()
            ->assertDontSeeText('Basis Data')
            ->assertDontSeeText('MK-BASIS');
    }
}