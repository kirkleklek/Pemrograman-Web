<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsurePublishedAssignment;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('root');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

// Autentikasi.
Route::middleware('guest')->group(function () {
    Route::get('/login', [
        AuthenticatedSessionController::class,
        'create',
    ])->name('login');

    Route::post('/login', [
        AuthenticatedSessionController::class,
        'store',
    ])->name('login.store');

    Route::get('/forgot-password', [
        PasswordResetLinkController::class,
        'create',
    ])->name('password.request');

    Route::post('/forgot-password', [
        PasswordResetLinkController::class,
        'store',
    ])->name('password.email');

    Route::get('/reset-password/{token}', [
        NewPasswordController::class,
        'create',
    ])->name('password.reset');

    Route::post('/reset-password', [
        NewPasswordController::class,
        'store',
    ])->name('password.store');
});

Route::post('/logout', [
    AuthenticatedSessionController::class,
    'destroy',
])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/submissions/{submission}/download', [
        SubmissionController::class,
        'download',
    ])->name('submissions.download');

    Route::get('/materials/{material}/download', [
        MaterialController::class,
        'download',
    ])->name('materials.download');

    Route::get('/dashboard', function () {
        return view('welcome');
    })->name('dashboard');

    // ADMIN.
    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::resource('users', UserController::class);

            Route::resource('courses', CourseController::class);

            Route::scopeBindings()->group(function () {
                Route::resource(
                    'courses.materials',
                    MaterialController::class
                )->shallow();

                Route::resource(
                    'courses.assignments',
                    AssignmentController::class
                )->shallow();
            });

            Route::scopeBindings()->group(function () {
                Route::get(
                    'courses/{course}/enrollments',
                    [EnrollmentController::class, 'index']
                )->name('courses.enrollments.index');

                Route::post(
                    'courses/{course}/enrollments',
                    [EnrollmentController::class, 'store']
                )->name('courses.enrollments.store');

                Route::delete(
                    'courses/{course}/enrollments/{student}',
                    [EnrollmentController::class, 'destroy']
                )->name('courses.enrollments.destroy');
            });
        });

    // DOSEN.
    Route::middleware('role:dosen')
        ->prefix('dosen')
        ->name('dosen.')
        ->group(function () {

            Route::view(
                'feedback-templates',
                'dosen.feedback-templates'
            )->name('feedback-templates.index');

            Route::get(
                'courses',
                [CourseController::class, 'lecturerIndex']
            )->name('courses.index');

            // Rekap nilai mata kuliah.
            Route::get(
                'courses/{course}/grades',
                [GradeController::class, 'courseRecap']
            )->name('courses.grades.index');

            Route::scopeBindings()->group(function () {

                Route::resource(
                    'courses.materials',
                    MaterialController::class
                )->shallow();

                Route::get(
                    'courses/{course}/enrollments',
                    [EnrollmentController::class, 'index']
                )->name('courses.enrollments.index');

                Route::post(
                    'courses/{course}/enrollments',
                    [EnrollmentController::class, 'store']
                )->name('courses.enrollments.store');

                Route::delete(
                    'courses/{course}/enrollments/{student}',
                    [EnrollmentController::class, 'destroy']
                )->name('courses.enrollments.destroy');

                Route::resource(
                    'courses.assignments',
                    AssignmentController::class
                )->shallow();

                Route::resource(
                    'assignments.submissions',
                    SubmissionController::class
                )
                    ->only(['index', 'show'])
                    ->shallow();
            });
        });

    // MAHASISWA.
    Route::middleware('role:mahasiswa')
        ->prefix('mahasiswa')
        ->name('mahasiswa.')
        ->group(function () {

            Route::view('notifications', 'notifications.index')
                ->name('notifications.index');

            Route::get(
                'courses',
                [CourseController::class, 'studentIndex']
            )->name('courses.index');

            // Nilai mahasiswa yang sedang login.
            Route::get(
                'grades',
                [GradeController::class, 'studentIndex']
            )->name('grades.index');

            Route::scopeBindings()->group(function () {

                Route::resource(
                    'courses.materials',
                    MaterialController::class
                )
                    ->only(['index', 'show'])
                    ->shallow();

                Route::resource(
                    'courses.assignments',
                    AssignmentController::class
                )
                    ->only(['index', 'show'])
                    ->shallow();

                Route::resource(
                    'assignments.submissions',
                    SubmissionController::class
                )
                    ->only(['show', 'store'])
                    ->middlewareFor(
                        'show',
                        EnsurePublishedAssignment::class
                    )
                    ->shallow();
            });
        });
});
