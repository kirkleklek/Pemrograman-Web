<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class GradeGivenNotification extends Notification
{
    public function __construct(private int $submissionId, private string $title) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return ['submission_id' => $this->submissionId, 'title' => $this->title];
    }
}
