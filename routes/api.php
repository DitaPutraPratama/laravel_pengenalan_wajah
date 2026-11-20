<?php

declare(strict_types=1);

use App\Http\Controllers\Face\DeleteController;
use App\Http\Controllers\Face\EnrollController;
use App\Http\Controllers\Face\IdentifyController;
use App\Http\Controllers\Face\SessionController;
use App\Http\Middleware\EnsureFaceConfigured;
use Illuminate\Support\Facades\Route;

Route::prefix('face')->name('face.api.')->middleware(EnsureFaceConfigured::class)->group(function (): void {
    Route::post('sessions', SessionController::class)->middleware('throttle:face-sessions')->name('sessions');
    Route::post('enroll', EnrollController::class)->middleware('throttle:face-enroll')->name('enroll');
    Route::post('identify', IdentifyController::class)->middleware('throttle:face-identify')->name('identify');
    Route::post('delete/code', [DeleteController::class, 'byCode'])->middleware('throttle:face-delete')->name('delete.code');
});
