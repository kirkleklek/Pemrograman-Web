<?php

namespace App\Listeners;

use App\Events\GradeGiven;
use App\Models\Grade;
use App\Notifications\GradeGivenNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendGradeNotification implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 10;

    public bool $afterCommit = true;

    public function handle(GradeGiven $event): void
    {
        $grade = Grade::with(['submission.assignment', 'submission.student'])->find($event->gradeId);
        $submission = $grade?->submission;

        if (! $submission || ! $submission->student || $submission->student->role !== 'mahasiswa') {
            return;
        }

        if ($submission->assignment->status !== 'published'
            || ! $submission->student->courses()->whereKey($submission->assignment->course_id)->exists()) {
            return;
        }

        Notification::send($submission->student, new GradeGivenNotification(
            $submission->id, $submission->assignment->title,
        ));
    }

    public function failed(GradeGiven $event, Throwable $exception): void
    {
        Log::error('Notifikasi nilai gagal setelah seluruh percobaan.', [
            'grade_id' => $event->gradeId,
            'exception_class' => $exception::class,
        ]);
    }
}
