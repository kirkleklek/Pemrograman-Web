<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MaterialApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_former_lecturer_requires_enrollment_after_becoming_student(): void
    {
        $owner = User::factory()->create(['role' => 'dosen']);
        $course = Course::factory()->create(['lecturer_id' => $owner->id]);
        $material = $this->createMaterial($course, $owner, 'Materi Pengujian Role');
        $owner->forceFill(['role' => 'mahasiswa'])->save();
        Sanctum::actingAs($owner);

        $this->getJson("/api/v1/courses/{$course->id}/materials")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke sumber daya ini.']);

        $owner->courses()->attach($course, ['enrolled_at' => now()]);
        $this->getJson("/api/v1/courses/{$course->id}/materials")
            ->assertOk()
            ->assertJsonPath('data.0.id', $material->id);
    }

    public function test_admin_can_view_course_materials_with_pagination(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        foreach (range(1, 16) as $number) {
            $this->createMaterial(
                $course,
                $dosen,
                "Materi {$number}"
            );
        }

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/courses/{$course->id}/materials")
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 16);

        $this->getJson("/api/v1/courses/{$course->id}/materials?page=2")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 16);
    }

    public function test_dosen_can_view_materials_from_own_course(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $material = $this->createMaterial(
            $course,
            $dosen,
            'Materi Dosen'
        );

        Sanctum::actingAs($dosen);

        $this->getJson("/api/v1/courses/{$course->id}/materials")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $material->id)
            ->assertJsonPath('data.0.title', 'Materi Dosen')
            ->assertJsonPath('data.0.uploader.id', $dosen->id);
    }

    public function test_dosen_cannot_view_materials_from_another_dosens_course(): void
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

        $this->createMaterial($course, $owner, 'Materi Rahasia');

        Sanctum::actingAs($otherDosen);

        $this->getJson("/api/v1/courses/{$course->id}/materials")
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_enrolled_mahasiswa_can_view_course_materials(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $course->students()->attach($mahasiswa->id, [
            'enrolled_at' => now(),
        ]);

        $material = $this->createMaterial(
            $course,
            $dosen,
            'Materi Mahasiswa'
        );

        Sanctum::actingAs($mahasiswa);

        $this->getJson("/api/v1/courses/{$course->id}/materials")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $material->id)
            ->assertJsonPath('data.0.title', 'Materi Mahasiswa');
    }

    public function test_mahasiswa_not_enrolled_cannot_view_course_materials(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $dosen->id,
        ]);

        $this->createMaterial($course, $dosen, 'Materi Terbatas');

        Sanctum::actingAs($mahasiswa);

        $this->getJson("/api/v1/courses/{$course->id}/materials")
            ->assertForbidden()
            ->assertJson([
                'message' => 'Anda tidak memiliki akses ke sumber daya ini.',
            ]);
    }

    public function test_materials_endpoint_requires_authentication(): void
    {
        $course = Course::factory()->create();

        $this->getJson("/api/v1/courses/{$course->id}/materials")
            ->assertUnauthorized();
    }

    private function createMaterial(
        Course $course,
        User $uploader,
        string $title
    ): Material {
        return $course->materials()->create([
            'uploaded_by' => $uploader->id,
            'title' => $title,
            'description' => "Deskripsi {$title}",
            'type' => 'link',
            'file_path' => null,
            'original_name' => null,
            'file_size' => null,
            'mime_type' => null,
            'external_url' => 'https://example.com/materials/'
                .rawurlencode($title),
        ]);
    }
}
