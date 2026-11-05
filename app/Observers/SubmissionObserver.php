<?php

namespace App\Observers;

use App\Models\Submission;
use App\Services\DashboardCacheService;

class SubmissionObserver
{
    public function __construct(
        private DashboardCacheService $dashboardCache
    ) {}

    public function saved(Submission $submission): void
    {
        $this->clearDashboardCache($submission);
    }

    public function deleting(Submission $submission): void
    {
        $this->clearDashboardCache($submission);
    }

    private function clearDashboardCache(Submission $submission): void
    {
        $submission->loadMissing('assignment.course');

        // Hapus cache dashboard mahasiswa pemilik submission.
        $this->dashboardCache->forgetStudent(
            (int) $submission->user_id
        );

        // Hapus cache dashboard dosen pengampu mata kuliah.
        $lecturerId = $submission->assignment?->course?->lecturer_id;

        if ($lecturerId) {
            $this->dashboardCache->forgetLecturer((int) $lecturerId);
        }
    }
}
