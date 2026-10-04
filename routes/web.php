<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('root');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        return view('welcome');
    })->name('dashboard');


    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::resource('users', UserController::class);

            Route::resource('courses', CourseController::class);
        });


    Route::middleware('role:dosen')
        ->prefix('dosen')
        ->name('dosen.')
        ->group(function () {

            Route::scopeBindings()->group(function () {

                // Materi dalam mata kuliah
                Route::resource(
                    'courses.materials',
                    MaterialController::class
                )->shallow();

                // Tugas dalam mata kuliah
                Route::resource(
                    'courses.assignments',
                    AssignmentController::class
                )->shallow();

                // Submission tugas
                Route::resource(
                    'assignments.submissions',
                    SubmissionController::class
                )
                    ->only(['index', 'show'])
                    ->shallow();
            });
        });

    Route::middleware('role:mahasiswa')
        ->prefix('mahasiswa')
        ->name('mahasiswa.')
        ->group(function () {

            Route::scopeBindings()->group(function () {

                // Melihat materi dalam mata kuliah
                Route::resource(
                    'courses.materials',
                    MaterialController::class
                )
                    ->only(['index', 'show'])
                    ->shallow();

                // Melihat tugas dalam mata kuliah
                Route::resource(
                    'courses.assignments',
                    AssignmentController::class
                )
                    ->only(['index', 'show'])
                    ->shallow();

                // Melihat dan mengumpulkan submission
                Route::resource(
                    'assignments.submissions',
                    SubmissionController::class
                )
                    ->only(['show', 'store'])
                    ->shallow();
            });
        });
});