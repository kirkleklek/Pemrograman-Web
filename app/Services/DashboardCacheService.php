<?php

namespace App\Services;

use App\Models\Course;
use Closure;
use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    private const TTL_SECONDS = 300;

    public function rememberStudent(int $studentId, Closure $callback): mixed
    {
        return Cache::remember(
            "dashboard:student:{$studentId}",
            now()->addSeconds(self::TTL_SECONDS),
            $callback
        );
    }

    public function rememberLecturer(int $lecturerId, Closure $callback): mixed
    {
        return Cache::remember(
            "dashboard:lecturer:{$lecturerId}",
            now()->addSeconds(self::TTL_SECONDS),
            $callback
        );
    }

    public function forgetStudent(int $studentId): void
    {
        Cache::forget("dashboard:student:{$studentId}");
    }

    public function forgetLecturer(int $lecturerId): void
    {
        Cache::forget("dashboard:lecturer:{$lecturerId}");
    }

    public function forgetCourse(int $courseId): void
    {
        $course = Course::query()
            ->with('students:id')
            ->find($courseId);

        if (! $course) {
            return;
        }

        if ($course->lecturer_id) {
            $this->forgetLecturer($course->lecturer_id);
        }

        foreach ($course->students as $student) {
            $this->forgetStudent($student->id);
        }
    }
}
