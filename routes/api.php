<?php

use App\Http\Controllers\Api\PumpController;
use App\Http\Controllers\Api\TelemetryController;
use Illuminate\Support\Facades\Route;

Route::post('/telemetry', [TelemetryController::class, 'store']);
Route::get('/pump/state', [PumpController::class, 'state']);
Route::post('/pump/command', [PumpController::class, 'command']);
