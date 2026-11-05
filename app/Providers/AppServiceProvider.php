<?php

namespace App\Providers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Observers\AssignmentObserver;
use App\Observers\CourseObserver;
use App\Observers\GradeObserver;
use App\Observers\SubmissionObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Observer harus aktif di semua lingkungan.
        Course::observe(CourseObserver::class);
        Assignment::observe(AssignmentObserver::class);
        Submission::observe(SubmissionObserver::class);
        Grade::observe(GradeObserver::class);

        // Mencegah lazy loading di luar production.
        if (! $this->app->isProduction()) {
            Model::preventLazyLoading();
        }
    }
}
