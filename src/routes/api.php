<?php

use Illuminate\Support\Facades\Route;
use ME\Utility\Http\Controllers\BajarListController;
use ME\Utility\Http\Controllers\DataReceiverApiController;
use ME\Utility\Http\Controllers\ImageShareController;

// Bajar-list item update
Route::put('api/bajar-list/items/{item}', [BajarListController::class, 'apiListUpdate']);

// ── Image Share API (auth:sanctum or auth) ────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('api/images',              [ImageShareController::class, 'apiIndex']);
    Route::delete('api/images/{uuid}',    [ImageShareController::class, 'apiDestroy']);
});

// ── Universal Data Receiver — public, no auth, every method ───────────────────
Route::match(
    ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    '/api/receive',
    [DataReceiverApiController::class, 'receive']
)->name('udr.api.receive');

// ZKTeco attendance machine push endpoint (also captured by UDR)
Route::match(
    ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
    '/iclock/cdata',
    [DataReceiverApiController::class, 'receive']
)->name('udr.iclock');
