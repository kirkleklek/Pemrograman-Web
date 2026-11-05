<?php

namespace App\Observers;

use App\Models\Course;
use App\Services\DashboardCacheService;

class CourseObserver
{
    public function __construct(
        private DashboardCacheService $dashboardCache
    ) {}

    public function created(Course $course): void
    {
        $this->dashboardCache->forgetCourse($course->id);
    }

    public function updating(Course $course): void
    {
        // Hapus cache berdasarkan data mata kuliah sebelum diperbarui.
        $this->dashboardCache->forgetCourse($course->id);

        // Hapus cache dosen lama dan dosen baru jika pengampu berubah.
        $oldLecturerId = $course->getRawOriginal('lecturer_id');

        if ($oldLecturerId) {
            $this->dashboardCache->forgetLecturer((int) $oldLecturerId);
        }

        if ($course->lecturer_id) {
            $this->dashboardCache->forgetLecturer((int) $course->lecturer_id);
        }
    }

    public function updated(Course $course): void
    {
        // Pastikan cache berdasarkan relasi terbaru juga dihapus.
        $this->dashboardCache->forgetCourse($course->id);
    }

    public function deleting(Course $course): void
    {
        $this->dashboardCache->forgetCourse($course->id);
    }
}
