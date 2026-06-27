<?php

use App\Http\Controllers\Api\AffiliateController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/orders/metrics', [OrderController::class, 'metrics']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus']);
Route::get('/orders', [OrderController::class, 'index']);
Route::get('/affiliates/{id}/summary', [AffiliateController::class, 'summary']);
Route::get('/health', App\Http\Controllers\Api\HealthController::class);