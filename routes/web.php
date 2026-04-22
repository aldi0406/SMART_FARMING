<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', [DashboardController::class, 'index']);
Route::get('/dashboard/live', [DashboardController::class, 'live'])
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
    ]);
Route::post('/dashboard/mode', [DashboardController::class, 'mode']);
Route::post('/dashboard/siram', [DashboardController::class, 'siram']);
