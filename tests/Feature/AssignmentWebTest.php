<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssignmentWebTest extends TestCase
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
    }

    private function payload(): array
    {
        return ['title' => 'Tugas Week 8', 'instructions' => 'Kerjakan soal', 'due_at' => now()->addWeek()->format('Y-m-d\TH:i'), 'max_score' => 100, 'allow_late' => '0', 'status' => 'draft'];
    }

    private function assignment(array $attributes = []): Assignment
    {
        return Assignment::factory()->create(array_replace(['course_id' => $this->course->id, 'created_by' => $this->owner->id, 'status' => 'draft'], $attributes));
    }

    public static function textAtByteLimit(): array
    {
        return [
            'ASCII 65535 bytes' => [str_repeat('a', 65535)],
            'UTF-8 65535 bytes' => [str_repeat('é', 32767).'a'],
        ];
    }

    #[DataProvider('textAtByteLimit')]
    public function test_instructions_at_byte_limit_are_saved_on_create_and_update(string $text): void
    {
        $payload = array_replace($this->payload(), ['instructions' => $text]);
        $this->post(route('dosen.courses.assignments.store', $this->course), $payload)->assertSessionHasNoErrors();
        $assignment = $this->course->assignments()->sole();
        $this->assertSame($text, $assignment->instructions);

        $assignment->update(['instructions' => 'Sebelum revisi']);

        $this->put(route('dosen.assignments.update', $assignment), $payload)
            ->assertRedirect(route('dosen.assignments.show', $assignment))
            ->assertSessionHasNoErrors();
        $this->assertSame($text, $assignment->fresh()->instructions);
    }

    public static function oversizedText(): array
    {
        return [
            'ASCII 65536 bytes' => [str_repeat('a', 65536)],
            'UTF-8 65536 bytes' => [str_repeat('é', 32768)],
        ];
    }

    #[DataProvider('oversizedText')]
    public function test_oversized_instructions_are_rejected_without_writes_on_create_and_update(string $text): void
    {
        $assignment = $this->assignment();
        $original = $assignment->fresh()->getAttributes();
        $payload = array_replace($this->payload(), ['instructions' => $text, 'title' => 'Tidak boleh disimpan']);
        $createUrl = route('dosen.courses.assignments.create', $this->course);
        $editUrl = route('dosen.assignments.edit', $assignment);

        $this->from($createUrl)->post(route('dosen.courses.assignments.store', $this->course), $payload)
            ->assertRedirect($createUrl)->assertSessionHasErrors('instructions');
        $this->assertDatabaseCount('assignments', 1);
        $this->from($editUrl)->put(route('dosen.assignments.update', $assignment), $payload)
            ->assertRedirect($editUrl)->assertSessionHasErrors('instructions');
        $this->assertSame($original, $assignment->fresh()->getAttributes());
    }

    public function test_owner_can_create_publish_update_and_delete_without_trusting_ownership_fields(): void
    {
        $otherCourse = Course::factory()->create();
        $spoofed = ['course_id' => $otherCourse->id, 'created_by' => $otherCourse->lecturer_id];
        $this->post(route('dosen.courses.assignments.store', $this->course), $this->payload() + $spoofed)->assertSessionHasNoErrors();
        $assignment = $this->course->assignments()->sole();
        $this->assertEquals($this->owner->id, $assignment->created_by);
        $this->get(route('dosen.assignments.show', $assignment))->assertOk();
        $this->get(route('dosen.assignments.edit', $assignment))->assertOk();
        $this->put(route('dosen.assignments.update', $assignment), array_replace($this->payload(), ['status' => 'published', 'max_score' => 0]) + $spoofed)
            ->assertRedirect(route('dosen.assignments.show', $assignment));
        $this->assertDatabaseHas('assignments', ['id' => $assignment->id, 'course_id' => $this->course->id, 'created_by' => $this->owner->id, 'status' => 'published', 'max_score' => 0]);
        $this->delete(route('dosen.assignments.destroy', $assignment))
            ->assertRedirect(route('dosen.courses.assignments.index', $this->course));
        $this->assertDatabaseMissing('assignments', ['id' => $assignment->id]);
    }

    public static function invalidMetadata(): array
    {
        return [
            'empty title' => ['title', ''],
            'long title' => ['title', str_repeat('a', 256)],
            'non-text title' => ['title', ['invalid']],
            'empty instructions' => ['instructions', ''],
            'non-text instructions' => ['instructions', ['invalid']],
            'empty deadline' => ['due_at', ''],
            'invalid deadline' => ['due_at', 'not-a-date'],
            'impossible date' => ['due_at', '2026-02-30T12:00'],
            'negative score' => ['max_score', -1],
            'excess score' => ['max_score', 101],
            'fractional score' => ['max_score', 1.5],
            'invalid late flag' => ['allow_late', 'yes'],
            'missing late flag' => ['allow_late', null],
            'invalid status' => ['status', 'active'],
            'empty status' => ['status', ''],
        ];
    }

    #[DataProvider('invalidMetadata')]
    public function test_invalid_metadata_is_rejected_on_create_and_update(string $field, mixed $value): void
    {
        $assignment = $this->assignment();
        $payload = array_replace($this->payload(), [$field => $value]);
        $this->post(route('dosen.courses.assignments.store', $this->course), $payload)->assertSessionHasErrors($field);
        $this->put(route('dosen.assignments.update', $assignment), $payload)->assertSessionHasErrors($field);
        $this->assertDatabaseCount('assignments', 1);
        $this->assertEquals($assignment->title, $assignment->fresh()->title);
    }

    public function test_other_lecturer_cannot_list_create_edit_update_publish_or_delete(): void
    {
        $assignment = $this->assignment();
        $this->actingAs(User::factory()->create(['role' => 'dosen']));
        $this->get(route('dosen.courses.assignments.index', $this->course))->assertForbidden();
        $this->get(route('dosen.courses.assignments.create', $this->course))->assertForbidden();
        $this->post(route('dosen.courses.assignments.store', $this->course), $this->payload())->assertForbidden();
        $this->get(route('dosen.assignments.edit', $assignment))->assertForbidden();
        $this->put(route('dosen.assignments.update', $assignment), array_replace($this->payload(), ['status' => 'published']))->assertForbidden();
        $this->delete(route('dosen.assignments.destroy', $assignment))->assertForbidden();
        $this->assertEquals('draft', $assignment->fresh()->status);
    }

    public function test_enrolled_student_can_only_read_published_assignments_and_cannot_manage_them(): void
    {
        $draft = $this->assignment(['title' => 'Rahasia draft']);
        $published = $this->assignment(['title' => 'Tugas publik', 'status' => 'published']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $student->courses()->attach($this->course, ['enrolled_at' => now()]);
        $this->actingAs($student);
        $this->get(route('mahasiswa.courses.assignments.index', $this->course))->assertOk()->assertSee($published->title)->assertDontSee($draft->title);
        $this->get(route('mahasiswa.assignments.show', $published))->assertOk();
        $this->get(route('mahasiswa.assignments.show', $draft))->assertForbidden()->assertDontSee($draft->title);
        $this->post(route('dosen.courses.assignments.store', $this->course), $this->payload())->assertForbidden();
        $this->put(route('dosen.assignments.update', $draft), array_replace($this->payload(), ['status' => 'published']))->assertForbidden();
        $this->delete(route('dosen.assignments.destroy', $published))->assertForbidden();
        $this->assertDatabaseCount('assignments', 2);
        $this->assertEquals('draft', $draft->fresh()->status);
    }

    public function test_unenrolled_student_cannot_read_assignment_list_or_published_detail(): void
    {
        $assignment = $this->assignment(['status' => 'published']);
        $this->actingAs(User::factory()->create(['role' => 'mahasiswa']));
        $this->get(route('mahasiswa.courses.assignments.index', $this->course))->assertForbidden();
        $this->get(route('mahasiswa.assignments.show', $assignment))->assertForbidden();
    }

    public function test_metadata_update_preserves_submission_and_hides_draft_on_related_student_detail(): void
    {
        $assignment = $this->assignment(['status' => 'published']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $student->courses()->attach($this->course, ['enrolled_at' => now()]);
        $submission = Submission::factory()->create(['assignment_id' => $assignment->id, 'user_id' => $student->id]);
        $original = $submission->fresh()->getAttributes();
        $this->actingAs($student)->get(route('mahasiswa.submissions.show', $submission))->assertOk();
        $this->actingAs($this->owner)->put(route('dosen.assignments.update', $assignment), $this->payload())->assertSessionHasNoErrors();
        $this->assertEquals($original, $submission->fresh()->getAttributes());
        $this->get(route('dosen.submissions.show', $submission))->assertOk();
        $this->actingAs($student)->get(route('mahasiswa.submissions.show', $submission))->assertForbidden();
        $this->actingAs($this->owner)->put(route('dosen.assignments.update', $assignment), array_replace($this->payload(), ['status' => 'published']))->assertSessionHasNoErrors();
        $this->actingAs($student)->get(route('mahasiswa.submissions.show', $submission))->assertOk();
        $this->assertEquals($original, $submission->fresh()->getAttributes());
    }
}
