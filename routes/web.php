<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DeviceAssignmentController;

// Auth Routes
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Dashboard routes (Protected)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Command sending route (broadcast to all)
    Route::post('/send-command', [DashboardController::class, 'sendCommand'])
        ->name('send.command');

    // Task assignment route (assign to specific devices)
    Route::post('/assign-task', [DashboardController::class, 'assignTask'])
        ->name('assign.task');

    Route::get('/device-assignments', [DeviceAssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/logs/clear', [DashboardController::class, 'clearLogs'])->name('logs.clear');
    Route::get('/device-assignments/stats', [DeviceAssignmentController::class, 'stats'])->name('assignments.stats');
    Route::post('/device-assignments/clear', [DeviceAssignmentController::class, 'clearAll'])->name('assignments.clear');
    Route::post('/device-assignments/{assignment}/delete', [DeviceAssignmentController::class, 'destroySingle'])->name('assignments.destroy');
});

// Your API routes remain separate
require __DIR__.'/api.php';
