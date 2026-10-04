<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\MaterialController;
use App\Http\Controllers\Api\AssignmentController;
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

        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy']);
    });
});