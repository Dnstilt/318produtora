<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'landing']);
Route::get('/termos-de-uso', [PageController::class, 'termos']);
Route::get('/politica-de-privacidade', [PageController::class, 'privacidade']);

Route::middleware(['auth', 'can:viewAdmin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index']);
    Route::put('/sections/{id}', [AdminController::class, 'updateSection']);
    Route::post('/sections/{id}/video', [AdminController::class, 'uploadSectionVideo']);
    Route::get('/sections/{id}/status', [AdminController::class, 'sectionStatus']);

    Route::post('/photos', [AdminController::class, 'storePhoto']);
    Route::delete('/photos/{id}', [AdminController::class, 'deletePhoto']);

    Route::put('/social-links/{id}', [AdminController::class, 'updateSocialLink']);
    Route::put('/pages/{slug}', [AdminController::class, 'updatePage']);
});

require __DIR__.'/auth.php';
