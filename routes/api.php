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
Route::post('/device-data/{id}', [DeviceDataController::class, 'update']); // Add new route for updating specific device
Route::get('/actuator/{id}/status', [DeviceDataController::class, 'getActuatorStatus']); // Get actuator status (on/off)
Route::get('/actuators', [DeviceDataController::class, 'getAllActuators']); // Get all actuator devices
