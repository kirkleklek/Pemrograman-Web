<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware([
        'auth:sanctum',
        'throttle:60,1',
    ])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/courses', [CourseController::class, 'index']);
        Route::get('/courses/{course}', [CourseController::class, 'show']);
        Route::get('/courses/{course}/materials', [MaterialController::class, 'index']);
        Route::get('/courses/{course}/assignments', [AssignmentController::class, 'index']);

        Route::post('/assignments', [AssignmentController::class, 'store']);

        Route::match(
            ['put', 'patch'],
            '/assignments/{assignment}',
            [AssignmentController::class, 'update']
        );

        Route::get(
            '/assignments/{assignment}/submissions',
            [SubmissionController::class, 'index']
        );

        Route::post(
            '/assignments/{assignment}/submissions',
            [SubmissionController::class, 'store']
        );

        Route::put(
            '/submissions/{submission}/grade',
            [GradeController::class, 'update']
        );

        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy']);

        Route::get('/notifications', [NotificationController::class, 'index']);

        Route::post(
            '/notifications/{notification}/read',
            [NotificationController::class, 'read']
        );
    });
});