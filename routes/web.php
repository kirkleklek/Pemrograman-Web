<?php

use App\Http\Controllers\CourseController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('dashboard');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

Route::resource('courses', CourseController::class);
Route::resource('users', UserController::class);
