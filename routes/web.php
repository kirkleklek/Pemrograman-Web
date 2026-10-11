<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\CourseController;
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

// Autentikasi web untuk pengguna yang belum login.
Route::middleware('guest')->group(function () {
    Route::get('/login', [
        AuthenticatedSessionController::class,
        'create',
    ])->name('login');

    Route::post('/login', [
        AuthenticatedSessionController::class,
        'store',
    ])->name('login.store');

    // Meminta tautan reset password melalui email.
    Route::get('/forgot-password', [
        PasswordResetLinkController::class,
        'create',
    ])->name('password.request');

    Route::post('/forgot-password', [
        PasswordResetLinkController::class,
        'store',
    ])->name('password.email');

    // Menampilkan formulir dan memproses password baru.
    Route::get('/reset-password/{token}', [
        NewPasswordController::class,
        'create',
    ])->name('password.reset');

    Route::post('/reset-password', [
        NewPasswordController::class,
        'store',
    ])->name('password.store');
});

// Logout hanya dapat dilakukan pengguna yang sudah login.
Route::post('/logout', [
    AuthenticatedSessionController::class,
    'destroy',
])->middleware('auth')->name('logout');

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
                    ->middlewareFor('show', EnsurePublishedAssignment::class)
                    ->shallow();
            });
        });
});
