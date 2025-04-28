<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeviceDataController;

// Authentication route
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// IoT Device routes
Route::post('/device-data', [DeviceDataController::class, 'store']);
Route::get('/device-data/{id}', [DeviceDataController::class, 'show']);
Route::get('/device-data/{id}/history', [DeviceDataController::class, 'history']);
