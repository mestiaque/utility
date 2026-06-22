<?php

use Illuminate\Support\Facades\Route;
use ME\Utility\Http\Controllers\BajarListController;
use ME\Utility\Http\Controllers\DataReceiverApiController;

// Bajar-list item update
Route::put('api/bajar-list/items/{item}', [BajarListController::class, 'apiListUpdate']);

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
