<?php

namespace App\Observers;

use App\Models\Assignment;
use App\Services\DashboardCacheService;

class AssignmentObserver
{
    public function __construct(
        private DashboardCacheService $dashboardCache
    ) {}

    public function created(Assignment $assignment): void
    {
        $this->dashboardCache->forgetCourse($assignment->course_id);
    }

    public function updating(Assignment $assignment): void
    {
        // Hapus cache mata kuliah sebelum tugas diperbarui.
        $oldCourseId = $assignment->getRawOriginal('course_id');

        if ($oldCourseId) {
            $this->dashboardCache->forgetCourse((int) $oldCourseId);
        }

        // Hapus juga cache mata kuliah tujuan jika tugas dipindahkan.
        $this->dashboardCache->forgetCourse($assignment->course_id);
    }

    public function updated(Assignment $assignment): void
    {
        $this->dashboardCache->forgetCourse($assignment->course_id);
    }

    public function deleting(Assignment $assignment): void
    {
        $this->dashboardCache->forgetCourse($assignment->course_id);
    }
}
