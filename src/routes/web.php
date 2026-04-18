<?php
use App\Http\Controllers\Api\ZKTecoPushController;
use Illuminate\Support\Facades\Route;
use ME\Http\Middleware\LocaleMiddleware;
use ME\Utility\Http\Controllers\GeneratePackageController;
use ME\Utility\Http\Controllers\UtilityController;
use ME\Utility\Http\Controllers\BajarListController;
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


    Route::prefix('bajar-list')->group(function () {
        Route::get('/', [BajarListController::class, 'index'])->name('bajar-list.groups.index');
        Route::post('/', [BajarListController::class, 'store'])->name('bajar-list.groups.store');
        Route::put('/{group}', [BajarListController::class, 'update'])->name('bajar-list.groups.update');
        Route::delete('/{group}', [BajarListController::class, 'destroy'])->name('bajar-list.groups.destroy');

        Route::get('/{group}/items', [BajarListController::class, 'listIndex'])->name('bajar-list.items.index');
        Route::get('/{group}/items/print', [BajarListController::class, 'listPrint'])->name('bajar-list.items.print');
        Route::post('/{group}/items', [BajarListController::class, 'listStore'])->name('bajar-list.items.store');
        Route::put('/{group}/items/{item}', [BajarListController::class, 'listUpdate'])->name('bajar-list.items.update');
        Route::delete('/{group}/items/{item}', [BajarListController::class, 'listDestroy'])->name('bajar-list.items.destroy');
    });


});

Route::get('/test-zkteco', [ZKTecoPushController::class, 'test'])->name('test-zkteco');

