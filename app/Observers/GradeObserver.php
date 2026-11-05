<?php

namespace App\Observers;

use App\Models\Grade;
use App\Services\DashboardCacheService;

class GradeObserver
{
    public function __construct(
        private DashboardCacheService $dashboardCache
    ) {}

    public function saved(Grade $grade): void
    {
        $this->clearDashboardCache($grade);
    }

    public function deleting(Grade $grade): void
    {
        $this->clearDashboardCache($grade);
    }

    private function clearDashboardCache(Grade $grade): void
    {
        $grade->loadMissing('submission.assignment.course');

        $submission = $grade->submission;

        if (! $submission) {
            return;
        }

        // Hapus cache dashboard mahasiswa.
        $this->dashboardCache->forgetStudent(
            (int) $submission->user_id
        );

        // Hapus cache dashboard dosen.
        $lecturerId = $submission->assignment?->course?->lecturer_id;

        if ($lecturerId) {
            $this->dashboardCache->forgetLecturer((int) $lecturerId);
        }
    }
}
