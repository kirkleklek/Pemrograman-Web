<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MaterialWebTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['role' => 'dosen']);
        $this->course = Course::factory()->create(['lecturer_id' => $this->owner->id]);
        $this->actingAs($this->owner);
        Storage::fake('local');
    }

    private function payload(): array
    {
        return ['title' => 'Materi Week 8', 'description' => 'Referensi', 'type' => 'link', 'external_url' => 'https://example.com/materi'];
    }

    private function material(): Material
    {
        return $this->course->materials()->create($this->payload() + ['uploaded_by' => $this->owner->id]);
    }

    public function test_owner_can_create_update_and_delete_link_metadata_without_trusting_ownership_fields(): void
    {
        $otherCourse = Course::factory()->create();
        $spoofed = ['course_id' => $otherCourse->id, 'uploaded_by' => $otherCourse->lecturer_id, 'file_path' => 'forged.pdf'];
        $this->post(route('dosen.courses.materials.store', $this->course), $this->payload() + $spoofed)
            ->assertRedirect(route('dosen.courses.materials.index', $this->course));
        $material = $this->course->materials()->sole();
        $this->assertSame($this->owner->id, $material->uploaded_by);
        $this->assertNull($material->file_path);

        $this->put(route('dosen.materials.update', $material), array_replace($this->payload(), ['title' => 'Revisi']) + $spoofed)
            ->assertRedirect(route('dosen.materials.show', $material));
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'title' => 'Revisi', 'course_id' => $this->course->id, 'uploaded_by' => $this->owner->id]);
        $this->delete(route('dosen.materials.destroy', $material))
            ->assertRedirect(route('dosen.courses.materials.index', $this->course));
        $this->assertDatabaseMissing('materials', ['id' => $material->id]);
    }

    public static function invalidMetadata(): array
    {
        return [
            'empty title' => ['title', ''],
            'long title' => ['title', str_repeat('a', 256)],
            'non-text title' => ['title', ['invalid']],
            'non-text description' => ['description', ['invalid']],
            'unknown type' => ['type', 'video'],
            'missing link URL' => ['external_url', ''],
            'invalid URL' => ['external_url', 'invalid'],
            'long URL' => ['external_url', 'https://example.com/'.str_repeat('a', 256)],
        ];
    }

    #[DataProvider('invalidMetadata')]
    public function test_invalid_metadata_is_rejected_on_create_and_update(string $field, mixed $value): void
    {
        $material = $this->material();
        $payload = array_replace($this->payload(), [$field => $value]);
        $this->post(route('dosen.courses.materials.store', $this->course), $payload)->assertSessionHasErrors($field);
        $this->put(route('dosen.materials.update', $material), $payload)->assertSessionHasErrors($field);
        $this->assertDatabaseCount('materials', 1);
        $this->assertEquals($this->payload()['title'], $material->fresh()->title);
    }

    public static function baselineUrls(): array
    {
        return [
            'HTTPS' => ['https://example.com/material'],
            'HTTP' => ['http://example.com/material'],
            'FTP' => ['ftp://example.com/material'],
        ];
    }

    #[DataProvider('baselineUrls')]
    public function test_baseline_url_protocols_are_accepted_on_create_and_update(string $url): void
    {
        $payload = array_replace($this->payload(), ['external_url' => $url]);
        $this->post(route('dosen.courses.materials.store', $this->course), $payload)
            ->assertRedirect(route('dosen.courses.materials.index', $this->course))
            ->assertSessionHasNoErrors();
        $material = $this->course->materials()->sole();
        $this->assertSame($url, $material->external_url);

        $this->put(route('dosen.materials.update', $material), $payload)
            ->assertRedirect(route('dosen.materials.show', $material))
            ->assertSessionHasNoErrors();
        $this->assertSame($url, $material->fresh()->external_url);
    }

    public static function textAtByteLimit(): array
    {
        return [
            'ASCII 65535 bytes' => [str_repeat('a', 65535)],
            'UTF-8 65535 bytes' => [str_repeat('é', 32767).'a'],
        ];
    }

    #[DataProvider('textAtByteLimit')]
    public function test_description_at_byte_limit_is_saved_on_create_and_update(string $text): void
    {
        $payload = array_replace($this->payload(), ['description' => $text]);
        $this->post(route('dosen.courses.materials.store', $this->course), $payload)
            ->assertRedirect(route('dosen.courses.materials.index', $this->course))
            ->assertSessionHasNoErrors();
        $material = $this->course->materials()->sole();
        $this->assertSame($text, $material->description);

        $material->update(['description' => 'Sebelum revisi']);

        $this->put(route('dosen.materials.update', $material), $payload)
            ->assertRedirect(route('dosen.materials.show', $material))
            ->assertSessionHasNoErrors();
        $this->assertSame($text, $material->fresh()->description);
    }

    public static function oversizedText(): array
    {
        return [
            'ASCII 65536 bytes' => [str_repeat('a', 65536)],
            'UTF-8 65536 bytes' => [str_repeat('é', 32768)],
        ];
    }

    #[DataProvider('oversizedText')]
    public function test_oversized_description_is_rejected_without_writes_on_create_and_update(string $text): void
    {
        $material = $this->material();
        $original = $material->fresh()->getAttributes();
        $payload = array_replace($this->payload(), ['description' => $text, 'title' => 'Tidak boleh disimpan']);
        $createUrl = route('dosen.courses.materials.create', $this->course);
        $editUrl = route('dosen.materials.edit', $material);

        $this->from($createUrl)->post(route('dosen.courses.materials.store', $this->course), $payload)
            ->assertRedirect($createUrl)->assertSessionHasErrors('description');
        $this->assertDatabaseCount('materials', 1);
        $this->from($editUrl)->put(route('dosen.materials.update', $material), $payload)
            ->assertRedirect($editUrl)->assertSessionHasErrors('description');
        $this->assertSame($original, $material->fresh()->getAttributes());
    }

    public function test_optional_description_can_remain_null_or_empty(): void
    {
        foreach ([null, ''] as $description) {
            $payload = array_replace($this->payload(), ['description' => $description]);
            $this->post(route('dosen.courses.materials.store', $this->course), $payload)
                ->assertRedirect(route('dosen.courses.materials.index', $this->course))
                ->assertSessionHasNoErrors();
            $material = $this->course->materials()->latest('id')->firstOrFail();
            $this->assertNull($material->description);
            $this->put(route('dosen.materials.update', $material), $payload)
                ->assertRedirect(route('dosen.materials.show', $material))
                ->assertSessionHasNoErrors();
            $this->assertNull($material->fresh()->description);
        }
    }

    public function test_file_is_required_when_creating_file_material_or_switching_from_link(): void
    {
        $material = $this->material();
        $payload = ['title' => 'File', 'type' => 'file'];
        $this->post(route('dosen.courses.materials.store', $this->course), $payload)->assertSessionHasErrors('file');
        $this->put(route('dosen.materials.update', $material), $payload)->assertSessionHasErrors('file');
        $this->assertEquals('link', $material->fresh()->type);
    }

    public function test_invalid_file_type_is_rejected_on_create_and_update(): void
    {
        $material = $this->material();
        $payload = ['title' => 'File', 'type' => 'file', 'file' => UploadedFile::fake()->create('invalid.txt', 1, 'text/plain')];
        $this->post(route('dosen.courses.materials.store', $this->course), $payload)->assertSessionHasErrors('file');
        $this->put(route('dosen.materials.update', $material), $payload)->assertSessionHasErrors('file');
        $this->assertEquals('link', $material->fresh()->type);
    }

    public function test_existing_file_can_be_retained_when_editing_metadata(): void
    {
        $this->post(route('dosen.courses.materials.store', $this->course), [
            'title' => 'PDF', 'type' => 'file', 'file' => UploadedFile::fake()->create('materi.pdf', 1, 'application/pdf'),
        ])->assertSessionHasNoErrors();
        $material = $this->course->materials()->sole();
        $filePath = $material->file_path;
        $this->put(route('dosen.materials.update', $material), ['title' => 'PDF revisi', 'type' => 'file'])
            ->assertRedirect(route('dosen.materials.show', $material));
        $this->assertEquals($filePath, $material->fresh()->file_path);
        Storage::disk('local')->assertExists($filePath);
    }

    public function test_other_lecturer_cannot_create_edit_update_or_delete_materials(): void
    {
        $material = $this->material();
        $this->actingAs(User::factory()->create(['role' => 'dosen']));
        $this->get(route('dosen.courses.materials.create', $this->course))->assertForbidden();
        $this->post(route('dosen.courses.materials.store', $this->course), $this->payload())->assertForbidden();
        $this->get(route('dosen.materials.edit', $material))->assertForbidden();
        $this->put(route('dosen.materials.update', $material), $this->payload())->assertForbidden();
        $this->delete(route('dosen.materials.destroy', $material))->assertForbidden();
        $this->assertDatabaseCount('materials', 1);
    }

    public function test_only_enrolled_students_can_read_and_students_cannot_manage_materials(): void
    {
        $material = $this->material();
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $this->actingAs($student);
        $this->get(route('mahasiswa.courses.materials.index', $this->course))->assertForbidden();
        $this->get(route('mahasiswa.materials.show', $material))->assertForbidden();
        $student->courses()->attach($this->course, ['enrolled_at' => now()]);
        $this->get(route('mahasiswa.courses.materials.index', $this->course))->assertOk()->assertSee($material->title);
        $this->get(route('mahasiswa.materials.show', $material))->assertOk();
        $this->post(route('dosen.courses.materials.store', $this->course), $this->payload())->assertForbidden();
        $this->put(route('dosen.materials.update', $material), $this->payload())->assertForbidden();
        $this->delete(route('dosen.materials.destroy', $material))->assertForbidden();
        $this->assertDatabaseCount('materials', 1);
    }

    public function test_admin_can_create_view_update_and_delete_materials(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $payload = $this->payload();

        // Admin membuat materi.
        $this->post(
            route('admin.courses.materials.store', $this->course),
            $payload
        )->assertRedirect(route('admin.courses.materials.index', $this->course));

        $material = Material::query()
            ->where('course_id', $this->course->id)
            ->where('title', $payload['title'])
            ->firstOrFail();

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'course_id' => $this->course->id,
            'uploaded_by' => $admin->id,
        ]);

        // Admin melihat detail dan halaman edit.
        $this->get(route('admin.materials.show', $material))
            ->assertOk();

        $this->get(route('admin.materials.edit', $material))
            ->assertOk();

        // Admin mengubah materi.
        $this->put(
            route('admin.materials.update', $material),
            array_replace($payload, ['title' => 'Materi Admin Revisi'])
        )->assertRedirect(route('admin.materials.show', $material));

        $this->assertDatabaseHas('materials', [
            'id' => $material->id,
            'title' => 'Materi Admin Revisi',
        ]);

        // Admin menghapus materi.
        $this->delete(route('admin.materials.destroy', $material))
            ->assertRedirect(route('admin.courses.materials.index', $this->course));

        $this->assertDatabaseMissing('materials', [
            'id' => $material->id,
        ]);
    }

    public function test_valid_pdf_pptx_and_docx_uploads_are_stored_privately(): void
    {
        $files = [
            UploadedFile::fake()->create('materi.pdf', 1, 'application/pdf'),
            UploadedFile::fake()->create('materi.pptx', 1, 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
            UploadedFile::fake()->create('materi.docx', 1, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ];

        foreach ($files as $file) {
            $this->post(route('dosen.courses.materials.store', $this->course), [
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'type' => 'file',
                'file' => $file,
            ])->assertRedirect(route('dosen.courses.materials.index', $this->course))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('materials', 3);
        foreach (Material::query()->get() as $material) {
            $this->assertNotNull($material->file_path);
            Storage::disk('local')->assertExists($material->file_path);
        }
    }

    public function test_file_over_20_mb_is_rejected_on_create_and_update(): void
    {
        $material = $this->material();
        $tooLarge = UploadedFile::fake()->create('large.pdf', 20481, 'application/pdf');

        $this->post(route('dosen.courses.materials.store', $this->course), [
            'title' => 'File besar',
            'type' => 'file',
            'file' => $tooLarge,
        ])->assertSessionHasErrors('file');

        $this->put(route('dosen.materials.update', $material), [
            'title' => 'Materi revisi',
            'type' => 'file',
            'file' => UploadedFile::fake()->create('large.pdf', 20481, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('materials', 1);
        $this->assertSame('link', $material->fresh()->type);
    }

    public function test_enrolled_student_can_download_private_material(): void
    {
        $path = 'materials/materi.pdf';
        Storage::disk('local')->put($path, 'isi materi');
        $material = $this->course->materials()->create([
            'uploaded_by' => $this->owner->id,
            'title' => 'Materi PDF',
            'type' => 'file',
            'file_path' => $path,
            'original_name' => 'materi.pdf',
            'file_size' => strlen('isi materi'),
            'mime_type' => 'application/pdf',
        ]);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $student->courses()->attach($this->course, ['enrolled_at' => now()]);

        $this->actingAs($student)
            ->get(route('materials.download', $material))
            ->assertOk()
            ->assertDownload('materi.pdf');
    }

    public function test_unenrolled_student_cannot_download_private_material(): void
    {
        $path = 'materials/rahasia.pdf';
        Storage::disk('local')->put($path, 'isi rahasia');
        $material = $this->course->materials()->create([
            'uploaded_by' => $this->owner->id,
            'title' => 'Materi Rahasia',
            'type' => 'file',
            'file_path' => $path,
            'original_name' => 'rahasia.pdf',
            'file_size' => strlen('isi rahasia'),
            'mime_type' => 'application/pdf',
        ]);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($student)
            ->get(route('materials.download', $material))
            ->assertForbidden();
    }

    public function test_guest_cannot_download_material(): void
    {
        $path = 'materials/materi.pdf';
        Storage::disk('local')->put($path, 'isi materi');
        $material = $this->course->materials()->create([
            'uploaded_by' => $this->owner->id,
            'title' => 'Materi PDF',
            'type' => 'file',
            'file_path' => $path,
            'original_name' => 'materi.pdf',
            'file_size' => strlen('isi materi'),
            'mime_type' => 'application/pdf',
        ]);

        auth()->logout();
        $this->get(route('materials.download', $material))
            ->assertRedirect(route('login'));
    }

    public function test_admin_and_course_lecturer_can_download_private_material(): void
    {
        $path = 'materials/materi.pdf';
        Storage::disk('local')->put($path, 'isi materi');
        $material = $this->course->materials()->create([
            'uploaded_by' => $this->owner->id,
            'title' => 'Materi PDF',
            'type' => 'file',
            'file_path' => $path,
            'original_name' => 'materi.pdf',
            'file_size' => strlen('isi materi'),
            'mime_type' => 'application/pdf',
        ]);

        $this->actingAs($this->owner)
            ->get(route('materials.download', $material))
            ->assertOk()
            ->assertDownload('materi.pdf');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('materials.download', $material))
            ->assertOk()
            ->assertDownload('materi.pdf');
    }

    public function test_missing_file_and_link_material_cannot_be_downloaded(): void
    {
        $link = $this->material();
        $this->get(route('materials.download', $link))->assertNotFound();

        $missing = $this->course->materials()->create([
            'uploaded_by' => $this->owner->id,
            'title' => 'Berkas hilang',
            'type' => 'file',
            'file_path' => 'materials/missing.pdf',
            'original_name' => 'missing.pdf',
        ]);
        $this->get(route('materials.download', $missing))->assertNotFound();
    }

    public function test_record_failure_removes_uploaded_file(): void
    {
        Material::creating(function (): void {
            throw new \RuntimeException('Record failed');
        });
        $this->withoutExceptionHandling();

        try {
            $this->post(route('dosen.courses.materials.store', $this->course), [
                'title' => 'PDF', 'type' => 'file',
                'file' => UploadedFile::fake()->create('materi.pdf', 1, 'application/pdf'),
            ]);
            $this->fail('The storage exception should be propagated.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Record failed', $exception->getMessage());
        }

        $this->assertDatabaseCount('materials', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('materials'));
    }
}
