<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LecturerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_dosen_menampilkan_ringkasan_penilaian_miliknya(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherLecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $ownCourse = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $otherCourse = Course::factory()->create([
            'lecturer_id' => $otherLecturer->id,
        ]);

        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'name' => 'Mahasiswa Kampus',
        ]);

        // Submission yang belum dinilai.
        $pendingAssignment = Assignment::factory()->create([
            'course_id' => $ownCourse->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Menunggu Penilaian',
            'status' => 'published',
        ]);

        $pendingSubmission = Submission::factory()->create([
            'assignment_id' => $pendingAssignment->id,
            'user_id' => $student->id,
        ]);

        // Submission yang sudah dinilai.
        $gradedAssignment = Assignment::factory()->create([
            'course_id' => $ownCourse->id,
            'created_by' => $lecturer->id,
            'title' => 'Tugas Sudah Dinilai',
            'status' => 'published',
        ]);

        $gradedSubmission = Submission::factory()->create([
            'assignment_id' => $gradedAssignment->id,
            'user_id' => $student->id,
        ]);

        Grade::create([
            'submission_id' => $gradedSubmission->id,
            'graded_by' => $lecturer->id,
            'score' => 85,
            'feedback' => 'Sudah baik.',
            'graded_at' => now(),
        ]);

        // Submission milik dosen lain tidak boleh terlihat.
        $otherAssignment = Assignment::factory()->create([
            'course_id' => $otherCourse->id,
            'created_by' => $otherLecturer->id,
            'title' => 'Tugas Milik Dosen Lain',
            'status' => 'published',
        ]);

        Submission::factory()->create([
            'assignment_id' => $otherAssignment->id,
            'user_id' => $student->id,
        ]);

        $response = $this->actingAs($lecturer)
            ->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertViewHas('lecturerCourseCount', 1)
            ->assertViewHas('pendingGradingCount', 1)
            ->assertViewHas('gradedSubmissionCount', 1)
            ->assertSeeText('Submission Menunggu Penilaian')
            ->assertSeeText('Tugas Menunggu Penilaian')
            ->assertSeeText('Mahasiswa Kampus')
            ->assertDontSeeText('Tugas Milik Dosen Lain');
    }
}