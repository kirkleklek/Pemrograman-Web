<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CourseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_former_lecturer_requires_enrollment_to_read_course_detail_after_becoming_student(): void
    {
        $owner = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $owner->id]);
        $owner->forceFill(['role' => 'mahasiswa'])->save();
        Sanctum::actingAs($owner);

        $this->getJson("/api/v1/courses/{$course->id}")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);

        $owner->courses()->attach($course, ['enrolled_at' => now()]);
        $this->getJson("/api/v1/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $course->id);
    }

    public function test_enrolled_student_and_admin_can_read_course_detail(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $student->courses()->attach($course, ['enrolled_at' => now()]);

        foreach ([$student, User::factory()->create(['role' => 'admin'])] as $user) {
            Sanctum::actingAs($user);
            $this->getJson("/api/v1/courses/{$course->id}")
                ->assertOk()
                ->assertJsonPath('data.id', $course->id);
        }
    }

    public function test_admin_can_view_all_courses_with_pagination(): void
    {
        User::factory()->create([
            'role' => 'admin',
        ]);

        Course::factory()->count(16)->create();

        Sanctum::actingAs(
            User::where('role', 'admin')->firstOrFail()
        );

        $response = $this->getJson('/api/v1/courses');

        $response
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 16);

        $this->getJson('/api/v1/courses?page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_dosen_only_sees_courses_they_teach(): void
    {
        $dosenA = User::factory()->create([
            'role' => 'dosen',
        ]);

        $dosenB = User::factory()->create([
            'role' => 'dosen',
        ]);

        $courseA = Course::factory()->create([
            'lecturer_id' => $dosenA->id,
        ]);

        Course::factory()->create([
            'lecturer_id' => $dosenB->id,
        ]);

        Sanctum::actingAs($dosenA);

        $this->getJson('/api/v1/courses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $courseA->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_mahasiswa_only_sees_courses_they_follow(): void
    {
        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $courseA = Course::factory()->create();
        Course::factory()->create();

        $mahasiswa->courses()->attach($courseA->id, [
            'enrolled_at' => now(),
        ]);

        Sanctum::actingAs($mahasiswa);

        $this->getJson('/api/v1/courses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $courseA->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_dosen_cannot_view_course_owned_by_another_dosen(): void
    {
        $owner = User::factory()->create([
            'role' => 'dosen',
        ]);

        $otherDosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherDosen);

        $this->getJson("/api/v1/courses/{$course->id}")
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_mahasiswa_cannot_view_course_they_do_not_follow(): void
    {
        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create();

        Sanctum::actingAs($mahasiswa);

        $this->getJson("/api/v1/courses/{$course->id}")
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_authorized_user_can_view_course_detail_and_counts(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        Sanctum::actingAs($dosen);

        $this->getJson("/api/v1/courses/{$course->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $course->id)
            ->assertJsonPath('data.lecturer.id', $dosen->id)
            ->assertJsonPath('data.materials_count', 0)
            ->assertJsonPath('data.assignments_count', 0);
    }
}
