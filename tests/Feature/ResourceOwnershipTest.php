<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_cannot_view_material_owned_by_another_lecturer(): void
    {
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
            'title' => 'Materi Milik Dosen Lain',
            'description' => 'Materi untuk pengujian kepemilikan.',
            'type' => 'link',
            'external_url' => 'https://example.com/materi',
        ]);

        $this->actingAs($otherLecturer)
            ->get(route('dosen.materials.show', $material))
            ->assertForbidden();
    }

    public function test_dosen_cannot_view_assignment_owned_by_another_lecturer(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherLecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($otherLecturer)
            ->get(route('dosen.assignments.show', $assignment))
            ->assertForbidden();
    }

    public function test_student_cannot_view_another_students_submission(): void
    {
        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $submissionOwner = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $otherStudent = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        // Kedua mahasiswa terdaftar agar tes menguji kepemilikan
        // submission, bukan sekadar status pendaftaran.
        $submissionOwner->courses()->attach($course->id, [
            'enrolled_at' => now(),
        ]);

        $otherStudent->courses()->attach($course->id, [
            'enrolled_at' => now(),
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $submissionOwner->id,
        ]);

        $this->actingAs($otherStudent)
            ->get(route('mahasiswa.submissions.show', $submission))
            ->assertForbidden();
    }
}