<?php

namespace Tests\Feature;

use App\Events\GradeGiven;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DashboardCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    /**
     * Membuat data dasar untuk pengujian cache.
     */
    private function createDashboardFixture(): array
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

        $course->students()->attach($student->id, [
            'enrolled_at' => now(),
        ]);

        $assignment = Assignment::factory()->create([
            'course_id' => $course->id,
            'created_by' => $lecturer->id,
            'status' => 'published',
        ]);

        return [$lecturer, $student, $course, $assignment];
    }

    /**
     * Mengisi cache dashboard mahasiswa dan dosen.
     */
    private function primeDashboardCache(
        User $student,
        User $lecturer
    ): void {
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

        $this->assertDashboardCacheExists($student, $lecturer);
    }

    /**
     * Memastikan kedua cache dashboard sudah tersedia.
     */
    private function assertDashboardCacheExists(
        User $student,
        User $lecturer
    ): void {
        $this->assertTrue(
            Cache::has("dashboard:student:{$student->id}")
        );

        $this->assertTrue(
            Cache::has("dashboard:lecturer:{$lecturer->id}")
        );
    }

    /**
     * Memastikan kedua cache dashboard sudah terhapus.
     */
    private function assertDashboardCacheCleared(
        User $student,
        User $lecturer
    ): void {
        $this->assertFalse(
            Cache::has("dashboard:student:{$student->id}")
        );

        $this->assertFalse(
            Cache::has("dashboard:lecturer:{$lecturer->id}")
        );
    }

    public function test_submission_changes_invalidate_student_and_lecturer_dashboard_cache(): void
    {
        [$lecturer, $student, , $assignment] = $this->createDashboardFixture();

        $this->primeDashboardCache($student, $lecturer);

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        // Cache harus terhapus setelah submission dibuat.
        $this->assertDashboardCacheCleared($student, $lecturer);

        // Pastikan perubahan submission juga menghapus cache.
        $this->primeDashboardCache($student, $lecturer);

        $submission->update([
            'note' => 'Pengumpulan ulang tugas.',
        ]);

        $this->assertDashboardCacheCleared($student, $lecturer);
    }

    public function test_grade_changes_invalidate_student_and_lecturer_dashboard_cache(): void
    {
        Event::fake([GradeGiven::class]);

        [$lecturer, $student, , $assignment] = $this->createDashboardFixture();

        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $this->primeDashboardCache($student, $lecturer);

        $grade = Grade::create([
            'submission_id' => $submission->id,
            'graded_by' => $lecturer->id,
            'score' => 75,
            'feedback' => 'Cukup baik.',
            'graded_at' => now(),
        ]);

        // Cache harus terhapus setelah nilai dibuat.
        $this->assertDashboardCacheCleared($student, $lecturer);

        // Pastikan perubahan nilai juga menghapus cache.
        $this->primeDashboardCache($student, $lecturer);

        $grade->update([
            'score' => 90,
            'feedback' => 'Hasil penilaian diperbarui.',
        ]);

        $this->assertDashboardCacheCleared($student, $lecturer);
    }

    public function test_assignment_update_invalidates_student_and_lecturer_dashboard_cache(): void
    {
        [$lecturer, $student, , $assignment] = $this->createDashboardFixture();

        $this->primeDashboardCache($student, $lecturer);

        $assignment->update([
            'title' => 'Tugas yang telah diperbarui',
        ]);

        $this->assertDashboardCacheCleared($student, $lecturer);
    }

    public function test_course_update_invalidates_student_and_lecturer_dashboard_cache(): void
    {
        [$lecturer, $student, $course] = $this->createDashboardFixture();

        $this->primeDashboardCache($student, $lecturer);

        $course->update([
            'name' => 'Mata Kuliah yang Diperbarui',
        ]);

        $this->assertDashboardCacheCleared($student, $lecturer);
    }
}
