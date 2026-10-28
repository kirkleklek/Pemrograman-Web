<?php

namespace Tests\Feature;

use App\Events\AssignmentPublished;
use App\Events\GradeGiven;
use App\Listeners\SendGradeNotification;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\GradeGivenNotification;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MilestoneNotificationTest extends TestCase
{
    use DatabaseMigrations;

    private Course $course;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'database']);
        $this->course = Course::factory()->create();
        $this->student = User::factory()->create(['role' => 'mahasiswa']);
        $this->course->students()->attach($this->student, ['enrolled_at' => now()]);
    }

    private function assignment(string $status = 'published'): Assignment
    {
        return Assignment::factory()->createQuietly([
            'course_id' => $this->course->id,
            'created_by' => $this->course->lecturer_id,
            'status' => $status,
        ]);
    }

    private function workOnce(): void
    {
        $this->artisan('queue:work', [
            'connection' => 'database', '--once' => true, '--sleep' => 0,
        ])->assertSuccessful();
    }

    public function test_published_assignment_waits_for_worker_and_only_notifies_enrolled_students(): void
    {
        $second = User::factory()->create(['role' => 'mahasiswa']);
        $outsider = User::factory()->create(['role' => 'mahasiswa']);
        $this->course->students()->attach($second, ['enrolled_at' => now()]);

        $this->actingAs($this->course->lecturer)->post(route('dosen.courses.assignments.store', $this->course), [
            'title' => 'Tugas M3', 'instructions' => 'Kerjakan tugas',
            'due_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'max_score' => 100, 'allow_late' => false, 'status' => 'published',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $assignment = $this->course->assignments()->sole();
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('notifications', 0);
        $this->workOnce();
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('notifications', 2);
        foreach ([$this->student, $second] as $student) {
            $notification = $student->unreadNotifications()->sole();
            $this->assertSame(NewAssignmentNotification::class, $notification->type);
            $this->assertSame(['assignment_id' => $assignment->id, 'title' => 'Tugas M3'], $notification->data);
        }
        $this->assertSame(0, $outsider->notifications()->count());
        $this->assertSame(0, $this->course->lecturer->notifications()->count());
        $this->actingAs($this->student)->get(route('mahasiswa.notifications.index'))->assertOk()
            ->assertSee('Tugas baru telah dipublikasikan.')
            ->assertSee(route('mahasiswa.assignments.show', $assignment), false);
    }

    public function test_draft_and_metadata_edits_do_not_send_publication_events(): void
    {
        Event::fake([AssignmentPublished::class]);
        $assignment = Assignment::factory()->create([
            'course_id' => $this->course->id, 'status' => 'draft',
        ]);
        $assignment->update(['title' => 'Revisi draft']);
        Event::assertNotDispatched(AssignmentPublished::class);

        $assignment->update(['status' => 'published']);
        Event::assertDispatchedTimes(AssignmentPublished::class, 1);
        $assignment->update(['title' => 'Revisi published']);
        $assignment->save();
        Event::assertDispatchedTimes(AssignmentPublished::class, 1);

        $new = Assignment::factory()->create(['course_id' => $this->course->id, 'status' => 'published']);
        $new->update(['title' => 'Revisi langsung setelah create']);
        Event::assertDispatchedTimes(AssignmentPublished::class, 2);
    }

    public function test_api_draft_to_published_queues_the_same_listener(): void
    {
        $assignment = $this->assignment('draft');
        Sanctum::actingAs($this->course->lecturer);
        $this->putJson('/api/v1/assignments/'.$assignment->id, ['status' => 'published'])->assertOk();
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('notifications', 0);
        $this->workOnce();
        $this->assertSame(1, $this->student->notifications()->count());
    }

    public function test_grading_queues_private_notification_without_score_or_feedback(): void
    {
        $assignment = $this->assignment();
        $submission = Submission::factory()->create([
            'assignment_id' => $assignment->id, 'user_id' => $this->student->id,
        ]);
        $other = User::factory()->create(['role' => 'mahasiswa']);
        $this->course->students()->attach($other, ['enrolled_at' => now()]);
        Sanctum::actingAs($this->course->lecturer);

        $this->putJson('/api/v1/submissions/'.$submission->id.'/grade', [
            'score' => 87, 'feedback' => 'Feedback privat mahasiswa',
        ])->assertCreated();
        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertStringNotContainsString('Feedback privat mahasiswa', DB::table('jobs')->value('payload'));
        $this->workOnce();

        $notification = $this->student->notifications()->sole();
        $this->assertSame(GradeGivenNotification::class, $notification->type);
        $this->assertSame(['submission_id' => $submission->id, 'title' => $assignment->title], $notification->data);
        $this->assertSame(0, $other->notifications()->count());
        $this->actingAs($this->student)->get(route('mahasiswa.notifications.index'))->assertOk()
            ->assertSee(route('mahasiswa.submissions.show', $submission), false)
            ->assertDontSee('Feedback privat mahasiswa');
        $this->get(route('mahasiswa.submissions.show', $submission))->assertOk()
            ->assertSee('87.00')->assertSee('Feedback privat mahasiswa');
        $this->actingAs($other)->get(route('mahasiswa.submissions.show', $submission))->assertForbidden();
    }

    public function test_grade_changes_notify_again_but_timestamp_only_edits_do_not(): void
    {
        Event::fake([GradeGiven::class]);
        $submission = Submission::factory()->create([
            'assignment_id' => $this->assignment()->id, 'user_id' => $this->student->id,
        ]);
        $grade = Grade::create([
            'submission_id' => $submission->id, 'graded_by' => $this->course->lecturer_id,
            'score' => 80, 'feedback' => 'Awal', 'graded_at' => now(),
        ]);
        $grade->update(['graded_at' => now()->addMinute()]);
        Event::assertDispatchedTimes(GradeGiven::class, 1);
        $grade->update(['score' => 90]);
        $grade->update(['feedback' => 'Revisi']);
        Event::assertDispatchedTimes(GradeGiven::class, 3);
    }

    public function test_rollback_does_not_leave_a_notification_job(): void
    {
        DB::beginTransaction();
        Assignment::factory()->create(['course_id' => $this->course->id, 'status' => 'published']);
        $this->assertDatabaseCount('jobs', 0);
        DB::rollBack();
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_worker_skips_assignment_that_became_draft_or_was_deleted(): void
    {
        foreach (['draft', 'deleted'] as $state) {
            $assignment = $this->assignment();
            AssignmentPublished::dispatch($assignment->id);
            if ($state === 'draft') {
                $assignment->update(['status' => 'draft']);
            } else {
                $assignment->delete();
            }
            $this->workOnce();
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseCount('failed_jobs', 0);
    }

    public function test_failed_listener_retries_then_logs_and_records_failed_job(): void
    {
        $assignment = $this->assignment();
        AssignmentPublished::dispatch($assignment->id);
        $payload = json_decode(DB::table('jobs')->value('payload'), true);
        $this->assertSame(3, $payload['maxTries']);
        $this->assertSame('10', $payload['backoff']);
        Notification::shouldReceive('send')->times(3)->andThrow(new \RuntimeException('Simulated delivery failure'));
        Log::shouldReceive('error')->times(3)->with('Simulated delivery failure', \Mockery::type('array'));
        Log::shouldReceive('error')->once()->with('Notifikasi tugas gagal setelah seluruh percobaan.', [
            'assignment_id' => $assignment->id, 'exception_class' => \RuntimeException::class,
        ]);

        foreach (range(1, 3) as $attempt) {
            $this->workOnce();
            if ($attempt < 3) {
                $job = DB::table('jobs')->sole();
                $this->assertSame($attempt, $job->attempts);
                $this->assertGreaterThan(time(), $job->available_at);
                DB::table('jobs')->update(['available_at' => time() - 1]);
            }
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 1);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertDatabaseHas('assignments', ['id' => $assignment->id]);
    }

    public function test_grade_failure_handler_logs_identifiers_only(): void
    {
        $listener = new SendGradeNotification;
        $this->assertSame(3, $listener->tries);
        $this->assertSame(10, $listener->backoff);
        Log::shouldReceive('error')->once()->with('Notifikasi nilai gagal setelah seluruh percobaan.', [
            'grade_id' => 123, 'exception_class' => \RuntimeException::class,
        ]);
        $listener->failed(new GradeGiven(123), new \RuntimeException('Feedback sensitif'));
    }
}
