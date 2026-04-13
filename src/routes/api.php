<?php
use Illuminate\Support\Facades\Route;
use Utility\Http\Controllers\UtilityController;
use App\Http\Controllers\Api\ZKTecoPushController;

Route::prefix('api/utility')->group(function() {
    Route::get('/', [UtilityController::class, 'index']);
});
Route::post('/iclock/cdata', [ZKTecoPushController::class, 'receiveData']);
