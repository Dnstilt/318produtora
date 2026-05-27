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

    Route::get('/_debug/php-limits', function () {
        abort_unless(app()->environment('local'), 404);

        return response()->json([
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'php_ini_loaded' => php_ini_loaded_file(),
        ]);
    });
});

require __DIR__.'/auth.php';
