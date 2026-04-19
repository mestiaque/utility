<?php
use Illuminate\Support\Facades\Route;
use Utility\Http\Controllers\UtilityController;
use App\Http\Controllers\Api\ZKTecoPushController;

use ME\Utility\Http\Controllers\BajarListController;

Route::prefix('api/utility')->group(function() {
    Route::get('/', [UtilityController::class, 'index']);
});

// API route for bajar-list item update
Route::put('api/bajar-list/items/{item}', [BajarListController::class, 'apiListUpdate']);
Route::post('/iclock/cdata', [ZKTecoPushController::class, 'receiveData']);
