<?php

declare(strict_types=1);

use App\Http\Controllers\Face\AccountController;
use App\Http\Controllers\Face\AttendController;
use App\Http\Controllers\Face\EnrollController;
use App\Http\Controllers\Face\LoginController;
use App\Http\Controllers\Face\SessionController;
use App\Http\Middleware\EnsureFaceConfigured;
use Illuminate\Support\Facades\Route;

// Pages. The face is the account (ADR-020).
Route::middleware('guest')->group(function (): void {
    Route::view('/', 'face.login')->name('face.login');
    Route::view('/enroll', 'face.enroll')->name('face.enroll');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [AccountController::class, 'show'])->name('face.dashboard');
    Route::put('/account/name', [AccountController::class, 'updateName'])->name('face.name');
    Route::delete('/attendance', [AccountController::class, 'clearAttendance'])->name('face.attendance.clear');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('face.account.destroy');
    Route::post('/logout', [AccountController::class, 'logout'])->name('face.logout');
});

// JSON for resources/js/face. In the web group so login can start a session (CSRF via X-CSRF-TOKEN).
Route::prefix('api/face')->name('face.api.')->middleware(EnsureFaceConfigured::class)->group(function (): void {
    Route::post('sessions', SessionController::class)->middleware('throttle:face-sessions')->name('sessions');
    Route::post('enroll', EnrollController::class)->middleware('throttle:face-enroll')->name('enroll');
    Route::post('login', LoginController::class)->middleware('throttle:face-login')->name('login');
    Route::post('attendance', AttendController::class)->middleware(['auth', 'throttle:face-attend'])->name('attendance');
});
