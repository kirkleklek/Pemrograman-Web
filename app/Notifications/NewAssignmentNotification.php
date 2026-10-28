<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NewAssignmentNotification extends Notification
{
    public function __construct(private int $assignmentId, private string $title) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return ['assignment_id' => $this->assignmentId, 'title' => $this->title];
    }
}
