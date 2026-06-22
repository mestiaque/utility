<?php

use Illuminate\Support\Facades\Route;
use ME\Http\Middleware\LocaleMiddleware;
use ME\Utility\Http\Controllers\DataReceiverController;
use ME\Utility\Http\Controllers\GeneratePackageController;
use ME\Utility\Http\Controllers\BajarListController;
use ME\Utility\Http\Controllers\UtilityController;

Route::group(['prefix' => 'utility', 'as' => 'ut.', 'middleware' => ['web', 'auth', LocaleMiddleware::class, 'activityLog']], function () {

    // ── Package & Feature Generators ──────────────────────────────────────────
    Route::get('/laravel-package-generator', [GeneratePackageController::class, 'index'])->name('package-generator');
    Route::post('/generate-laravel-package', [GeneratePackageController::class, 'generate'])->name('generate-package');

    Route::get('/laravel-feature-generator', [UtilityController::class, 'laravelFeatureGenerate'])->name('laravelFeatureGenerate');
    Route::post('/generate-laravel-feature', [UtilityController::class, 'generateLaravelFeature'])->name('generateLaravelFeature');

    Route::get('/nested-folder-generate', [UtilityController::class, 'nestedFolderGenerate'])->name('nestedFolderGenerate');
    Route::post('/generate-nested-folder', [UtilityController::class, 'generateNestedFolder'])->name('generateNestedFolder');

    // ── Misc tools ────────────────────────────────────────────────────────────
    Route::get('/wedding-card', [UtilityController::class, 'weddingCard'])->name('wedding-card');
    Route::get('/em-visualizer', [UtilityController::class, 'emVisualizer'])->name('em-visualizer');

    // ── Bajar List ────────────────────────────────────────────────────────────
    Route::prefix('bajar-list')->group(function () {
        Route::get('/',                         [BajarListController::class, 'index'])->name('bajar-list.groups.index');
        Route::post('/',                        [BajarListController::class, 'store'])->name('bajar-list.groups.store');
        Route::put('/{group}',                  [BajarListController::class, 'update'])->name('bajar-list.groups.update');
        Route::delete('/{group}',               [BajarListController::class, 'destroy'])->name('bajar-list.groups.destroy');
        Route::get('/{group}/items',            [BajarListController::class, 'listIndex'])->name('bajar-list.items.index');
        Route::get('/{group}/items/print',      [BajarListController::class, 'listPrint'])->name('bajar-list.items.print');
        Route::post('/{group}/items',           [BajarListController::class, 'listStore'])->name('bajar-list.items.store');
        Route::put('/{group}/items/{item}',     [BajarListController::class, 'listUpdate'])->name('bajar-list.items.update');
        Route::delete('/{group}/items/{item}',  [BajarListController::class, 'listDestroy'])->name('bajar-list.items.destroy');
    });

    // ── Data Receiver (admin panel) ───────────────────────────────────────────
    Route::prefix('data-receiver')->group(function () {
        Route::get('/',                             [DataReceiverController::class, 'index'])->name('data-receiver');
        Route::get('/logs',                         [DataReceiverController::class, 'logs'])->name('data-receiver.logs');
        Route::get('/logs/{uuid}',                  [DataReceiverController::class, 'logShow'])->name('data-receiver.log.show');
        Route::delete('/logs/{uuid}',               [DataReceiverController::class, 'logDestroy'])->name('data-receiver.log.destroy');
        Route::get('/files',                        [DataReceiverController::class, 'files'])->name('data-receiver.files');
        Route::get('/files/{uuid}/download',        [DataReceiverController::class, 'fileDownload'])->name('data-receiver.file.download');
        Route::delete('/files/{uuid}',              [DataReceiverController::class, 'fileDestroy'])->name('data-receiver.file.destroy');
        Route::get('/export/csv',                   [DataReceiverController::class, 'exportCsv'])->name('data-receiver.export.csv');
    });

});

// ── Public (no auth) misc routes ─────────────────────────────────────────────
Route::group(['middleware' => ['web', LocaleMiddleware::class, 'activityLog']], function () {
    Route::get('/eq',           fn () => view('utility::em-visualizer'))->name('eq');
    Route::get('/calculator',   fn () => view('utility::calculator'))->name('calculator');
    Route::get('/i-love-you',   fn () => view('utility::i_love_you.x5'))->name('i-love-you');
    Route::get('/i-love-you/{day}', fn (int $day) => $day >= 1 && $day <= 30
        ? view("utility::i_love_you.$day")
        : abort(404)
    )->where('day', '[0-9]+')->name('i-love-you.day');
});

