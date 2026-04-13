<?php
use App\Http\Controllers\Api\ZKTecoPushController;
use Illuminate\Support\Facades\Route;
use ME\Http\Middleware\LocaleMiddleware;
use ME\Utility\Http\Controllers\GeneratePackageController;
use ME\Utility\Http\Controllers\UtilityController;
use MshadyDev\ZKTeco\ZKTeco;


Route::group(['prefix' => 'utility', 'as' => 'ut.', 'middleware' => ['web', 'auth', LocaleMiddleware::class]], function () {
    Route::get('/laravel-package-generator', [GeneratePackageController::class, 'index'])->name('package-generator');
    Route::post('/generate-laravel-package', [GeneratePackageController::class, 'generate'])->name('generate-package');

    Route::get('/laravel-feature-generator', [UtilityController::class, 'laravelFeatureGenerate'])->name('laravelFeatureGenerate');
    Route::post('/generate-laravel-feature', [UtilityController::class, 'generateLaravelFeature'])->name('generateLaravelFeature');

    Route::get('/nested-folder-generate', [UtilityController::class, 'nestedFolderGenerate'])->name('nestedFolderGenerate');
    Route::post('/generate-nested-folder', [UtilityController::class, 'generateNestedFolder'])->name('generateNestedFolder');

    Route::get('/wedding-card', [UtilityController::class, 'weddingCard'])->name('wedding-card');
    Route::get('/feel-music', [UtilityController::class, 'feelMusic'])->name('feel-music');

});

Route::get('/test-zkteco', [ZKTecoPushController::class, 'test'])->name('test-zkteco');

