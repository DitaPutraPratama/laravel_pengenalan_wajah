<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/', 'face.home')->name('face.home');
Route::view('/enroll', 'face.enroll')->name('face.enroll');
Route::view('/check', 'face.check')->name('face.check');
Route::view('/delete', 'face.delete')->name('face.delete');
