<?php

use Illuminate\Support\Facades\Route;
use ME\Http\Middleware\LocaleMiddleware;
use ME\Utility\Http\Controllers\AdmsController;
use ME\Utility\Http\Controllers\DataReceiverController;
use ME\Utility\Http\Controllers\GeneratePackageController;
use ME\Utility\Http\Controllers\BajarListController;
use ME\Utility\Http\Controllers\ImageShareController;
use ME\Utility\Http\Controllers\InvoiceGeneratorController;
use ME\Utility\Http\Controllers\MediaDownloaderController;
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

    // ── Invoice Generator ─────────────────────────────────────────────────────
    Route::get('/invoice-generator', [InvoiceGeneratorController::class, 'index'])->name('invoice-generator.index');
    Route::post('/invoice-generator/generate', [InvoiceGeneratorController::class, 'generate'])->name('invoice-generator.generate');
    Route::get('/invoice-generator/history', [InvoiceGeneratorController::class, 'history'])->name('invoice-generator.history');
    Route::get('/invoice-generator/{invoiceNumber}/print', [InvoiceGeneratorController::class, 'print'])->name('invoice-generator.print');
    Route::delete('/invoice-generator/{invoiceNumber}', [InvoiceGeneratorController::class, 'destroy'])->name('invoice-generator.destroy');

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

    // ── ADMS Settings ─────────────────────────────────────────────────────────
    Route::prefix('adms')->group(function () {
        Route::get('/settings',           [AdmsController::class, 'settings'])->name('adms.settings');
        Route::post('/settings/config',   [AdmsController::class, 'saveConfig'])->name('adms.config.save');
        Route::post('/settings/schedule', [AdmsController::class, 'saveSchedule'])->name('adms.schedule.save');
        Route::get('/test-connection',    [AdmsController::class, 'testConnection'])->name('adms.test-connection');
    });

    // ── Image Share ───────────────────────────────────────────────────────────
    Route::prefix('image-share')->group(function () {
        Route::get('/',            [ImageShareController::class, 'index'])->name('image-share.index');
        Route::post('/upload',     [ImageShareController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('image-share.store');
        Route::delete('/{uuid}',   [ImageShareController::class, 'destroy'])->name('image-share.destroy');
    });

    // ── Media Downloader ──────────────────────────────────────────────────────
    Route::prefix('media-downloader')->group(function () {
        $throttle = config('utility.media_downloader.throttle', []);

        Route::get('/',                         [MediaDownloaderController::class, 'index'])->name('media-downloader.index');
        Route::post('/analyze',                 [MediaDownloaderController::class, 'analyze'])
            ->middleware('throttle:' . ($throttle['analyze'] ?? '10,1'))
            ->name('media-downloader.analyze');
        Route::post('/downloads',               [MediaDownloaderController::class, 'download'])
            ->middleware('throttle:' . ($throttle['download'] ?? '5,1'))
            ->name('media-downloader.download');
        Route::get('/downloads/{uuid}/status',  [MediaDownloaderController::class, 'status'])->name('media-downloader.status');
        Route::get('/downloads/{uuid}/file',    [MediaDownloaderController::class, 'file'])->name('media-downloader.file');
        Route::post('/downloads/{uuid}/cancel', [MediaDownloaderController::class, 'cancel'])->name('media-downloader.cancel');
        Route::delete('/downloads/{uuid}',      [MediaDownloaderController::class, 'destroy'])->name('media-downloader.destroy');
        Route::delete('/downloads',             [MediaDownloaderController::class, 'destroyAll'])->name('media-downloader.destroy-all');
        Route::post('/batches',                 [MediaDownloaderController::class, 'startBatch'])
            ->middleware('throttle:' . ($throttle['download'] ?? '5,1'))
            ->name('media-downloader.batch.start');
        Route::get('/batches/{batch}/status',   [MediaDownloaderController::class, 'batchStatus'])->whereUuid('batch')->name('media-downloader.batch.status');
        Route::post('/batches/{batch}/cancel',  [MediaDownloaderController::class, 'batchCancel'])->whereUuid('batch')->name('media-downloader.batch.cancel');
        Route::post('/batches/{batch}/retry',   [MediaDownloaderController::class, 'batchRetry'])
            ->middleware('throttle:' . ($throttle['download'] ?? '5,1'))
            ->whereUuid('batch')->name('media-downloader.batch.retry');
        Route::get('/batches/{batch}/zip',      [MediaDownloaderController::class, 'batchZip'])->whereUuid('batch')->name('media-downloader.batch.zip');
        Route::get('/setup',                    [MediaDownloaderController::class, 'setup'])->name('media-downloader.setup');
        Route::post('/setup/install',           [MediaDownloaderController::class, 'setupInstall'])
            ->middleware('throttle:3,10')
            ->name('media-downloader.setup.install');
        Route::post('/cookies',                 [MediaDownloaderController::class, 'storeCookies'])->name('media-downloader.cookies.store');
        Route::delete('/cookies',               [MediaDownloaderController::class, 'destroyCookies'])->name('media-downloader.cookies.destroy');
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

    // Public image access — no login required, shareable URL
    Route::get('/open-images/{uuid}', [ImageShareController::class, 'show'])
        ->name('ut.image-share.public');
});

