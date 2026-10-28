<?php

namespace App\Listeners;

use App\Events\AssignmentPublished;
use App\Models\Assignment;
use App\Notifications\NewAssignmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendAssignmentNotification implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 10;

    public bool $afterCommit = true;

    public function handle(AssignmentPublished $event): void
    {
        $assignment = Assignment::with('course')->find($event->assignmentId);

        // Tugas bisa dihapus atau dikembalikan ke draft saat menunggu worker.
        if (! $assignment || $assignment->status !== 'published') {
            return;
        }

        // Seluruh insert dibatalkan jika pengiriman gagal di tengah proses.
        DB::transaction(function () use ($assignment): void {
            $assignment->course->students()->where('role', 'mahasiswa')
                ->chunkById(100, function (Collection $students) use ($assignment): void {
                    Notification::send($students, new NewAssignmentNotification(
                        $assignment->id, $assignment->title,
                    ));
                }, 'users.id', 'id');
        });
    }

    public function failed(AssignmentPublished $event, Throwable $exception): void
    {
        Log::error('Notifikasi tugas gagal setelah seluruh percobaan.', [
            'assignment_id' => $event->assignmentId,
            'exception_class' => $exception::class,
        ]);
    }
}
