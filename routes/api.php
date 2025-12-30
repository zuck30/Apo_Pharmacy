<?php
// routes/api.php

use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\SaleApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Product API
    Route::prefix('products')->group(function () {
        Route::get('/search', [ProductApiController::class, 'search']);
        Route::get('/{id}/stock', [ProductApiController::class, 'stock']);
        Route::get('/{id}/quick-data', [ProductApiController::class, 'quickData']);
        Route::get('/low-stock-alerts', [ProductApiController::class, 'lowStockAlerts']);
        Route::get('/expiry-alerts', [ProductApiController::class, 'expiryAlerts']);
    });
    
    // Sale API
    Route::prefix('sales')->group(function () {
        Route::post('/process', [SaleApiController::class, 'process']);
        Route::get('/daily-summary', [SaleApiController::class, 'dailySummary']);
        Route::get('/top-products', [SaleApiController::class, 'topProducts']);
    });
});