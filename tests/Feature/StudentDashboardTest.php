<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_mahasiswa_menampilkan_ringkasan_tugas_dan_status_pengumpulan(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $course->students()->attach($student->id, [
            'enrolled_at' => now(),
        ]);

        $submittedAssignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas yang sudah dikumpulkan',
            'status' => 'published',
        ]);

        Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas yang belum dikumpulkan',
            'status' => 'published',
        ]);

        Submission::factory()->create([
            'assignment_id' => $submittedAssignment->id,
            'user_id' => $student->id,
        ]);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Total Tugas')
            ->assertSeeText('Sudah Dikumpulkan')
            ->assertSeeText('Belum Dikumpulkan')
            ->assertSeeText('Tugas yang sudah dikumpulkan')
            ->assertSeeText('Tugas yang belum dikumpulkan')
            ->assertSeeText('1');
    }

    public function test_dashboard_mahasiswa_tidak_menampilkan_tugas_mata_kuliah_lain(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $otherCourse = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        Assignment::factory()->create([
            'course_id' => $otherCourse->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas milik mahasiswa lain',
            'status' => 'published',
        ]);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText('Tugas milik mahasiswa lain')
            ->assertSeeText('Total Tugas');
    }
}