<?php

namespace Tests\Feature;

use App\Events\GradeGiven;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GradeReadApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([GradeGiven::class]);
    }

    private function makeGrade(
        Course $course,
        User $student,
        int $score = 80
    ): Grade {
        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $course->lecturer_id,
        ]);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        return Grade::create([
            'submission_id' => $submission->id,
            'graded_by' => $course->lecturer_id,
            'score' => $score,
            'feedback' => 'Feedback pengujian.',
            'graded_at' => now(),
        ]);
    }

    public function test_dosen_hanya_mendapat_rekap_nilai_mata_kuliah_miliknya(): void
    {
        $dosen = User::factory()->create(['role' => 'dosen']);
        $dosenLain = User::factory()->create(['role' => 'dosen']);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $otherCourse = Course::factory()->create([
            'lecturer_id' => $dosenLain->id,
        ]);

        $student = User::factory()->create(['role' => 'mahasiswa']);

        $grade = $this->makeGrade($course, $student, 85);

        $foreignGrade = $this->makeGrade(
            $otherCourse,
            $student,
            95
        );

        Sanctum::actingAs($dosen);

        $response = $this->getJson(
            "/api/v1/courses/{$course->id}/grades"
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $grade->id);

        $this->assertNotSame(
            $foreignGrade->id,
            $response->json('data.0.id')
        );
    }

    public function test_dosen_lain_dan_mahasiswa_tidak_dapat_melihat_rekap_nilai(): void
    {
        $owner = User::factory()->create(['role' => 'dosen']);
        $otherLecturer = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        $this->makeGrade($course, $student);

        foreach ([$otherLecturer, $student] as $user) {
            Sanctum::actingAs($user);

            $this->getJson("/api/v1/courses/{$course->id}/grades")
                ->assertForbidden();
        }
    }

    public function test_admin_dapat_melihat_rekap_nilai_sesuai_grade_policy(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $grade = $this->makeGrade($course, $student);

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/courses/{$course->id}/grades")
            ->assertOk()
            ->assertJsonPath('data.0.id', $grade->id);
    }

    public function test_mahasiswa_hanya_mendapat_nilai_miliknya_dari_mata_kuliah_yang_diikuti(): void
    {
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $peer = User::factory()->create(['role' => 'mahasiswa']);

        $courseA = Course::factory()->create();
        $courseB = Course::factory()->create();

        $courseA->students()->attach($student->id, [
            'enrolled_at' => now(),
        ]);

        $courseA->students()->attach($peer->id, [
            'enrolled_at' => now(),
        ]);

        $ownGrade = $this->makeGrade($courseA, $student, 90);

        $peerGrade = $this->makeGrade($courseA, $peer, 75);

        // Mahasiswa memiliki submission di mata kuliah,
        // tetapi sudah tidak terdaftar pada mata kuliah tersebut.
        $unenrolledGrade = $this->makeGrade($courseB, $student, 60);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/v1/my/grades');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownGrade->id);

        $returnedIds = collect($response->json('data'))
            ->pluck('id')
            ->all();

        $this->assertContains($ownGrade->id, $returnedIds);
        $this->assertNotContains($peerGrade->id, $returnedIds);
        $this->assertNotContains($unenrolledGrade->id, $returnedIds);
    }

    public function test_hanya_mahasiswa_yang_dapat_mengakses_my_grades(): void
    {
        $this->getJson('/api/v1/my/grades')
            ->assertUnauthorized();

        $lecturer = User::factory()->create(['role' => 'dosen']);
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([$lecturer, $admin] as $user) {
            Sanctum::actingAs($user);

            $this->getJson('/api/v1/my/grades')
                ->assertForbidden();
        }
    }
}